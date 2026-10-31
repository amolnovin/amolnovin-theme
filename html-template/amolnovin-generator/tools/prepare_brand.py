"""Prepare brand assets: font subset, logo (webp + traced SVG mark), favicons."""
import os, sys, json
import numpy as np
from PIL import Image
import potrace
from fontTools import subset
from fontTools.ttLib import TTFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
RES = f"{ROOT}/_assetsrc"
OUT_IMG = f"{ROOT}/static/img/brand"
os.makedirs(OUT_IMG, exist_ok=True)

# ---------- 1) font subset ----------
src = f"{ROOT}/_assetsrc/fonts/Vazirmatn[wght].woff2"
font = TTFont(src)
uni = set(range(0x20, 0x7F)) | {0xA0, 0xAB, 0xBB, 0xD7, 0xF7, 0xB7, 0xA9, 0xAE, 0xB0, 0xB1}
uni |= set(range(0x600, 0x700))
uni |= {0x200B, 0x200C, 0x200D, 0x200E, 0x200F, 0x2010, 0x2011, 0x2012, 0x2013, 0x2014, 0x2018, 0x2019,
        0x201C, 0x201D, 0x2022, 0x2026, 0x202A, 0x202B, 0x202C, 0x202D, 0x202E, 0x2039, 0x203A, 0x2212, 0x2044,
        0x2060, 0x2066, 0x2067, 0x2068, 0x2069, 0x25CF}
opts = subset.Options()
opts.layout_features = ["*"]
opts.flavor = "woff2"
opts.hinting = False
opts.notdef_outline = True
opts.name_IDs = [1, 2, 3, 4, 6]
opts.drop_tables += ["DSIG"]
sub = subset.Subsetter(opts)
sub.populate(unicodes=uni)
sub.subset(font)
font.flavor = "woff2"
font.save(f"{ROOT}/static/fonts/Vazirmatn-subset.woff2")
print("font bytes", os.path.getsize(f"{ROOT}/static/fonts/Vazirmatn-subset.woff2"))

# ---------- 2) logo ----------
logo = Image.open(f"{RES}/logo_weserv.png").convert("RGBA")
logo.save(f"{OUT_IMG}/avn_new_logo-512-512.webp", "WEBP", quality=92, method=6)
print("logo webp", os.path.getsize(f"{OUT_IMG}/avn_new_logo-512-512.webp"))

# ---------- 3) trace the mark ----------
crop = logo.crop((0, 92, 512, 356))          # mark only (no text)
S = 3
big = crop.resize((crop.width * S, crop.height * S), Image.LANCZOS)
a = np.asarray(big).astype(np.float32)
rgb, alpha = a[..., :3], a[..., 3]
navy = np.array([0x27, 0x28, 0x69], np.float32)
teal = np.array([0x13, 0xA8, 0x9E], np.float32)
white = np.array([255, 255, 255], np.float32)
def dist(c): return np.linalg.norm(rgb - c, axis=-1)
dn, dt, dw = dist(navy), dist(teal), dist(white)
bg = np.array([0xFC, 0xFF, 0xFA], np.float32)
dw = np.minimum(dw, np.linalg.norm(rgb - bg, axis=-1))
navy_mask = (dn < dw * 0.95) & (dn < dt) & (alpha > 128)
teal_mask = (dt < dw * 0.95) & (dt < dn) & (alpha > 128)
from PIL import ImageFilter
from scipy import ndimage as ndi
def closing(m, k):
    im = Image.fromarray((m * 255).astype(np.uint8))
    im = im.filter(ImageFilter.MaxFilter(k)).filter(ImageFilter.MinFilter(k))
    return np.asarray(im) > 127
# keep only the biggest teal component (the arc); tiny teal bits are circuit lines -> belong to the navy shape
lab, n = ndi.label(teal_mask)
sizes = ndi.sum(teal_mask, lab, range(1, n + 1))
keep = 1 + int(np.argmax(sizes))
arc = lab == keep
circuit = teal_mask & ~arc
teal_mask = closing(arc, 7)
navy_mask = closing(navy_mask | circuit, 13)
navy_mask = ndi.binary_fill_holes(navy_mask | teal_mask) & ~teal_mask
# drop tiny navy specks
lab, n = ndi.label(navy_mask)
sizes = ndi.sum(navy_mask, lab, range(1, n + 1))
navy_mask = np.isin(lab, [i + 1 for i, sz in enumerate(sizes) if sz > 600])

def trace(mask, tol=0.25):
    bm = potrace.Bitmap(~mask)
    plist = bm.trace(turdsize=60, turnpolicy=potrace.POTRACE_TURNPOLICY_MINORITY, alphamax=1.0, opticurve=True, opttolerance=0.6)
    parts = []
    f = lambda p: f"{p.x / S:.0f} {p.y / S:.0f}"
    for curve in plist:
        parts.append("M" + f(curve.start_point))
        for seg in curve.segments:
            if seg.is_corner:
                parts.append("L" + f(seg.c) + "L" + f(seg.end_point))
            else:
                parts.append("C" + f(seg.c1) + " " + f(seg.c2) + " " + f(seg.end_point))
        parts.append("Z")
    return "".join(parts)

d_navy, d_teal = trace(navy_mask), trace(teal_mask)
# tight bbox from masks
ys, xs = np.where(navy_mask | teal_mask)
x0, x1, y0, y1 = xs.min() / S, xs.max() / S, ys.min() / S, ys.max() / S
pad = 2
vb = f"{x0 - pad:.1f} {y0 - pad:.1f} {x1 - x0 + 2 * pad:.1f} {y1 - y0 + 2 * pad:.1f}"
svg = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" role="img" aria-label="لوگوی آمل نوین">'
       f'<path fill="#272869" fill-rule="evenodd" d="{d_navy}"/><path fill="#13A89E" fill-rule="evenodd" d="{d_teal}"/></svg>')
open(f"{OUT_IMG}/logo-mark.svg", "w").write(svg)
# light variant for dark backgrounds (navy -> white-ish indigo tint)
svg_light = svg.replace("#272869", "#E8E9FF")
open(f"{OUT_IMG}/logo-mark-light.svg", "w").write(svg_light)
json.dump({"viewBox": vb, "d_navy": d_navy, "d_teal": d_teal}, open(f"{ROOT}/data/_logo_paths.json", "w"))
print("mark svg bytes", len(svg), "viewBox", vb)
