"""SEO + integrity audit of the generated site.  python3 tools/audit.py [OUT] [--md report.md]"""
import os, re, sys, json, glob, collections
from bs4 import BeautifulSoup
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = sys.argv[1] if len(sys.argv) > 1 and not sys.argv[1].startswith("--") else os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "amolnovin-theme"))
MD = sys.argv[sys.argv.index("--md") + 1] if "--md" in sys.argv else None
SITE = "https://amolnovin.ir"
meta = {m["path"]: m for m in json.load(open(f"{ROOT}/_pages.json", encoding="utf-8"))}

def norm(s):
    s = s.replace("\u200c", "").replace("ي", "ی").replace("ك", "ک").replace("ۀ", "ه")
    s = s.translate(str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩", "01234567890123456789"))
    return re.sub(r"\s+", " ", s).strip().lower()

files = [f for f in glob.glob(f"{OUT}/**/*.html", recursive=True)]
def path_of(f):
    rel = os.path.relpath(f, OUT).replace(os.sep, "/")
    if rel == "index.html": return "/"
    if rel.endswith("/index.html"): return "/" + rel[:-len("index.html")]
    return "/" + rel

docs, ids = {}, {}
for f in files:
    soup = BeautifulSoup(open(f, encoding="utf-8").read(), "lxml")
    docs[f] = soup
    ids[f] = {t.get("id") for t in soup.find_all(id=True)}

errors, warns = [], []
rows = []
titles, descs = collections.defaultdict(list), collections.defaultdict(list)
for f, soup in sorted(docs.items()):
    p = path_of(f); m = meta.get(p, {}); e = lambda msg: errors.append((p, msg)); w = lambda msg: warns.append((p, msg))
    title = (soup.title.string or "").strip() if soup.title else ""
    d = soup.find("meta", attrs={"name": "description"}); desc = d["content"].strip() if d else ""
    h1s = soup.find_all("h1")
    can = soup.find("link", rel="canonical")
    if not title: e("no <title>")
    if not desc: e("no meta description")
    if len(h1s) != 1: e(f"{len(h1s)} <h1>")
    if not can or not can.get("href", "").startswith(SITE): e("canonical missing/invalid")
    elif p != "/404.html" and can["href"] != SITE + p: e(f"canonical mismatch {can['href']}")
    if soup.html.get("lang") != "fa" or soup.html.get("dir") != "rtl": e("html lang/dir")
    for prop in ("og:title", "og:description", "og:image", "og:url", "og:type"):
        if not soup.find("meta", property=prop): e(f"missing {prop}")
    if not soup.find("meta", attrs={"name": "twitter:card"}): e("missing twitter:card")
    if p != "/404.html" and not m.get("noindex"):
        if not (28 <= len(title) <= 75): w(f"title length {len(title)}")
        if not (100 <= len(desc) <= 175): w(f"description length {len(desc)}")
        titles[title].append(p); descs[desc].append(p)
    # JSON-LD
    types = []
    for s in soup.find_all("script", type="application/ld+json"):
        try:
            j = json.loads(s.string)
            for n in j.get("@graph", [j]):
                t = n.get("@type"); types += t if isinstance(t, list) else [t]
        except Exception as ex:
            e(f"invalid JSON-LD: {ex}")
    if "Organization" not in types and "ProfessionalService" not in types: e("no Organization schema")
    # images
    for im in soup.find_all("img"):
        if im.find_parent(id="lightbox"): continue
        if im.get("alt") is None: e(f"img without alt: {im.get('src')}")
        if not im.get("width") or not im.get("height"): w(f"img without width/height: {im.get('src')}")
    # links
    for a in soup.find_all("a", href=True):
        h = a["href"]
        if re.match(r"^(https?:|mailto:|tel:|javascript:|sms:)", h): continue
        if p == "/404.html" and h.startswith("/"): tgt = os.path.join(OUT, h.lstrip("/").split("#")[0].split("?")[0])
        else:
            base = os.path.dirname(f)
            pth, _, frag = h.partition("#"); pth = pth.split("?")[0]
            tgt = os.path.normpath(os.path.join(base, pth)) if pth else f
        frag = h.partition("#")[2]
        if os.path.isdir(tgt): tgt = os.path.join(tgt, "index.html")
        if not os.path.exists(tgt): e(f"broken link {h}"); continue
        if frag and tgt in ids and frag not in ids[tgt]: e(f"broken anchor {h}")
    for tag, attr in (("link", "href"), ("script", "src"), ("img", "src")):
        for t in soup.find_all(tag):
            u = t.get(attr)
            if not u or re.match(r"^(https?:|data:|#|mailto:|tel:)", u): continue
            tgt = os.path.join(OUT, u.lstrip("/")) if p == "/404.html" else os.path.normpath(os.path.join(os.path.dirname(f), u.split("?")[0]))
            if not os.path.exists(tgt): e(f"missing asset {u}")
    # headings
    hs = [int(h.name[1]) for h in soup.find_all(re.compile(r"^h[1-6]$"))]
    for a_, b_ in zip(hs, hs[1:]):
        if b_ - a_ > 1: w(f"heading jump h{a_}->h{b_}"); break
    # content
    for t in soup(["script", "style", "svg"]): t.decompose()
    main = soup.find("main") or soup.body
    text = norm(main.get_text(" "))
    words = len(text.split())
    kw = m.get("kw"); kws = m.get("kws") or []
    kwr = {}
    if kw and not m.get("noindex"):
        nk = norm(kw)
        kwr["title"] = nk in norm(title); kwr["h1"] = bool(h1s) and nk in norm(h1s[0].get_text()); kwr["desc"] = nk in norm(desc)
        kwr["h2"] = any(nk in norm(h.get_text()) for h in main.find_all("h2")); kwr["body"] = text.count(nk)
        kwr["first150"] = nk in " ".join(text.split()[:150])
        kwr["sec"] = f"{sum(1 for k in kws if norm(k) in text)}/{len(kws)}"
    rows.append((p, words, len(title), len(desc), kwr))
for t, ps in titles.items():
    if len(ps) > 1: errors.append((ps[0], f"duplicate title with {ps[1:]}"))
for t, ps in descs.items():
    if len(ps) > 1: errors.append((ps[0], f"duplicate description with {ps[1:]}"))
print(f"pages: {len(files)} | errors: {len(errors)} | warnings: {len(warns)}")
for p, msg in errors[:60]: print("ERR ", p, msg)
for p, msg in warns[:40]: print("WARN", p, msg)
if MD:
    L = ["# گزارش ممیزی سئو و یکپارچگی (خودکار)", "", f"- تعداد صفحات: **{len(files)}**", f"- خطا: **{len(errors)}** · هشدار: **{len(warns)}**", "",
         "| صفحه | کلمه | طول عنوان | طول توضیح | کلیدواژه‌ی اصلی در: عنوان / H1 / توضیح / H2 / ۱۵۰ کلمه‌ی اول | تعداد تکرار | کلمات فرعی |", "|---|---|---|---|---|---|---|"]
    for p, words, tl, dl, k in sorted(rows):
        if k:
            mark = lambda v: "✓" if v else "✗"
            L.append(f"| `{p}` | {words} | {tl} | {dl} | {mark(k['title'])} / {mark(k['h1'])} / {mark(k['desc'])} / {mark(k['h2'])} / {mark(k['first150'])} | {k['body']} | {k['sec']} |")
        else:
            L.append(f"| `{p}` | {words} | {tl} | {dl} | — | — | — |")
    if errors: L += ["", "## خطاها"] + [f"- `{p}`: {m}" for p, m in errors]
    if warns: L += ["", "## هشدارها"] + [f"- `{p}`: {m}" for p, m in warns]
    open(MD, "w", encoding="utf-8").write("\n".join(L) + "\n")
sys.exit(1 if errors else 0)
