# -*- coding: utf-8 -*-
"""DOM-level comparison: static HTML template  vs  the WordPress (Sage) theme.

    python3 tools/wp_compare.py [--static http://localhost:8080] [--wp http://localhost:8081] [--only /services/] [-v] [--max 12]

For every page of the static site (from _pages.json) both documents are fetched, parsed and reduced to a canonical token
stream (tags, normalised attributes, whitespace-collapsed text).  Differences that cannot be seen by a visitor are
normalised away:  relative vs absolute URLs, Vite file hashes, class order, number formatting inside style="",
JSON stored in data-* attributes (key order / escaping), inline-SVG sprite order.  Everything else must be identical.

Exit code 1 when any page differs.
"""
import argparse, json, os, re, sys, difflib, urllib.request, urllib.error
from urllib.parse import urlparse, urljoin
from lxml import html as LH, etree


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
ap.add_argument("--only", default=None, help="only paths containing this text")
ap.add_argument("--max", type=int, default=10, help="max differences printed per page")
ap.add_argument("-v", "--verbose", action="store_true")
ap.add_argument("--head", action="store_true", help="also compare <head> (SEO tags, JSON-LD)")
a = ap.parse_args()

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PAGES = json.load(open(_pages_json(), encoding="utf-8"))

HOSTS = {urlparse(a.static).netloc, urlparse(a.wp).netloc, "amolnovin.ir", "www.amolnovin.ir"}
URL_ATTRS = {"href", "src", "action", "data-src", "data-lightbox", "data-endpoint", "poster", "content"}
JSON_ATTRS = {"data-calc", "data-rotate", "data-chooser"}
HASH_RE = re.compile(r"-[A-Za-z0-9_-]{8}(?=\.[A-Za-z0-9]+$)")


def fetch(base, path):
    url = base.rstrip("/") + path
    req = urllib.request.Request(url, headers={"User-Agent": "wp-compare/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return r.status, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")


def norm_url(u, page_path, base_host=None):
    u = u.strip()
    if not u or u.startswith(("tel:", "mailto:", "javascript:", "data:", "#")):
        return u
    p = urlparse(urljoin("http://x" + page_path, u))
    if p.netloc in ("t.me", "wa.me") and p.query:
        from urllib.parse import parse_qsl
        q = [(k, re.sub(r"https?://(?:localhost:\d+|amolnovin\.ir)", "SITE", v)) for k, v in parse_qsl(p.query)]
        return f"{p.scheme}://{p.netloc}{p.path}?" + "&".join(f"{k}={v}" for k, v in q)
    if p.netloc not in HOSTS and p.netloc != "x":
        return u                                               # external link: untouched
    path = p.path
    m = re.search(r"/wp-content/themes/[^/]+/public/build/assets/([^/]+)$", path)
    if m:
        return "ASSET:" + HASH_RE.sub("", m.group(1))
    m = re.search(r"/(?:assets/(?:img|fonts)|wp-content/uploads/amolnovin)/(?:[^/]+/)*([^/]+)$", path)
    if m:
        return "ASSET:" + m.group(1)
    path = re.sub(r"/index\.html$", "/", path)
    if path.endswith("/feed/") or path.endswith("/feed.xml"):
        path = "/FEED"
    path = path.replace("/sitemap.xml/", "/sitemap.xml")
    if path == "/wp-json/amolnovin/v1/lead" or path == "/api/lead.php":
        path = "/LEAD-ENDPOINT"
    query = ("?" + p.query) if p.query else ""
    return path + query + (("#" + p.fragment) if p.fragment else "")


def norm_style(s):
    out = []
    for decl in s.split(";"):
        decl = decl.strip()
        if not decl:
            continue
        k, _, v = decl.partition(":")
        v = re.sub(r"\s+", " ", v.strip())
        v = re.sub(r"(?<![\d.])(\d+)\.0+(?=[a-z%]|$)", r"\1", v)          # 0.0s -> 0s
        v = re.sub(r"(\d*\.\d*?)0+(?=[a-z%]|$)", r"\1", v)                # 0.30s -> 0.3s
        v = re.sub(r"(?<![\d.])0(?=s\b)", "0", v)
        out.append(f"{k.strip().lower()}:{v}")
    return ";".join(out)


def norm_json(s):
    try:
        return json.dumps(json.loads(s), sort_keys=True, ensure_ascii=False)
    except Exception:
        return s


def norm_ws(t):
    t = re.sub(r"\s+", " ", t or "").strip()
    return re.sub(r"http://localhost:[0-9۰-۹]+", "amolnovin.ir", t)


def attrs_of(el, page_path):
    out = []
    for k, v in el.attrib.items():
        k = k.lower()
        if k == "class":
            v = " ".join(sorted(set(v.split())))
        elif k == "style":
            v = norm_style(v)
        elif k in JSON_ATTRS:
            v = norm_json(v)
        elif k in URL_ATTRS and not (el.tag == "meta" and k == "content" and not (el.get("property") or "").startswith(("og:url", "og:image")) and not (el.get("name") or "").startswith("twitter:image")):
            v = norm_url(v, page_path)
        elif k == "data-root":
            continue
        else:
            v = norm_ws(v)
        if k == "open" and v == "":
            v = "open"
        # deliberate fix in the WordPress theme: the section anchor «content» of /services/seo/ is «section-content» (it clashed with <main id="content">)
        if (k == "id" and v == "section-content") or (k == "href" and v == "#section-content"):
            v = v.replace("section-content", "content")
        out.append(f'{k}="{v}"')
    return " ".join(sorted(out))


SKIP_TAGS = {"script", "style", "noscript"}


def tokens(el, page_path, out):
    tag = el.tag if isinstance(el.tag, str) else None
    if tag is None:
        return
    if tag == "svg" and el.get("style") and "width:0" in el.get("style", "").replace(" ", ""):
        # inline sprite: compare the set of symbol ids only
        ids = sorted(s.get("id") for s in el.iter("symbol"))
        out.append("<SPRITE " + ",".join(ids) + ">")
        return
    if tag == "script":
        typ = el.get("type", "")
        if typ == "speculationrules":
            return
        src = el.get("src")
        if src:
            return
        elif typ == "application/ld+json":
            out.append("<ld+json>")
        else:
            out.append("<script inline " + norm_ws(el.text)[:60] + ">")
        return
    if tag in ("style", "noscript"):
        out.append(f"<{tag} {norm_ws(''.join(el.itertext()))[:80]}>")
        return
    out.append(f"<{tag} {attrs_of(el, page_path)}>")
    t = norm_ws(el.text)
    if t:
        out.append("T:" + t)
    for ch in el:
        tokens(ch, page_path, out)
        tl = norm_ws(ch.tail)
        if tl:
            out.append("T:" + tl)
    out.append(f"</{tag}>")


def body_tokens(doc, page_path):
    body = doc.find("body")
    out = []
    # keep <body class> comparable: WordPress adds its own classes -> only the page-<kind> class counts
    if body is not None:
        cls = [c for c in body.get("class", "").split() if c in ("page-home", "page-service", "page-generic", "page-type", "page-portfolio", "page-project", "page-store", "page-blog", "page-post", "page-sitemap", "page-error", "page-search", "page-archive")]
        out.append("<body " + " ".join(sorted(cls)) + ">")
        t = norm_ws(body.text)
        if t:
            out.append("T:" + t)
        for ch in body:
            tokens(ch, page_path, out)
            tl = norm_ws(ch.tail)
            if tl:
                out.append("T:" + tl)
    return out


def jsonld(doc, page_path):
    items = []
    for s in doc.iter("script"):
        if s.get("type") == "application/ld+json":
            try:
                items.append(json.loads(s.text))
            except Exception as e:
                items.append({"_error": str(e)})
    return items


def deep_norm(o, page_path):
    if isinstance(o, str):
        if o.startswith(("http://", "https://", "/")) and not re.search(r"\s", o):
            return norm_url(o, page_path)
        return o
    if isinstance(o, list):
        return [deep_norm(x, page_path) for x in o]
    if isinstance(o, dict):
        return {k: deep_norm(v, page_path) for k, v in o.items()}
    return o


def head_items(doc, page_path):
    h = doc.find("head")
    out = {}
    out["title"] = norm_ws(h.findtext("title"))
    for m in h.iter("meta"):
        key = m.get("name") or m.get("property") or m.get("charset")
        if key:
            val = m.get("content", "")
            if (key.startswith("og:image") and key in ("og:image",)) or key == "twitter:image" or key == "og:url":
                val = norm_url(val, page_path)
            out[f"meta:{key}:{m.get('media', '')}"] = norm_ws(val)
    for l in h.iter("link"):
        rel = l.get("rel", "")
        if rel in ("canonical", "alternate", "manifest", "sitemap", "icon", "apple-touch-icon"):
            out[f"link:{rel}:{l.get('hreflang', '')}:{l.get('type', '')}:{l.get('sizes', '')}"] = norm_url(l.get("href", ""), page_path)
    out["ld"] = json.dumps(deep_norm(jsonld(doc, page_path), page_path), sort_keys=True, ensure_ascii=False)
    return out


def json_diff(x, y, path="$"):
    out = []
    if type(x) != type(y):
        return [f"{path}: {str(x)[:90]!r} != {str(y)[:90]!r}"]
    if isinstance(x, dict):
        for k in sorted(set(x) | set(y)):
            if k not in x:
                out.append(f"{path}.{k}: only in wp = {str(y[k])[:90]!r}")
            elif k not in y:
                out.append(f"{path}.{k}: only in static = {str(x[k])[:90]!r}")
            else:
                out += json_diff(x[k], y[k], f"{path}.{k}")
    elif isinstance(x, list):
        if len(x) != len(y):
            out.append(f"{path}: list length {len(x)} != {len(y)}")
        for i, (p, q) in enumerate(zip(x, y)):
            out += json_diff(p, q, f"{path}[{i}]")
    elif x != y:
        out.append(f"{path}: {str(x)[:90]!r} != {str(y)[:90]!r}")
    return out


def compare(path, wp_path):
    s1, h1 = fetch(a.static, path)
    s2, h2 = fetch(a.wp, wp_path)
    problems = []
    if path == "/404.html":
        s1 = 200
    if s1 != 200 or (s2 != 200 and path != "/404.html"):
        return [f"HTTP static={s1} wp={s2}"], 0
    d1, d2 = LH.fromstring(h1), LH.fromstring(h2)
    t1, t2 = body_tokens(d1, path), body_tokens(d2, path)
    sm = difflib.SequenceMatcher(None, t1, t2, autojunk=False)
    n = 0
    for tag, i1, i2, j1, j2 in sm.get_opcodes():
        if tag == "equal":
            continue
        n += 1
        if len(problems) < a.max:
            problems.append(f"BODY {tag}: static[{i1}:{i2}] {t1[i1:i2][:4]}  ->  wp[{j1}:{j2}] {t2[j1:j2][:4]}")
    if a.head:
        he1, he2 = head_items(d1, path), head_items(d2, path)
        if path == "/404.html":
            for k in [k for k in list(he1) if k.startswith(("link:canonical", "link:alternate:fa-IR", "link:alternate:x-default", "meta:og:url")) or k == "ld"]:
                he1.pop(k, None); he2.pop(k, None)
        for k in sorted(set(he1) | set(he2)):
            if he1.get(k) != he2.get(k):
                n += 1
                if k == "ld":
                    for d in json_diff(json.loads(he1[k]), json.loads(he2[k]))[:8]:
                        problems.append("HEAD ld " + d)
                elif len(problems) < a.max + 6:
                    x, y = (he1.get(k) or "∅"), (he2.get(k) or "∅")
                    problems.append(f"HEAD {k}:\n      static: {x[:230]}\n      wp    : {y[:230]}")
    return problems, len(t1)


def main():
    bad = 0
    total = 0
    paths = [p for p in PAGES]
    for pg in paths:
        path = pg["path"]
        if a.only and a.only not in path:
            continue
        wp_path = "/this-page-does-not-exist/" if path == "/404.html" else path
        total += 1
        problems, ntok = compare(path, wp_path)
        if problems:
            bad += 1
            print(f"✗ {path}  ({len(problems)} diffs)")
            for p in problems:
                print("    " + p)
        elif a.verbose:
            print(f"✓ {path}  ({ntok} tokens)")
    print(f"\n{total - bad}/{total} pages identical" + (" — ALL MATCH" if not bad else f" — {bad} differ"))
    sys.exit(1 if bad else 0)


main()
