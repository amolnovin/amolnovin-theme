# -*- coding: utf-8 -*-
"""Export the static-site data model to JSON for the WordPress (Sage) theme.

    python3 tools/export_wp.py [--theme /path/to/wp-content/themes/amolnovin]

The Blade templates read exactly the same structures the Jinja templates read, so nothing is re-typed by hand:
    resources/data/site.json      brand / contact / nav / team data (SITE, CITIES, TEAM, NAV_*)
    resources/data/common.json    shared lists (TECH, WHY, PROCESS, PLANS, CALC, ROTATOR ...)
    resources/data/icons.json     inline SVG sprite source (Lucide + Simple Icons)
    resources/data/pages.json     every page registry entry that is a normal WordPress *page*
    resources/data/types.json     20 website types      -> custom post type  website_type
    resources/data/projects.json  portfolio projects     -> custom post type  project
    resources/data/products.json  plugin store           -> custom post type  wplm_product
    resources/data/posts.json     blog posts             -> core posts
    resources/images/**           every image of the static theme + generated type mock-ups
"""
import os, re, sys, json, shutil, argparse

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
sys.path.insert(0, os.path.join(ROOT, "tools"))

ap = argparse.ArgumentParser()
_cands = [os.path.normpath(os.path.join(ROOT, "..", "amolnovin-sage")), os.path.normpath(os.path.join(ROOT, "..", "..", "wordpress-theme", "amolnovin"))]
ap.add_argument("--theme", default=next((c for c in _cands if os.path.isdir(c)), _cands[-1]), help="Sage theme folder (wordpress-theme/amolnovin)")
ap.add_argument("--out", default="/tmp/export_wp_unused")       # build.py parses --out; we never write the static site here
ARGS, _ = ap.parse_known_args()
sys.argv = [sys.argv[0], "--out", ARGS.out]

import copy
import config                     # noqa: E402

# Brand / contact values that the page definitions interpolate into their texts (phone, prices, e-mail, address …)
# are exported as tokens — «[[phone_display]]», «[[price_design]]» … — and resolved by the theme at render time from
# Appearance → Customize.  The original values are kept for site.json.
ORIG_SITE = copy.deepcopy(config.SITE)
TOKEN_KEYS = ["name", "short", "en_name", "phone_display", "phone_intl", "whatsapp", "email", "street", "city", "region", "address", "maps_query",
              "response_time", "founder", "founded_j", "founded", "years", "projects", "support_months", "price_design", "price_support", "price_seo"]


class TokInt(int):
    """An int that prints as its token (arithmetic keeps working: schema prices are computed from it)."""
    def __new__(cls, v, tok):
        o = int.__new__(cls, v)
        o.tok = tok
        return o
    def __str__(self): return self.tok
    __repr__ = __str__
    def __format__(self, spec): return self.tok if not spec else int.__format__(int(self), spec)


for _k in TOKEN_KEYS:
    _v = config.SITE[_k]
    config.SITE[_k] = TokInt(_v, f"[[{_k}]]") if isinstance(_v, int) else f"[[{_k}]]"

import build                      # noqa: E402  (registers all pages, nothing is written)
import mockups                    # noqa: E402
from data import common, portfolio, store   # noqa: E402

THEME = ARGS.theme
DATA = os.path.join(THEME, "resources", "data")
IMG = os.path.join(THEME, "resources", "images")
SITE_URL = ORIG_SITE["url"]


def tokenize(o):
    """Replace the hard-coded production origin by a token so schema URLs follow the real site URL in WordPress."""
    if isinstance(o, str):
        if o.startswith(SITE_URL + "/assets/"):
            return "@asset:" + o[len(SITE_URL + "/assets/"):]
        return o.replace(SITE_URL, "{SITE_URL}")
    if isinstance(o, (list, tuple)):
        return [tokenize(x) for x in o]
    if isinstance(o, dict):
        return {k: tokenize(v) for k, v in o.items()}
    return o


def plain(o):
    """JSON-safe copy (tuples -> lists, Markup -> str)."""
    if isinstance(o, dict):
        return {str(k): plain(v) for k, v in o.items()}
    if isinstance(o, (list, tuple, set)):
        return [plain(x) for x in o]
    if isinstance(o, (str, int, float, bool)) or o is None:
        return str(o) if (not isinstance(o, (int, float, bool)) and not isinstance(o, str) and o is not None) else o
    return str(o)


# hand-typed copy that repeats a Customizer value: prices (the three «از N میلیون» prices are unambiguous in the content) and the address
_PRICE_RES = [(re.compile(r"(?<![\d۰-۹.,])(?:17|۱۷) میلیون"), "[[price_design]] میلیون"),
              (re.compile(r"(?<![\d۰-۹.,])(?:7|۷) میلیون"), "[[price_support]] میلیون"),
              (re.compile(r"(?<![\d۰-۹.,])(?:10|۱۰) میلیون"), "[[price_seo]] میلیون")]
_PHRASES = [("آمل، خیابان طالب آملی، دریای ۳۲", "[[maps_query]]"), ("آمل، طالب آملی، دریای ۳۲", "[[city]]، [[street_short]]")]


def retoken(o):
    if isinstance(o, str):
        for rx, rep in _PRICE_RES:
            o = rx.sub(rep, o)
        for a, b in _PHRASES:
            o = o.replace(a, b)
        return o
    if isinstance(o, list):
        return [retoken(x) for x in o]
    if isinstance(o, dict):
        return {k: retoken(v) for k, v in o.items()}
    return o


def dump(name, obj):
    if name not in ("site.json", "icons.json"):
        obj = retoken(obj)
    os.makedirs(DATA, exist_ok=True)
    with open(os.path.join(DATA, name), "w", encoding="utf-8") as f:
        json.dump(obj, f, ensure_ascii=False, indent=1)
    return os.path.getsize(os.path.join(DATA, name))


SKIP_PAGE_KEYS = {"template", "t", "p", "post", "prev", "next", "canonical", "path"}


PRICE_KEYS = {int(ORIG_SITE["price_design"]): "price_design", int(ORIG_SITE["price_support"]): "price_support", int(ORIG_SITE["price_seo"]): "price_seo"}


def mark_prices(o):
    """Offer nodes: add `_price` (site key) next to the baked minPrice — Schema.php recomputes it from the Customizer values."""
    if isinstance(o, dict):
        ps = o.get("priceSpecification")
        if isinstance(ps, dict) and isinstance(ps.get("minPrice"), int):
            key = PRICE_KEYS.get(ps["minPrice"] // 10_000_000)
            if key:
                o["_price"] = key
        for v in o.values():
            mark_prices(v)
    elif isinstance(o, list):
        for v in o:
            mark_prices(v)
    return o


def page_payload(pg):
    """Registry entry -> JSON dict (entity objects are exported separately; prev/next become slugs)."""
    d = {k: v for k, v in pg.items() if k not in SKIP_PAGE_KEYS}
    if pg.get("prev"):
        d["prev_slug"] = pg["prev"]["slug"]
    if pg.get("next"):
        d["next_slug"] = pg["next"]["slug"]
    d = mark_prices(tokenize(plain(d)))
    # deliberate fix (WordPress only): a section anchor «content» collides with <main id="content"> (skip-link target) —
    # the sub-navigation link «تولید محتوا» of /services/seo/ then jumped to the top of <main> instead of its own section
    for sec in d.get("sections", []):
        if sec.get("id") == "content":
            sec["id"] = "section-content"
    return d


# ------------------------------------------------------------------ site / common
site = dict(
    site={k: (list(v) if isinstance(v, tuple) else v) for k, v in ORIG_SITE.items()},
    cities=config.CITIES, team=config.TEAM,
    nav_services=[list(x) for x in config.NAV_SERVICES], nav_more=[list(x) for x in config.NAV_MORE],
)
sizes = {"site.json": dump("site.json", plain(site))}

faq_fmt = build.pages_def.fmt_faq(common.HOME_FAQ)
common_out = dict(
    rotator=common.ROTATOR, tech=common.TECH, why=common.WHY, process=common.PROCESS, plans=common.PLANS, calc=common.CALC,
    home_projects=common.HOME_PROJECTS, home_posts=common.HOME_POSTS, service_options=common.SERVICE_OPTIONS,
    chooser_labels=common.CHOOSER_LABELS, home_faq=faq_fmt,
)
sizes["common.json"] = dump("common.json", plain(common_out))
shutil.copy(os.path.join(ROOT, "data", "icons.json"), os.path.join(DATA, "icons.json"))
sizes["icons.json"] = os.path.getsize(os.path.join(DATA, "icons.json"))

# ------------------------------------------------------------------ pages by kind
by_path = {p["path"]: p for p in build.PAGES}
pages_out, types_out, projects_out, posts_out = [], [], [], []

for pg in build.PAGES:
    path, kind = pg["path"], pg["kind"]
    if kind == "type":
        t = pg["t"]
        types_out.append(dict(slug=t["slug"], order=len(types_out), entity=plain(t), page=page_payload(pg)))
    elif kind == "project":
        p = pg["p"]
        projects_out.append(dict(slug=p["slug"], entity=plain(p), page=page_payload(pg)))
    elif kind == "post":
        p = pg["post"]
        posts_out.append(dict(slug=p["slug"], entity=plain(p), page=page_payload(pg)))
    elif kind == "error":
        continue
    else:
        slug = path.strip("/").split("/")[-1] or "home"
        parent = "/".join(path.strip("/").split("/")[:-1])
        pages_out.append(dict(path=path, slug=slug, parent=parent or None, kind=kind, template=pg["template"], page=page_payload(pg)))

# the 8 projects that only appear as portfolio cards (no case-study page)
have = {p["slug"] for p in projects_out}
for p in portfolio.PROJECTS:
    if p["slug"] not in have:
        projects_out.append(dict(slug=p["slug"], entity=plain(p), page=None))
order = [p["slug"] for p in portfolio.PROJECTS]
projects_out.sort(key=lambda x: order.index(x["slug"]))
for i, x in enumerate(projects_out):
    x["order"] = i

products_out = []
for i, pr in enumerate(store.PRODUCTS):
    products_out.append(dict(slug=pr["slug"], order=i, entity=plain(pr)))

sizes["pages.json"] = dump("pages.json", pages_out)
sizes["types.json"] = dump("types.json", types_out)
sizes["projects.json"] = dump("projects.json", dict(cats=portfolio.CATS, services=portfolio.SERVICES, list=projects_out))
sizes["products.json"] = dump("products.json", dict(cats=store.CATS, store_url=store.STORE_URL, list=products_out))
sizes["posts.json"] = dump("posts.json", posts_out)

# ------------------------------------------------------------------ images (+ generated type mock-ups)
if os.path.isdir(IMG):
    shutil.rmtree(IMG)
shutil.copytree(os.path.join(ROOT, "static", "img"), IMG)
os.makedirs(os.path.join(IMG, "types"), exist_ok=True)
for t in build.TYPES:
    with open(os.path.join(IMG, "types", f"{t['slug']}.svg"), "w", encoding="utf-8") as f:
        f.write(mockups.mockup(t["slug"], t["color"]))
nimg = sum(len(fs) for _, _, fs in os.walk(IMG))

print("exported ->", THEME)
for k, v in sizes.items():
    print(f"  {k:14s} {v / 1024:7.1f} KB")
print(f"  pages={len(pages_out)} types={len(types_out)} projects={len(projects_out)} (detail={sum(1 for p in projects_out if p['page'])}) products={len(products_out)} posts={len(posts_out)} images={nimg}")
kinds = {}
for p in pages_out:
    kinds[p["kind"]] = kinds.get(p["kind"], 0) + 1
print("  page kinds:", kinds)
sec_kinds = {}
for p in pages_out:
    for s in p["page"].get("sections", []):
        sec_kinds[s["kind"]] = sec_kinds.get(s["kind"], 0) + 1
print("  section kinds used:", dict(sorted(sec_kinds.items(), key=lambda x: -x[1])))
