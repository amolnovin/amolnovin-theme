"""Generate 1200x630 Open-Graph images for every page (Chromium renders real Persian typography)."""
import os, re, sys, json
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
from playwright.sync_api import sync_playwright
import mockups, util
from config import SITE
from data.types_a import TYPES_A
from data.types_b import TYPES_B
from data import portfolio, blog

OUTD = f"{ROOT}/static/img/og"
os.makedirs(OUTD, exist_ok=True)
FONT = f"file://{ROOT}/static/fonts/Vazirmatn-subset.woff2"
MARK = f"file://{ROOT}/static/img/brand/logo-mark-light.svg"
CIRCUIT = f"file://{ROOT}/static/img/ui/circuit.svg"
TYPES = {t["slug"]: t for t in TYPES_A + TYPES_B}
PROJ = portfolio.BY_SLUG
POSTS = blog.BY_SLUG
pages = json.load(open(f"{ROOT}/_pages.json", encoding="utf-8"))

CSS = f"""
@font-face{{font-family:V;src:url({FONT}) format('woff2');font-weight:100 900}}
*{{box-sizing:border-box}} body{{margin:0;width:1200px;height:630px;overflow:hidden;font-family:V;direction:rtl;color:#fff;
background:radial-gradient(700px 420px at 12% 8%,rgba(19,168,158,.38),transparent 62%),radial-gradient(700px 480px at 100% 100%,rgba(75,80,180,.55),transparent 62%),linear-gradient(160deg,#171a5c,#080a26 72%);position:relative}}
.circ{{position:absolute;inset:0;background:url({CIRCUIT}) center/560px repeat;opacity:.55;-webkit-mask-image:radial-gradient(ellipse at 20% 30%,#000 5%,transparent 75%)}}
.wrap{{position:absolute;inset:0;display:grid;grid-template-columns:1.1fr .9fr;gap:30px;padding:56px 64px;align-items:center}}
.chip{{display:inline-flex;gap:10px;align-items:center;padding:6px 18px;border-radius:999px;background:rgba(46,199,187,.16);color:#6fe0d6;font-weight:700;font-size:24px;margin-bottom:22px}}
.chip:before{{content:"";width:10px;height:10px;border-radius:50%;background:#2ec7bb}}
h1{{margin:0 0 18px;font-weight:900;line-height:1.35;color:#fff}}
.sub{{margin:0;font-size:30px;color:#c7cbf3;line-height:1.7;font-weight:500}}
.foot{{position:absolute;inset-inline:64px;bottom:44px;display:flex;align-items:center;gap:18px;font-size:26px;color:#c7cbf3}}
.foot img{{height:46px}} .foot b{{color:#fff;font-size:34px;font-weight:900}} .foot .sp{{flex:1}}
.foot .url{{direction:ltr;font-weight:700;color:#6fe0d6}}
.vis{{display:grid;place-items:center;height:100%;padding-bottom:40px}}
.vis svg{{width:100%;height:auto;filter:drop-shadow(0 30px 50px rgba(0,0,0,.5));transform:rotate(-3deg)}}
.shot{{width:100%;border-radius:22px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.25);background:#fff;transform:rotate(-3deg)}}
.shot .bar{{height:34px;background:#f1f3fb;display:flex;gap:7px;align-items:center;padding:0 14px}}
.shot .bar i{{width:11px;height:11px;border-radius:50%;background:#ff6b6b}} .shot .bar i:nth-child(2){{background:#ffc247}} .shot .bar i:nth-child(3){{background:#3ddc97}}
.shot img{{display:block;width:100%;aspect-ratio:16/10;object-fit:cover;object-position:top}}
.photo{{width:100%;border-radius:26px;box-shadow:0 30px 70px rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.25);transform:rotate(-3deg);display:block}}
.bigmark{{width:92%;opacity:.95;filter:drop-shadow(0 20px 40px rgba(0,0,0,.4))}}
"""

def clean(h): return re.sub(r"<[^>]+>", "", h or "").strip()

def visual(p):
    k, slug = p["kind"], p.get("slug")
    path = p["path"]
    if k == "type":
        t = TYPES[path.strip("/").split("/")[-1]]
        return mockups.mockup(t["slug"], t["color"], "og"), f"از {SITE['price_design']} میلیون تومان، با {SITE['support_months']} ماه پشتیبانی"
    if k == "project":
        pr = PROJ[path.strip("/").split("/")[-1]]
        return f'<div class="shot"><div class="bar"><i></i><i></i><i></i></div><img src="file://{ROOT}/static/img/portfolio/{pr["img"]}-lg.webp"></div>', "نمونه‌کار و مطالعه‌ی موردی آمل نوین"
    if k == "post":
        po = POSTS[slug]
        return f'<img class="photo" src="file://{ROOT}/static/img/blog/{po["img"]}.webp">', f"{po['cat']} · {util.jdate(po['date'])}"
    if path == "/portfolio/":
        return f'<div class="shot"><div class="bar"><i></i><i></i><i></i></div><img src="file://{ROOT}/static/img/portfolio/sayana-camp-store-lg.webp"></div>', "پروژه‌های واقعی طراحی سایت، پشتیبانی و سئو"
    return f'<img class="bigmark" src="{MARK}">', "طراحی سایت · سئو · پشتیبانی در آمل و مازندران"

def build(p):
    title = clean(p.get("short_title") or p.get("h1") or p["title"])
    if p["path"] == "/": title = "طراحی سایت در آمل؛ سایتی که دیده می‌شود و مشتری می‌آورد"
    vis, sub = visual(p)
    size = 78 if len(title) <= 24 else 66 if len(title) <= 40 else 54 if len(title) <= 62 else 46
    eyebrow = p.get("cat_label") or p.get("eyebrow") or "فناوری اطلاعات آمل نوین"
    if p["kind"] == "type": eyebrow = "طراحی انواع سایت"
    html = (f"<!doctype html><html lang=fa dir=rtl><head><meta charset=utf-8><style>{CSS}</style></head><body><div class=circ></div>"
            f"<div class=wrap><div><span class=chip>{util.fa(eyebrow)}</span><h1 style='font-size:{size}px'>{util.fa(title)}</h1><p class=sub>{util.fa(sub)}</p></div><div class=vis>{vis}</div></div>"
            f"<div class=foot><img src='{MARK}'><b>آمل نوین</b><span>فناوری اطلاعات · آمل، مازندران</span><span class=sp></span><span class=url>amolnovin.ir</span></div></body></html>")
    return html

only = sys.argv[1:]
with sync_playwright() as pw:
    b = pw.chromium.launch(); pg = b.new_page(viewport={"width": 1200, "height": 630})
    n = 0
    for p in pages:
        if p.get("noindex") and p["path"] != "/404.html": continue
        if p["path"] == "/404.html": continue
        key = p.get("og") or p["path"].strip("/").replace("/", "-") or "home"
        if only and key not in only: continue
        tmp = f"{OUTD}/_tmp.html"; open(tmp, "w", encoding="utf-8").write(build(p))
        pg.goto("file://" + tmp); pg.evaluate("document.fonts.ready"); pg.wait_for_timeout(120)
        pg.screenshot(path=f"{OUTD}/{key}.jpg", type="jpeg", quality=84)
        n += 1
    os.remove(f"{OUTD}/_tmp.html"); b.close()
print("og images:", n)
