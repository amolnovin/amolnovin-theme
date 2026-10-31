import os, re, json
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
NM = f"{ROOT}/_assetsrc/node/node_modules"   # npm i lucide-static simple-icons (inside _assetsrc/node)
LUC = f"{NM}/lucide-static/icons"
SI = f"{NM}/simple-icons/icons"
lucide = """menu x chevron-down chevron-left chevron-right chevron-up arrow-left arrow-right arrow-up arrow-up-left arrow-down external-link search sun moon phone phone-call mail map-pin clock calendar calendar-check calendar-days message-circle message-square send check circle-check plus minus star heart share-2 copy link layout-grid list eye download play quote info triangle-alert shield-check shield lock key-round zap rocket gauge trending-up chart-column chart-line chart-pie target users user user-check briefcase building-2 store shopping-cart shopping-bag credit-card wallet package truck graduation-cap book-open school stethoscope heart-pulse scale gavel house building utensils coffee bed-double plane map compass newspaper rss dumbbell factory wrench hammer settings landmark hand-heart scissors sparkles car ruler pencil-ruler palette layout-template megaphone wheat leaf sprout camera code-xml terminal database server cloud globe languages smartphone monitor laptop cpu network wifi hard-drive bot brain wand-sparkles lightbulb puzzle layers file-text file-check clipboard-check handshake award badge-check medal thumbs-up headset life-buoy refresh-cw history scan-search bug shield-alert activity badge-percent tag percent calculator receipt hand-coins banknote image images video package-check folder-open git-branch braces file-code mouse-pointer-click panel-top app-window layout-dashboard box boxes vault fingerprint qr-code bell bell-ring timer flag route mountain tent trees waves tree-palm hotel moon-star book-marked library notebook-pen pen-tool pencil square-pen contact id-card circle-help circle-x circle-alert bookmark log-in user-plus trophy flame crown gem gift ticket scroll-text file-signature stamp server-cog cog wrench rotate-ccw hourglass cloud-upload database-backup scan-line key mail-check at-sign list-checks list-ordered align-right text-cursor-input form-input square-check-big badge-dollar-sign coins piggy-bank blocks component binary radar telescope microscope pill syringe tooth baby sofa lamp-ceiling bath paintbrush brush-cleaning spray-can shirt gamepad-2 music mic book heart-handshake church pickaxe tractor apple milk fish beef cake cookie wine bike bus train anchor ship plane-takeoff luggage hand-platter chef-hat soup pizza hamburger calendar-clock filter siren unlock""".split()
brands = """whatsapp telegram instagram linkedin wordpress woocommerce elementor php laravel react mysql javascript html5 css googlesearchconsole googleanalytics cloudflare figma github youtube x""".split()
icons, missing = {}, []
for n in dict.fromkeys(lucide):
    p = f"{LUC}/{n}.svg"
    if not os.path.exists(p): missing.append(n); continue
    s = open(p).read()
    inner = re.sub(r"^.*?<svg[^>]*>", "", s, flags=re.S)
    inner = re.sub(r"</svg>\s*$", "", inner).strip()
    inner = re.sub(r"\s+", " ", inner)
    icons[n] = {"body": inner, "vb": "0 0 24 24", "brand": False}
for n in brands:
    p = f"{SI}/{n}.svg"
    if not os.path.exists(p): missing.append("brand:"+n); continue
    s = open(p).read()
    d = re.search(r'<path d="([^"]+)"', s).group(1)
    icons["b-"+n] = {"body": f'<path d="{d}"/>', "vb": "0 0 24 24", "brand": True}
json.dump(icons, open(f"{ROOT}/data/icons.json", "w"), ensure_ascii=False)
print(len(icons), "icons; missing:", missing)
