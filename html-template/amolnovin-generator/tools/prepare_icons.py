"""Favicons / app icons from the traced mark + symbol for inline usage."""
import json, os, sys, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from render import render_html
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
B = f"{ROOT}/static/img/brand"
P = json.load(open(f"{ROOT}/data/_logo_paths.json"))
vb = P["viewBox"]
vx, vy, vw, vh = [float(x) for x in vb.split()]

# symbol that follows CSS custom properties (theme aware) -> used by templates via <use href="#logo-mark">
symbol = (f'<symbol id="logo-mark" viewBox="{vb}">'
          f'<path style="fill:var(--logo-n,#272869)" fill-rule="evenodd" d="{P["d_navy"]}"/>'
          f'<path style="fill:var(--logo-t,#13A89E)" fill-rule="evenodd" d="{P["d_teal"]}"/></symbol>')
open(f"{ROOT}/templates/_logo_symbol.svg", "w").write(symbol)

# standalone svg favicon (auto light/dark)
fav = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vx-30:.1f} {vy-95:.1f} {vw+60:.1f} {vw+60:.1f}">'
       f'<style>.n{{fill:#272869}}.t{{fill:#13A89E}}@media(prefers-color-scheme:dark){{.n{{fill:#E8E9FF}}}}</style>'
       f'<path class="n" fill-rule="evenodd" d="{P["d_navy"]}"/><path class="t" fill-rule="evenodd" d="{P["d_teal"]}"/></svg>')
open(f"{B}/favicon.svg", "w").write(fav)

def icon_html(size, bg, pad, radius, navy="#272869"):
    inner = size - 2 * pad
    mark = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" width="{inner}" height="{inner*vh/vw:.1f}">'
            f'<path fill="{navy}" fill-rule="evenodd" d="{P["d_navy"]}"/><path fill="#13A89E" fill-rule="evenodd" d="{P["d_teal"]}"/></svg>')
    return (f'<!doctype html><html><body style="margin:0;background:transparent"><div style="width:{size}px;height:{size}px;'
            f'background:{bg};border-radius:{radius}px;display:grid;place-items:center">{mark}</div></body></html>')

jobs = [("icon-512.png", 512, "#ffffff", 44, 112), ("icon-192.png", 192, "#ffffff", 17, 42),
        ("apple-touch-icon.png", 180, "#ffffff", 16, 0), ("favicon-32.png", 32, "#ffffff", 2, 6),
        ("favicon-48.png", 48, "#ffffff", 3, 8), ("icon-maskable-512.png", 512, "#ffffff", 76, 0)]
for name, size, bg, pad, rad in jobs:
    render_html(icon_html(size, bg, pad, rad), f"{B}/{name}", size, size, omit_bg=True)
Image.open(f"{B}/favicon-48.png").save(f"{B}/favicon.ico", format="ICO", sizes=[(16, 16), (32, 32), (48, 48)])
for f in ("favicon-48.png",):
    os.remove(f"{B}/{f}")
print(sorted(os.listdir(B)))
