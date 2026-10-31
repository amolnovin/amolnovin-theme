import os
from PIL import Image
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = f"{ROOT}/_assetsrc/img"
OUT = f"{ROOT}/static/img"

def load(n): return Image.open(f"{SRC}/{n}.webp").convert("RGB")

def cover(im, w, h, anchor="top"):
    """scale to cover (w,h); crop with vertical anchor (top|center)."""
    r = max(w / im.width, h / im.height)
    nw, nh = round(im.width * r), round(im.height * r)
    im = im.resize((nw, nh), Image.LANCZOS)
    x = (nw - w) // 2
    y = 0 if anchor == "top" else (nh - h) // 2
    return im.crop((x, y, x + w, y + h))

def save(im, path, q=80):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    im.save(path, "WEBP", quality=q, method=6)
    return os.path.getsize(path)

portfolio = {
 "p-dhd": "dhd-rental-furniture", "p-beryon": "beryon-meat-company", "p-sayana": "sayana-camp-store",
 "p-maneli": "maneli-plants-store", "p-rang-sadeghi": "rang-sadeghi-paint-store", "p-shekar-shekan": "shekar-shekan-food-industries",
 "p-parastar": "parastar-tents-store", "p-mba": "mba-amol-machinery", "p-navid-melk": "navid-melk-real-estate",
 "p-vilasere": "vilasere-villa-rental", "p-choobin-decor": "choobin-decor", "p-heydari": "heydari-cable-store",
 "p-armoon": "armoon-publications", "p-faramobil": "faramobil-auto-parts", "p-ebrahim-pahlavan": "ebrahim-pahlavan-personal-brand",
 "p-dorita": "dorita-company", "p-arian-panjareh": "arian-panjareh-windows", "p-nopars": "nopars-steel-industry",
 "p-tarahan": "tarahan-pishro-studio", "p-hejazi": "hejazi-construction",
}
tot = 0
for k, name in portfolio.items():
    im = load(k)
    anchor = "center" if im.width / im.height > 1.9 else "top"
    tot += save(cover(im, 960, 600, anchor), f"{OUT}/portfolio/{name}.webp", 78)          # card
    tot += save(cover(im, 1100, 720, anchor), f"{OUT}/portfolio/{name}-lg.webp", 80)      # case-study hero
# long full-page screenshot (scroll preview)
im = load("p-sayana-desktop"); im = im.resize((800, round(im.height * 800 / im.width)), Image.LANCZOS)
im = im.crop((0, 0, 800, min(im.height, 3000)))
tot += save(im, f"{OUT}/portfolio/sayana-camp-store-fullpage.webp", 68)

store = {"s-webp": "webp-converter", "s-security": "security-suite", "s-notifier": "bei-notifier", "s-gold": "gold-pricing",
         "s-filter": "product-filter", "s-license": "license-manager", "s-plant": "plant-selector", "s-gym": "gym-pro",
         "s-checkout": "checkout-customizer", "s-wallet": "wallet", "s-auth": "login-register-otp"}
for k, name in store.items():
    im = load(k)
    if abs(im.width / im.height - 16 / 9) > 0.2:   # square cover -> pad with its own edge colour
        edge = im.getpixel((5, 5)); canvas = Image.new("RGB", (round(im.height * 16 / 9), im.height), edge)
        canvas.paste(im, ((canvas.width - im.width) // 2, 0)); im = canvas
    tot += save(cover(im, 800, 450, "center"), f"{OUT}/store/{name}.webp", 80)

blog = {"b-support": "wordpress-support-guide", "b-aiseo": "ai-seo-geo-guide", "b-bestamol": "best-web-design-company-amol",
        "b-woo": "woocommerce-store-guide", "b-wpwhy": "why-wordpress", "b-bizlocal": "business-website-guide",
        "b-domain": "domain-guide", "b-litespeed": "litespeed-cache-guide", "b-cpanel": "wordpress-cpanel-install"}
for k, name in blog.items():
    tot += save(cover(load(k), 900, 506, "center"), f"{OUT}/blog/{name}.webp", 78)

iman = load("x-iman").resize((480, 480), Image.LANCZOS)
tot += save(iman, f"{OUT}/team/iman-hosseinzadeh.webp", 82)
lic = load("x-parvaneh"); lic = lic.resize((1000, round(lic.height * 1000 / lic.width)), Image.LANCZOS)
tot += save(lic, f"{OUT}/team/business-license.webp", 82)
print("total KB", tot // 1024)
