"""Screenshot helper:  python3 tools/shot.py /path/ out_prefix [--w 1440] [--h 900] [--slices 4] [--theme dark] [--mobile] [--start 0]"""
import sys, argparse, os
from playwright.sync_api import sync_playwright
from PIL import Image
ap = argparse.ArgumentParser()
ap.add_argument("path"); ap.add_argument("out")
ap.add_argument("--w", type=int, default=1440); ap.add_argument("--h", type=int, default=900)
ap.add_argument("--slices", type=int, default=0); ap.add_argument("--start", type=int, default=0)
ap.add_argument("--theme", default="light"); ap.add_argument("--mobile", action="store_true")
ap.add_argument("--base", default="http://localhost:8080")
ap.add_argument("--click", default=None)
a = ap.parse_args()
with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={"width": a.w, "height": a.h}, device_scale_factor=1, color_scheme=a.theme,
                        is_mobile=a.mobile, has_touch=a.mobile, locale="fa-IR")
    pg = ctx.new_page()
    msgs = []
    pg.on("console", lambda m: msgs.append(f"{m.type}: {m.text}") if m.type in ("error", "warning") else None)
    pg.on("pageerror", lambda e: msgs.append(f"pageerror: {e}"))
    pg.on("requestfailed", lambda r: msgs.append(f"reqfail: {r.url}"))
    pg.goto(a.base + a.path, wait_until="networkidle")
    pg.add_style_tag(content=".js .reveal{opacity:1!important;transform:none!important;transition:none!important} .hv__win,.hv__chip,.hv__phone,.page-hero__visual .mock{animation:none!important}")
    pg.evaluate("document.documentElement.setAttribute('data-theme','%s')" % a.theme)
    if a.click:
        pg.click(a.click); pg.wait_for_timeout(500)
    pg.evaluate("""async()=>{const h=document.body.scrollHeight;for(let y=0;y<h;y+=500){window.scrollTo(0,y);await new Promise(r=>setTimeout(r,60));}window.scrollTo(0,0);}""")
    pg.wait_for_timeout(800)
    if a.slices:
        total = pg.evaluate("document.documentElement.scrollHeight"); print("page height", total)
        for i in range(a.slices):
            y0 = (a.start + i) * a.h
            if y0 >= total: break
            pg.evaluate("window.scrollTo(0,%d)" % y0); pg.wait_for_timeout(900)
            pg.screenshot(path=f"{a.out}_{a.start + i + 1}.png")
    else:
        pg.screenshot(path=a.out + ".png", full_page=False)
    for m in msgs[:15]: print(m)
    b.close()
