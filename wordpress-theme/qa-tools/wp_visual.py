# -*- coding: utf-8 -*-
"""Pixel comparison: static HTML template  vs  WordPress (Sage) theme.

    python3 tools/wp_visual.py [--pages /,/services/ ...] [--widths 1440,390] [--themes light,dark] [--out qa-output/wp]

Full-page screenshots (animations frozen, reveal-on-scroll forced visible, lazy images loaded) are taken on both sites
and compared pixel by pixel.  Reports the share of differing pixels per page; writes a diff image for every mismatch.
"""
import argparse, os, sys, json
from playwright.sync_api import sync_playwright
from PIL import Image, ImageChops


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
ap.add_argument("--pages", default="")
ap.add_argument("--widths", default="1440,390")
ap.add_argument("--themes", default="light")
ap.add_argument("--out", default="qa-output/wp")
ap.add_argument("--tol", type=int, default=10, help="per-channel tolerance (anti-aliasing)")
a = ap.parse_args()

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ALL = [p["path"] for p in json.load(open(_pages_json(), encoding="utf-8")) if p["path"] != "/404.html"]
PAGES = [p for p in a.pages.split(",") if p] or ALL
WIDTHS = [int(w) for w in a.widths.split(",")]
THEMES = a.themes.split(",")
os.makedirs(a.out, exist_ok=True)

FREEZE = """
*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}
.js .reveal{opacity:1!important;transform:none!important}
"""


def shot(browser, base, path, width, theme):
    ctx = browser.new_context(viewport={"width": width, "height": 900}, color_scheme=theme, reduced_motion="reduce", locale="fa-IR",
                              is_mobile=width < 500, has_touch=width < 500, device_scale_factor=1)
    pg = ctx.new_page()
    pg.goto(base + path, wait_until="networkidle")
    pg.evaluate("t => document.documentElement.setAttribute('data-theme', t)", theme)
    pg.add_style_tag(content=FREEZE)
    pg.evaluate("""async()=>{const h=document.documentElement.scrollHeight;for(let y=0;y<h;y+=600){window.scrollTo(0,y);await new Promise(r=>setTimeout(r,70));}window.scrollTo(0,0);}""")
    pg.wait_for_timeout(500)
    pg.evaluate("document.querySelectorAll('img[loading=lazy]').forEach(i=>{i.loading='eager'})")
    pg.wait_for_load_state("networkidle")
    # mask volatile content: the fake browser URL of projects without a domain (shows the host name of the site)
    pg.add_style_tag(content=".browser__url{color:transparent!important}")
    png = pg.screenshot(full_page=True)
    ctx.close()
    return png


def main():
    import io
    bad = 0
    n = 0
    with sync_playwright() as p:
        b = p.chromium.launch()
        for theme in THEMES:
            for w in WIDTHS:
                for path in PAGES:
                    n += 1
                    try:
                        s = Image.open(io.BytesIO(shot(b, a.static, path, w, theme))).convert("RGB")
                        t = Image.open(io.BytesIO(shot(b, a.wp, path, w, theme))).convert("RGB")
                    except Exception as e:
                        print(f"! {path} {w} {theme}: {e}")
                        bad += 1
                        continue
                    name = (path.strip("/").replace("/", "_") or "home") + f"-{w}-{theme}"
                    if s.size != t.size:
                        bad += 1
                        print(f"✗ {name}: size differs static={s.size} wp={t.size}")
                        s.save(f"{a.out}/{name}-static.png"); t.save(f"{a.out}/{name}-wp.png")
                        continue
                    diff = ImageChops.difference(s, t).convert("L").point(lambda v: 255 if v > a.tol else 0)
                    bbox = diff.getbbox()
                    px = sum(1 for v in diff.getdata() if v) if bbox else 0
                    pct = 100 * px / (s.size[0] * s.size[1])
                    if px:
                        bad += 1
                        print(f"✗ {name}: {px} px differ ({pct:.4f}%) bbox={bbox}")
                        diff.save(f"{a.out}/{name}-diff.png"); s.save(f"{a.out}/{name}-static.png"); t.save(f"{a.out}/{name}-wp.png")
                    else:
                        print(f"✓ {name}: identical {s.size[0]}x{s.size[1]}")
        b.close()
    print(f"\n{n - bad}/{n} screenshots pixel-identical" + (" — ALL MATCH" if not bad else f" — {bad} differ"))
    sys.exit(1 if bad else 0)


main()
