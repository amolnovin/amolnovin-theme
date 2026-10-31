"""Stitched, down-scaled overview of a page:  python3 tools/overview.py /path/ out.png [--w 1440] [--scale 0.4] [--cols 2] [--theme light] [--mobile]"""
import argparse
from playwright.sync_api import sync_playwright
from PIL import Image
ap = argparse.ArgumentParser()
ap.add_argument("path"); ap.add_argument("out")
ap.add_argument("--w", type=int, default=1440); ap.add_argument("--h", type=int, default=900)
ap.add_argument("--scale", type=float, default=0.4); ap.add_argument("--cols", type=int, default=2)
ap.add_argument("--theme", default="light"); ap.add_argument("--mobile", action="store_true")
ap.add_argument("--base", default="http://localhost:8080"); ap.add_argument("--max", type=int, default=0)
a = ap.parse_args()
with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={"width": a.w, "height": a.h}, color_scheme=a.theme, is_mobile=a.mobile, has_touch=a.mobile, locale="fa-IR")
    pg = ctx.new_page(); msgs = []
    pg.on("pageerror", lambda e: msgs.append(str(e))); pg.on("requestfailed", lambda r: msgs.append("reqfail " + r.url))
    pg.goto(a.base + a.path, wait_until="networkidle")
    pg.add_style_tag(content=".js .reveal{opacity:1!important;transform:none!important;transition:none!important} .site-header,.fab,.mobile-bar,.topbar{position:static!important} .fab,.mobile-bar{display:none!important} .hv__win,.hv__chip,.hv__phone,.page-hero__visual .mock{animation:none!important}")
    pg.evaluate("document.documentElement.setAttribute('data-theme','%s')" % a.theme)
    total = pg.evaluate("document.documentElement.scrollHeight")
    tiles = []
    y = 0
    while y < total and (not a.max or len(tiles) < a.max):
        pg.evaluate("window.scrollTo(0,%d)" % y); pg.wait_for_timeout(450)
        pg.screenshot(path="/tmp/_t.png")
        im = Image.open("/tmp/_t.png").convert("RGB")
        if y + a.h > total: im = im.crop((0, a.h - (total - y), im.width, a.h))
        tiles.append(im); y += a.h
    full = Image.new("RGB", (a.w, sum(t.height for t in tiles)))
    yy = 0
    for t in tiles: full.paste(t, (0, yy)); yy += t.height
    full = full.resize((int(full.width * a.scale), int(full.height * a.scale)), Image.LANCZOS)
    colh = -(-full.height // a.cols)
    out = Image.new("RGB", (full.width * a.cols + 10 * (a.cols - 1), colh), (200, 200, 210))
    for c in range(a.cols):
        out.paste(full.crop((0, c * colh, full.width, min(full.height, (c + 1) * colh))), (c * (full.width + 10), 0))
    out.save(a.out); print("page", total, "->", out.size)
    for m in msgs[:10]: print(m)
    b.close()
