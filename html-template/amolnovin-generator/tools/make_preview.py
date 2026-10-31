"""Create a single self-contained HTML (all CSS/JS/fonts/images inlined) of a built page for quick previews.
   python3 tools/make_preview.py [/path/in/site/] [out.html]"""
import re, os, sys, base64, mimetypes
SITE = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "amolnovin-theme"))
path = sys.argv[1] if len(sys.argv) > 1 else "/"
out = sys.argv[2] if len(sys.argv) > 2 else os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "preview", "home-preview.html"))
os.makedirs(os.path.dirname(out), exist_ok=True)
pdir = os.path.join(SITE, path.strip("/"))
html = open(os.path.join(pdir, "index.html"), encoding="utf-8").read()
MT = {".webp": "image/webp", ".svg": "image/svg+xml", ".png": "image/png", ".jpg": "image/jpeg", ".woff2": "font/woff2", ".ico": "image/x-icon"}
def datauri(fp):
    ext = os.path.splitext(fp)[1].lower()
    return f"data:{MT.get(ext, 'application/octet-stream')};base64," + base64.b64encode(open(fp, "rb").read()).decode()
def resolve(base, rel): return os.path.normpath(os.path.join(base, rel.split("?")[0].split("#")[0]))
# css
def css_inline(m):
    href = m.group(1); fp = resolve(pdir, href); css = open(fp, encoding="utf-8").read(); cdir = os.path.dirname(fp)
    css = re.sub(r"url\((['\"]?)(?!data:|https?:|#)([^)'\"]+)\1\)", lambda u: f"url({datauri(resolve(cdir, u.group(2)))})", css)
    return "<style>" + css + "</style>"
html = re.sub(r'<link rel="stylesheet" href="([^"]+)">', css_inline, html)
# js
def js_inline(m):
    fp = resolve(pdir, m.group(1)); js = open(fp, encoding="utf-8").read().replace("</script", "<\\/script")
    return "<script>" + js + "</script>"
html = re.sub(r'<script src="([^"]+)" defer></script>', js_inline, html)
# images + favicon
html = re.sub(r'(<img[^>]+src=")(?!data:|https?:)([^"]+)(")', lambda m: m.group(1) + datauri(resolve(pdir, m.group(2))) + m.group(3), html)
html = re.sub(r'<link rel="(?:icon|apple-touch-icon)"[^>]*>', "", html)
html = re.sub(r'<link rel="(?:preload|manifest|alternate|sitemap)"[^>]*>', "", html)
# neutralise page-to-page links (not present in the single-file preview)
html = re.sub(r'href="(?!https?:|mailto:|tel:|#)[^"]*"', 'href="#"', html)
html = html.replace('data-root="./"', 'data-root="#"')
open(out, "w", encoding="utf-8").write(html)
print(out, round(os.path.getsize(out) / 1024), "KB")
