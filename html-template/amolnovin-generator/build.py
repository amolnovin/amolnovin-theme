# -*- coding: utf-8 -*-
"""Static site generator for amolnovin.ir template.   python3 build.py [--out DIR]"""
import os, re, sys, json, shutil, glob, datetime, argparse
from xml.sax.saxutils import escape as xesc
from jinja2 import Environment, FileSystemLoader
from markupsafe import Markup

ROOT = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, ROOT)
sys.path.insert(0, os.path.join(ROOT, "tools"))

import config, util, schema, mockups
from config import SITE, CITIES, NAV_SERVICES, NAV_MORE, TEAM, BUILD_DATE
from data.types_a import TYPES_A
from data.types_b import TYPES_B
from data import portfolio, store, blog, common

ap = argparse.ArgumentParser()
ap.add_argument("--out", default=os.path.normpath(os.path.join(ROOT, "..", "amolnovin-theme")))   # sibling folder: ../amolnovin-theme
ARGS, _ = ap.parse_known_args()
OUT = ARGS.out

# ------------------------------------------------------------------ data
TYPES = TYPES_A + TYPES_B
TYPE_BY = {t["slug"]: t for t in TYPES}
PROJECTS = portfolio.BY_SLUG
PRODUCTS = store.PRODUCTS
POSTS = blog.BY_SLUG
for p in blog.POSTS:
    p["jdate"] = util.jdate(p["date"])
    p["read"] = util.fa(util.read_minutes(p["body"]))
    p["words"] = len(util.strip_tags(p["body"]).split())
POSTS_SORTED = sorted(blog.POSTS, key=lambda p: p["date"], reverse=True)
ICONS = json.load(open(f"{ROOT}/data/icons.json", encoding="utf-8"))

CURRENT = {"path": "/", "abs": False}


def url(p):
    if p.startswith(("http", "mailto:", "tel:", "#")):
        return p
    if CURRENT["abs"]:
        return p
    return util.rel(CURRENT["path"], p)


def asset(p):
    return url("/assets/" + p)


def icon(name, cls=""):
    if name not in ICONS:
        raise KeyError(f"icon missing: {name}")
    c = "i" + (" i--brand" if ICONS[name]["brand"] else "") + (f" {cls}" if cls else "")
    return Markup(f'<svg class="{c}" aria-hidden="true" focusable="false"><use href="#i-{name}"/></svg>')


def resolve_links(s):
    if s is None:
        return ""
    s = re.sub(r'href="@(/[^"]*)"', lambda m: f'href="{url(m.group(1))}"', str(s))
    s = re.sub(r'src="@asset:([^"]*)"', lambda m: f'src="{asset(m.group(1))}"', s)
    return Markup(s)


class DEnv(Environment):
    """dict keys win over dict methods (so `sec.items`, `p.pop` resolve to data, not to dict.items())"""
    def getattr(self, obj, attribute):
        if isinstance(obj, dict):
            if attribute in obj:
                return obj[attribute]
            if attribute in ("pop", "copy", "clear", "update", "setdefault", "popitem", "fromkeys"):
                return self.undefined(obj=obj, name=attribute)
        return super().getattr(obj, attribute)


env = DEnv(loader=FileSystemLoader(f"{ROOT}/templates"), autoescape=True, trim_blocks=True, lstrip_blocks=True)
env.filters["rl"] = resolve_links
env.filters["fa"] = util.fa
env.globals.update(
    SITE=SITE, CITIES=CITIES, NAV_SERVICES=NAV_SERVICES, NAV_MORE=NAV_MORE, TEAM=TEAM, TYPES=TYPES, TYPE_BY=TYPE_BY,
    PROJECTS=PROJECTS, PROJECTS_LIST=portfolio.PROJECTS, PRODUCTS=PRODUCTS, PRODUCTS_BY=store.BY_SLUG, POSTS=POSTS, POSTS_SORTED=POSTS_SORTED,
    SERVICES=portfolio.SERVICES, CAT_LABEL=dict(portfolio.CATS), PCATS=portfolio.CATS, STORE_CAT=dict(store.CATS), STORE_CATS=store.CATS, STORE_URL=store.STORE_URL,
    ROTATOR=common.ROTATOR, TECH=common.TECH, WHY=common.WHY, PROCESS=common.PROCESS, PLANS=common.PLANS,
    CHOOSER={str(i): dict(slug=t['slug'], title=t['title'], tldr=t['tldr']) for i, t in enumerate(TYPES)},
    CHOOSER_OPTS=[(str(i), common.CHOOSER_LABELS[t['slug']]) for i, t in enumerate(TYPES)],
    CALC=common.CALC, HOME_PROJECTS=common.HOME_PROJECTS, HOME_POSTS=common.HOME_POSTS, SERVICE_OPTIONS=common.SERVICE_OPTIONS,
    url=url, asset=asset, icon=icon, fa=util.fa, jdate=util.jdate,
)

# ------------------------------------------------------------------ pages registry
PAGES = []


def add(path, template, **kw):
    kw.update(path=path, template=template)
    kw.setdefault("kind", template.replace(".html", ""))
    kw.setdefault("crumbs", [])
    kw.setdefault("priority", 0.6)
    kw.setdefault("changefreq", "monthly")
    PAGES.append(kw)
    return kw


import pages_def
pages_def.register(add, dict(TYPES=TYPES, TYPE_BY=TYPE_BY, PROJECTS=PROJECTS, PRODUCTS=PRODUCTS, POSTS=POSTS, POSTS_SORTED=POSTS_SORTED))


# ------------------------------------------------------------------ rendering
def abs_url(path):
    return SITE["url"] + path


def og_for(page):
    key = page.get("og") or page["path"].strip("/").replace("/", "-") or "home"
    cand = f"{OUT}/assets/img/og/{key}.jpg"
    return f"/assets/img/og/{key}.jpg" if os.path.exists(cand) else "/assets/img/og/default.jpg"


def build_sprite(html):
    names = sorted(set(re.findall(r'href="#i-([a-z0-9-]+)"', html)))
    syms = "".join(f'<symbol id="i-{n}" viewBox="{ICONS[n]["vb"]}">{ICONS[n]["body"]}</symbol>' for n in names if n in ICONS)
    svg = f'<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false"><defs>{syms}</defs></svg>'
    return html.replace("<!--SPRITE-->", svg)


def render(page):
    CURRENT["path"] = page["path"]
    CURRENT["abs"] = page.get("abs_links", False)
    canonical = abs_url(page["path"])
    og_image = abs_url(og_for(page))
    page["canonical"] = canonical
    g = schema.graph(page, canonical, og_image)
    if page["kind"] == "post":
        g["@graph"].append(util.fa_obj(schema.article(POSTS[page["slug"]], canonical, og_image)))
    ctx = dict(page=page, root=("/" if CURRENT["abs"] else util.root_prefix(page["path"])), canonical=canonical, og_image=og_image, schema_json=util.ld(g))
    html = env.get_template(page["template"]).render(**ctx)
    html = build_sprite(html)
    html = util.fa_digits_html(html)
    return html


def write(path, content, binary=False):
    full = os.path.join(OUT, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, "wb" if binary else "w", **({} if binary else {"encoding": "utf-8"})) as f:
        f.write(content)


def page_file(page):
    p = page["path"]
    if p.endswith(".html"):
        return p.lstrip("/")
    return p.strip("/") + "/index.html" if p.strip("/") else "index.html"


# ------------------------------------------------------------------ static assets
def copy_assets():
    for sub in ("assets",):
        shutil.rmtree(f"{OUT}/{sub}/css", ignore_errors=True)
    os.makedirs(f"{OUT}/assets/css", exist_ok=True)
    css = "/* Amol Novin design system — generated from static/css/*.css */\n"
    for f in sorted(glob.glob(f"{ROOT}/static/css/*.css")):
        css += f"\n/* ===== {os.path.basename(f)} ===== */\n" + open(f, encoding="utf-8").read()
    write("assets/css/style.css", css)
    os.makedirs(f"{OUT}/assets/js", exist_ok=True)
    shutil.copy(f"{ROOT}/static/js/main.js", f"{OUT}/assets/js/main.js")
    for sub in ("fonts", "img"):
        src = f"{ROOT}/static/{sub}"
        dst = f"{OUT}/assets/{sub}"
        for dp, _dn, fn in os.walk(src):
            for f in fn:
                s = os.path.join(dp, f)
                d = os.path.join(dst, os.path.relpath(s, src))
                os.makedirs(os.path.dirname(d), exist_ok=True)
                shutil.copy(s, d)
    # website-type mock-ups
    os.makedirs(f"{OUT}/assets/img/types", exist_ok=True)
    for t in TYPES:
        write(f"assets/img/types/{t['slug']}.svg", mockups.mockup(t["slug"], t["color"]))
    shutil.copy(f"{ROOT}/static/img/brand/favicon.ico", f"{OUT}/favicon.ico")
    shutil.copy(f"{ROOT}/static/img/brand/apple-touch-icon.png", f"{OUT}/apple-touch-icon.png")


def search_index(pages):
    kind_label = {"home": "صفحه", "service": "خدمت", "type": "نوع سایت", "project": "نمونه‌کار", "post": "مقاله", "generic": "صفحه"}
    items = []
    for p in pages:
        if p.get("noindex") or p["path"].endswith(".html"):
            continue
        title = p.get("short_title") or (re.sub(r"<[^>]+>", "", p["h1"]) if p.get("h1") else p["title"])
        items.append(dict(t=title, u=p["path"], d=p["desc"],
                          k=" ".join([p.get("kw", "")] + p.get("kws", [])), c=p.get("cat_label") or kind_label.get(p["kind"], "صفحه")))
    write("assets/js/search-index.js", "window.AVN_INDEX=" + json.dumps(items, ensure_ascii=False, separators=(",", ":")) + ";")


def sitemap(pages):
    urls = []
    for p in pages:
        if p.get("noindex") or p["path"].endswith(".html"):
            continue
        urls.append(f"<url><loc>{xesc(abs_url(p['path']))}</loc><lastmod>{p.get('modified', BUILD_DATE.isoformat())}</lastmod>"
                    f"<changefreq>{p['changefreq']}</changefreq><priority>{p['priority']}</priority>"
                    f"<xhtml:link rel=\"alternate\" hreflang=\"fa-IR\" href=\"{xesc(abs_url(p['path']))}\"/></url>")
    xml = ('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">\n'
           + "\n".join(urls) + "\n</urlset>\n")
    write("sitemap.xml", xml)
    return len(urls)


def feed():
    items = []
    for p in POSTS_SORTED:
        d = datetime.date.fromisoformat(p["date"])
        items.append(f"<item><title>{xesc(p['title'])}</title><link>{abs_url('/' + p['slug'] + '/')}</link><guid isPermaLink=\"true\">{abs_url('/' + p['slug'] + '/')}</guid>"
                     f"<pubDate>{d.strftime('%a, %d %b %Y')} 08:00:00 +0330</pubDate><category>{xesc(p['cat'])}</category><description>{xesc(p['desc'])}</description></item>")
    xml = ('<?xml version="1.0" encoding="UTF-8"?>\n<rss version="2.0"><channel>'
           f"<title>وبلاگ {SITE['name']}</title><link>{SITE['url']}/blog-n/</link><description>راهنمای طراحی سایت، سئو و پشتیبانی سایت</description><language>fa-IR</language>"
           + "".join(items) + "</channel></rss>\n")
    write("feed.xml", xml)


def robots_llms_manifest():
    write("robots.txt", f"""# {SITE['name']} — robots.txt
User-agent: *
Allow: /
Disallow: /api/
Disallow: /*?s=
Disallow: /*?replytocom=

# AI / answer-engine crawlers are welcome to read the public content
User-agent: GPTBot
Allow: /
User-agent: OAI-SearchBot
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: Google-Extended
Allow: /

Sitemap: {SITE['url']}/sitemap.xml
""")
    top = [(NAV_SERVICES[i][1], NAV_SERVICES[i][0]) for i in range(len(NAV_SERVICES))]
    lines = [f"# {SITE['name']} ({SITE['en_name']})", "",
             f"> {SITE['name']} — طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا، امنیت و سرعت سایت در آمل، مازندران؛ فعال از سال {SITE['founded_j']} با پروانه‌ی کسب رسمی.", "",
             "## اطلاعات کلیدی",
             f"- نشانی: {SITE['address']}", f"- تلفن و واتساپ: {SITE['phone_display']}", f"- ایمیل: {SITE['email']}",
             f"- قیمت از: طراحی سایت {SITE['price_design']} میلیون تومان؛ پشتیبانی {SITE['price_support']} میلیون تومان در ماه؛ سئو {SITE['price_seo']} میلیون تومان در ماه",
             "- زمان تحویل: سایت شرکتی ۱۰ تا ۲۰ روز کاری؛ فروشگاهی ۲۰ تا ۴۵ روز کاری", f"- پشتیبانی: {SITE['support_months']} ماه پشتیبانی فنی رایگان با قرارداد کتبی", "",
             "## خدمات"] + [f"- [{n}]({SITE['url']}{u}): {d}" for (u, n, d, _i, _c) in NAV_SERVICES] + ["", "## انواع سایت"] + \
            [f"- [{t['title']}]({SITE['url']}/services/web-design/{t['slug']}/): {t['short']}" for t in TYPES] + \
            ["", "## صفحات مهم", f"- [نمونه‌کارها]({SITE['url']}/portfolio/)", f"- [هزینه طراحی سایت]({SITE['url']}/price-wordpress/)", f"- [طراحی سایت در آمل]({SITE['url']}/web-design-amol/)",
             f"- [فروشگاه افزونه‌ها]({SITE['url']}/wplm-store/)", f"- [وبلاگ]({SITE['url']}/blog-n/)", f"- [تماس با ما]({SITE['url']}/contact-us/)", ""]
    txt = "\n".join(lines)
    txt = "".join(seg if seg.startswith("http") else util.fa(seg) for seg in re.split(r"(https?://[^\s)]+)", txt))
    write("llms.txt", txt)
    write("manifest.webmanifest", json.dumps({
        "name": SITE["name"], "short_name": SITE["short"], "description": SITE["tagline"], "lang": "fa", "dir": "rtl", "start_url": "/", "scope": "/",
        "display": "standalone", "background_color": "#ffffff", "theme_color": SITE["theme_color"],
        "icons": [{"src": "/assets/img/brand/icon-192.png", "sizes": "192x192", "type": "image/png"}, {"src": "/assets/img/brand/icon-512.png", "sizes": "512x512", "type": "image/png"},
                  {"src": "/assets/img/brand/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable"}]}, ensure_ascii=False, indent=2))


HTACCESS = r"""# ==========================================================================
#  Amol Novin — Apache config (UTF-8 file).  Works on cPanel / DirectAdmin hosts.
# ==========================================================================
AddDefaultCharset UTF-8
Options -Indexes
DirectoryIndex index.html index.php
ErrorDocument 404 /404.html

<IfModule mod_rewrite.c>
RewriteEngine On
# --- force HTTPS + non-www (uncomment after SSL is active) ---
# RewriteCond %{HTTPS} off [OR]
# RewriteCond %{HTTP_HOST} ^www\. [NC]
# RewriteRule ^ https://amolnovin.ir%{REQUEST_URI} [L,R=301]

# --- old WordPress URLs  ->  new structure (301 keeps Google rankings) ---
RedirectMatch 301 ^/طراحی-سایت-شرکتی-در-آمل-نوین/?$   /services/web-design/corporate/
RedirectMatch 301 ^/طراحی-سایت-فروشگاهی-در-آمل/?$      /services/web-design/ecommerce/
RedirectMatch 301 ^/طراحی-سایت-املاک-در-آمل-نوین/?$    /services/web-design/real-estate/
RedirectMatch 301 ^/طراحی-سایت-وکالت-در-آمل-نوین/?$    /services/web-design/lawyer/
RedirectMatch 301 ^/projects/?$                          /portfolio/
RedirectMatch 301 ^/projects/فروشگاه-رنگ-صادقی/?$      /projects/rang-sadeghi/
RedirectMatch 301 ^/projects/فروشگاه-گل-و-گیاه-مانلی/?$ /projects/maneli-plants/

# --- no trailing-slash duplicates ---
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^(.*)$ /$1/ [L,R=301]
</IfModule>

# --- security headers ---
<IfModule mod_headers.c>
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
# Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"   # enable once HTTPS is final
</IfModule>

# --- compression ---
<IfModule mod_deflate.c>
AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json application/xml image/svg+xml font/woff2
</IfModule>

# --- caching ---
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType text/html "access plus 0 seconds"
ExpiresByType text/css "access plus 1 year"
ExpiresByType application/javascript "access plus 1 year"
ExpiresByType image/webp "access plus 1 year"
ExpiresByType image/svg+xml "access plus 1 year"
ExpiresByType image/png "access plus 1 year"
ExpiresByType image/jpeg "access plus 1 year"
ExpiresByType font/woff2 "access plus 1 year"
ExpiresByType application/xml "access plus 1 day"
</IfModule>
AddType application/manifest+json .webmanifest
AddType font/woff2 .woff2
"""

LEAD_PHP = r"""<?php
/**
 * Minimal lead endpoint (optional).  Receives the forms of the template and mails them to the owner.
 * Requires PHP mail() / local MTA.  If it is missing the forms fall back to WhatsApp automatically.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
$TO = 'info@amolnovin.ir';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$clean = function ($v, $max = 600) { $v = trim(strip_tags((string)$v)); $v = preg_replace('/[\r\n]+/', ' ', $v); return mb_substr($v, 0, $max); };
if (!empty($in['website'])) { echo json_encode(['ok' => true]); exit; }              // honeypot
$name = $clean($in['name'] ?? '', 120); $phone = preg_replace('/[^0-9+]/', '', (string)($in['phone'] ?? ''));
if (mb_strlen($name) < 2 || !preg_match('/^(\+98|0)?9[0-9]{9}$/', $phone)) { http_response_code(422); echo json_encode(['ok' => false, 'error' => 'invalid']); exit; }
$ip = $_SERVER['REMOTE_ADDR'] ?? '0'; $f = sys_get_temp_dir() . '/lead_' . md5($ip);
if (is_file($f) && time() - filemtime($f) < 30) { http_response_code(429); echo json_encode(['ok' => false, 'error' => 'rate']); exit; }
touch($f);
$body = "نام: $name\nموبایل: $phone\nخدمت: " . $clean($in['service'] ?? '') . "\nپیام: " . $clean($in['message'] ?? '', 1500) . "\nصفحه: " . $clean($in['page'] ?? '') . "\nIP: $ip\n";
$subject = '=?UTF-8?B?' . base64_encode('درخواست مشاوره‌ی جدید از سایت') . '?=';
$ok = @mail($TO, $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: no-reply@amolnovin.ir");
echo json_encode(['ok' => (bool)$ok]);
"""


def main():
    os.makedirs(OUT, exist_ok=True)
    og_keep = f"{OUT}/assets/img/og"
    tmp_og = None
    if os.path.isdir(og_keep):                       # keep generated OG images across rebuilds
        tmp_og = f"{ROOT}/.og_cache"
        shutil.rmtree(tmp_og, ignore_errors=True)
        shutil.copytree(og_keep, tmp_og)
    for item in os.listdir(OUT):
        if item in ("docs",):
            continue
        pth = os.path.join(OUT, item)
        shutil.rmtree(pth) if os.path.isdir(pth) else os.remove(pth)
    copy_assets()
    if tmp_og:
        shutil.copytree(tmp_og, og_keep, dirs_exist_ok=True)
        shutil.rmtree(tmp_og, ignore_errors=True)
    n = 0
    for page in PAGES:
        write(page_file(page), render(page))
        n += 1
    search_index(PAGES)
    cnt = sitemap(PAGES)
    feed()
    robots_llms_manifest()
    write(".htaccess", HTACCESS)
    write("api/lead.php", LEAD_PHP)
    write("api/.htaccess", "<IfModule mod_headers.c>\nHeader set X-Robots-Tag \"noindex\"\n</IfModule>\n")
    meta = [{k: p.get(k) for k in ("path", "kind", "title", "desc", "kw", "kws", "noindex", "og", "h1", "short_title", "cat_label", "eyebrow", "slug")} for p in PAGES]
    json.dump(meta, open(f"{ROOT}/_pages.json", "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print(f"built {n} pages, sitemap {cnt} urls -> {OUT}")


if __name__ == "__main__":
    main()
