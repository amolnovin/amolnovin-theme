import asyncio, os
from playwright.sync_api import sync_playwright

def render_html(html, out, width, height, scale=1, base=None, omit_bg=False, quality=None):
    """Render an HTML string (or a file:// URL) to a PNG/JPG with headless chromium."""
    with sync_playwright() as p:
        b = p.chromium.launch()
        pg = b.new_page(viewport={"width": width, "height": height}, device_scale_factor=scale)
        if html.startswith("file://") or html.startswith("http"):
            pg.goto(html)
        else:
            tmp = out + ".tmp.html"
            open(tmp, "w", encoding="utf-8").write(html)
            pg.goto("file://" + os.path.abspath(tmp))
        pg.wait_for_timeout(250)
        pg.evaluate("document.fonts.ready")
        kw = dict(path=out, omit_background=omit_bg)
        if out.lower().endswith((".jpg", ".jpeg")):
            kw["type"] = "jpeg"; kw["quality"] = quality or 85
        pg.screenshot(**kw)
        b.close()
        if not (html.startswith("file://") or html.startswith("http")):
            os.remove(tmp)
