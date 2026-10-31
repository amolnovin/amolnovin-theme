# -*- coding: utf-8 -*-
"""WordPress-specific QA: virtual files vs the static template, redirects, Customizer, headers, PHP log.

    python3 tools/wp_qa.py [--static http://localhost:8080] [--wp http://localhost:8081] [--site-dir /path/to/wordpress]
"""
import argparse, json, os, re, subprocess, sys, urllib.request, urllib.error
import xml.etree.ElementTree as ET
from urllib.parse import urlparse


def _pages_json():
    here = os.path.dirname(os.path.abspath(__file__))
    for d in (here, os.path.dirname(here), os.getcwd()):
        p = os.path.join(d, "_pages.json")
        if os.path.exists(p):
            return p
    raise SystemExit("_pages.json not found (it is written by the generator's build.py; a copy ships in qa-tools/)")

ap = argparse.ArgumentParser()
ap.add_argument("--static", default="http://localhost:8080")
ap.add_argument("--wp", default="http://localhost:8081")
ap.add_argument("--site-dir", default=os.environ.get("QA_SITE_DIR", "."), help="WordPress folder (for WP-CLI and debug.log)")
a = ap.parse_args()
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PAGES = json.load(open(_pages_json(), encoding="utf-8"))
res = []


def ok(name, cond, extra=""):
    res.append(bool(cond)); print(("PASS " if cond else "FAIL ") + name, extra)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kw):
        return None


def get(base, path, follow=True):
    req = urllib.request.Request(base.rstrip("/") + path, headers={"User-Agent": "wp-qa"})
    opener = urllib.request.build_opener() if follow else urllib.request.build_opener(NoRedirect)
    try:
        r = opener.open(req, timeout=60)
        return r.status, dict(r.headers), r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, dict(e.headers), e.read().decode("utf-8", "replace")


def wp(*args):
    return subprocess.run(["wp", *args, "--allow-root"], capture_output=True, text=True, cwd=a.site_dir).stdout.strip()


def pth(u):
    p = urlparse(u).path
    return re.sub(r"index\.html$", "", p)


# ---------------------------------------------------------------- crawl everything once (fills the PHP log if anything is wrong)
log_file = os.path.join(a.site_dir, "wp-content/debug.log")
open(log_file, "w").close()
bad = [p["path"] for p in PAGES if p["path"] != "/404.html" and get(a.wp, p["path"])[0] != 200]
ok("all 57 pages answer 200", not bad, str(bad))
st, hd, body = get(a.wp, "/this-page-does-not-exist/")
ok("unknown URL -> real 404 status with the theme's 404 page", st == 404 and "۴۰۴" in body and "صفحه‌ی مورد نظر پیدا نشد" in body)
ok("404 page is noindex", 'noindex,follow' in body)

# ---------------------------------------------------------------- sitemap.xml
ns = {"s": "http://www.sitemaps.org/schemas/sitemap/0.9"}
def sitemap(base):
    st, _, x = get(base, "/sitemap.xml")
    root = ET.fromstring(x)
    return st, {pth(u.find("s:loc", ns).text): (u.find("s:changefreq", ns).text, u.find("s:priority", ns).text) for u in root.findall("s:url", ns)}
s1, m1 = sitemap(a.static)
s2, m2 = sitemap(a.wp)
ok("sitemap.xml: same URL set as the static template", s2 == 200 and set(m1) == set(m2), f"static={len(m1)} wp={len(m2)} diff={sorted(set(m1) ^ set(m2))[:4]}")
ok("sitemap.xml: same changefreq / priority per URL", all(m1[k] == m2.get(k) for k in m1), str([(k, m1[k], m2.get(k)) for k in m1 if m1[k] != m2.get(k)][:3]))

# ---------------------------------------------------------------- robots / llms / manifest / feed
st, _, robots = get(a.wp, "/robots.txt")
ok("robots.txt has sitemap + AI crawlers", st == 200 and "Sitemap:" in robots and "GPTBot" in robots and "Disallow: /wp-admin/" in robots)
st1, _, l1 = get(a.static, "/llms.txt"); st2, _, l2 = get(a.wp, "/llms.txt")
norm = lambda t: re.sub(r"https?://(?:localhost:\d+|amolnovin\.ir)", "SITE", t).strip()
ok("llms.txt identical to the static file", st2 == 200 and norm(l1) == norm(l2), "")
st1, _, w1 = get(a.static, "/manifest.webmanifest"); st2, h2, w2 = get(a.wp, "/manifest.webmanifest")
j1, j2 = json.loads(w1), json.loads(w2)
for j in (j1, j2): j.pop("icons", None)
ok("manifest.webmanifest same as static (name, colours, rtl)", st2 == 200 and j1 == j2 and "manifest+json" in h2.get("Content-Type", ""))
st, h, feed = get(a.wp, "/feed/")
ok("RSS feed (/feed/) works with 5 posts", st == 200 and "<rss" in feed and feed.count("<item>") == 5, f"({feed.count('<item>')} items)")

# ---------------------------------------------------------------- search index
st, _, html = get(a.wp, "/")
m = re.search(r'<script src="([^"]*search-index\.js[^"]*)" defer>', html)
ok("home page loads search-index.js", bool(m))
if m:
    st, _, js = get("", m.group(1).replace("&#038;", "&")) if m.group(1).startswith("http") else (0, {}, "")
    wpi = {i["u"]: i for i in json.loads(js[len("window.AVN_INDEX="):].rstrip(";"))}
    _, _, sj = get(a.static, "/assets/js/search-index.js")
    sti = {i["u"]: i for i in json.loads(sj[len("window.AVN_INDEX="):].rstrip(";"))}
    ok("search index: same pages as the static template", set(wpi) == set(sti), f"static={len(sti)} wp={len(wpi)} diff={sorted(set(wpi) ^ set(sti))[:4]}")
    same = all(sti[k]["t"] == wpi[k]["t"] and sti[k]["d"] == wpi[k]["d"] and sti[k]["c"] == wpi[k]["c"] and sti[k]["k"].split() == wpi[k]["k"].split() for k in sti if k in wpi)
    ok("search index: same title / description / category / keywords", same)

# ---------------------------------------------------------------- redirects
RED = {"/طراحی-سایت-شرکتی-در-آمل-نوین/": "/services/web-design/corporate/", "/طراحی-سایت-فروشگاهی-در-آمل/": "/services/web-design/ecommerce/",
       "/طراحی-سایت-املاک-در-آمل-نوین/": "/services/web-design/real-estate/", "/طراحی-سایت-وکالت-در-آمل-نوین/": "/services/web-design/lawyer/",
       "/projects/": "/portfolio/", "/projects/فروشگاه-رنگ-صادقی/": "/projects/rang-sadeghi/", "/projects/فروشگاه-گل-و-گیاه-مانلی/": "/projects/maneli-plants/", "/feed.xml": "/feed/"}
from urllib.parse import quote
for old, new in RED.items():
    st, h, _ = get(a.wp, quote(old, safe="/"), follow=False)
    ok(f"301 {old} -> {new}", st == 301 and pth(h.get("Location", "")) == new, f"(got {st} {h.get('Location', '')})")

# ---------------------------------------------------------------- headers
st, h, _ = get(a.wp, "/")
hl = {k.lower(): v for k, v in h.items()}
ok("security headers", hl.get("x-content-type-options") == "nosniff" and "strict-origin" in hl.get("referrer-policy", "") and "camera=()" in hl.get("permissions-policy", ""))

# ---------------------------------------------------------------- customizer: the phone number is one setting
before = get(a.wp, "/contact-us/")[2]
ok("default phone 0933 202 1365 on the contact page", "۰۹۳۳ ۲۰۲ ۱۳۶۵" in before and "tel:+989332021365" in before and "wa.me/989332021365" in before)
wp("eval", 'set_theme_mod("amol_site", ["phone" => "09121112233", "price_design" => 19, "social" => ["instagram" => "https://instagram.com/amolnovin"]]);')
after = get(a.wp, "/contact-us/")[2]
home = get(a.wp, "/")[2]
ok("Customizer phone -> display, tel:, WhatsApp and JSON-LD", "۰۹۱۲ ۱۱۱ ۲۲۳۳" in after and "tel:+989121112233" in after and "wa.me/989121112233" in after and '"telephone":"+989121112233"' in after and "۰۹۳۳ ۲۰۲" not in after)
ok("Customizer price -> home page + schema", "از ۱۹ میلیون تومان" in home and "۱۹ میلیون تومان" in home)
ok("Customizer social link -> footer + sameAs", 'aria-label="instagram"' in home and "instagram.com/amolnovin" in home and '"sameAs"' in home)
wp("eval", 'remove_theme_mod("amol_site");')
restored = get(a.wp, "/contact-us/")[2]
ok("settings restored", "۰۹۳۳ ۲۰۲ ۱۳۶۵" in restored)

# ---------------------------------------------------------------- PHP log
log = open(log_file, encoding="utf-8", errors="replace").read()
bad_lines = [l for l in log.splitlines() if re.search(r"PHP (Warning|Notice|Deprecated|Fatal|Parse)", l)]
ok("no PHP warnings / notices / deprecations in debug.log after the crawl", not bad_lines, "\n      " + "\n      ".join(bad_lines[:5]))

print(f"\n{sum(res)}/{len(res)} passed")
sys.exit(0 if all(res) else 1)
