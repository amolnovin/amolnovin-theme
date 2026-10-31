import json, sys
from playwright.sync_api import sync_playwright
import os


def _pages_json():
    here = os.path.dirname(os.path.abspath(__file__))
    for d in (here, os.path.dirname(here), os.getcwd()):
        p = os.path.join(d, "_pages.json")
        if os.path.exists(p):
            return p
    raise SystemExit("_pages.json not found (it is written by the generator's build.py; a copy ships in qa-tools/)")
pages = [p["path"] for p in json.load(open(_pages_json(), encoding="utf-8"))]
W = int(sys.argv[1]) if len(sys.argv) > 1 else 390
bad = []
with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={"width": W, "height": 800}, is_mobile=W < 700, has_touch=W < 700, locale="fa-IR")
    pg = ctx.new_page()
    for path in pages:
        pg.goto(__import__("os").environ.get("QA_BASE", "http://localhost:8080") + ("/this-page-does-not-exist/" if path == "/404.html" and "8081" in __import__("os").environ.get("QA_BASE", "") else path), wait_until="load")
        pg.wait_for_timeout(250)
        r = pg.evaluate("""()=>{const W=window.innerWidth;const sw=document.documentElement.scrollWidth;if(sw<=W+1)return null;
          const out=[];document.querySelectorAll('body *').forEach(e=>{const r=e.getBoundingClientRect();if(r.right>W+2&&r.width>0&&getComputedStyle(e).position!=='fixed'){out.push((e.className&&e.className.baseVal===undefined?e.className:'')+'|'+e.tagName+'|'+Math.round(r.right))}});return {sw,out:out.slice(0,5)}}""")
        if r: bad.append((path, r))
    b.close()
print("width", W, "| pages checked:", len(pages), "| overflow:", len(bad))
for path, r in bad: print(path, r)
