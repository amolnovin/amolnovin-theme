"""Header regression check: "بیشتر" dropdown position (desktop) and header__actions side (mobile).

    python3 tools/menu_check.py [--base http://localhost:8080] [--shots qa-output/menu] [--page /portfolio/]

Desktop  : hover the «بیشتر» button -> the panel must open directly under that button
           (button's start edge == panel's start edge, panel inside the viewport, same top as other mega menus).
Mobile   : .header__actions must sit on the LEFT edge of the header (RTL end), brand on the right.
Exit code 1 when any check fails.
"""
import argparse, os, sys
from playwright.sync_api import sync_playwright

ap = argparse.ArgumentParser()
ap.add_argument("--base", default="http://localhost:8080")
ap.add_argument("--page", default="/portfolio/")
ap.add_argument("--shots", default="qa-output/menu")
ap.add_argument("--tag", default="")
a = ap.parse_args()
os.makedirs(a.shots, exist_ok=True)
fails = []


def check(ok, msg):
    print(("  ok   " if ok else "  FAIL ") + msg)
    if not ok:
        fails.append(msg)


RECT = """(sel)=>{const e=document.querySelector(sel); if(!e) return null; const r=e.getBoundingClientRect(); return {l:r.left,r:r.right,t:r.top,b:r.bottom,w:r.width,h:r.height}}"""

with sync_playwright() as p:
    b = p.chromium.launch()

    # ------------------------------------------------------------------ desktop
    for W in (1240, 1280, 1341, 1366, 1440, 1920):
        ctx = b.new_context(viewport={"width": W, "height": 609 if W == 1341 else 800}, locale="fa-IR")
        pg = ctx.new_page()
        pg.goto(a.base + a.page, wait_until="networkidle")
        print(f"\n[desktop {W}px]")
        btn = pg.locator("#mega-more").locator("xpath=preceding-sibling::button")
        btn.hover()
        pg.wait_for_timeout(600)
        bt = pg.evaluate(RECT, "[aria-controls=mega-more]")
        pn = pg.evaluate(RECT, "#mega-more .mega__panel")
        hd = pg.evaluate(RECT, ".site-header")
        vw = pg.evaluate("document.documentElement.clientWidth")
        vis = pg.evaluate("getComputedStyle(document.querySelector('#mega-more')).visibility")
        check(vis == "visible", "panel becomes visible on hover")
        # RTL: start edge == right edge
        check(abs(pn["r"] - bt["r"]) <= 1.5 or abs((pn["l"] + pn["r"]) / 2 - (bt["l"] + bt["r"]) / 2) <= 1.5,
              f"panel is aligned with the button (button {bt['l']:.0f}-{bt['r']:.0f}, panel {pn['l']:.0f}-{pn['r']:.0f})")
        check(pn["l"] <= bt["l"] + 1.5 and pn["r"] >= bt["r"] - 1.5, "button is horizontally inside the panel span")
        check(pn["l"] >= 0 and pn["r"] <= vw, f"panel is inside the viewport (0-{vw})")
        check(abs(pn["t"] - (hd["b"] + 10)) <= 2.5, f"panel top ({pn['t']:.0f}) = header bottom ({hd['b']:.0f}) + 10px gap")
        # pointer can travel button -> panel without closing
        pg.mouse.move((bt["l"] + bt["r"]) / 2, bt["b"] + 4)
        pg.mouse.move((pn["l"] + pn["r"]) / 2, pn["t"] + 40, steps=8)
        pg.wait_for_timeout(450)
        check(pg.evaluate("document.querySelector('#mega-more').parentElement.dataset.open") == "true", "stays open while moving the pointer into the panel")
        if W in (1341, 1440):
            pg.screenshot(path=f"{a.shots}/{a.tag}desktop-{W}-more.png", clip={"x": 0, "y": 0, "width": W, "height": 609 if W == 1341 else 560})
        # leave -> closes
        pg.mouse.move(W / 2, 700, steps=4)
        pg.wait_for_timeout(600)
        check(pg.evaluate("document.querySelector('#mega-more').parentElement.dataset.open") is None, "closes after the pointer leaves")
        # the other two mega menus are untouched (full container width, under the header)
        for mid in ("mega-services", "mega-types"):
            pg.locator(f"[aria-controls={mid}]").hover()
            pg.wait_for_timeout(450)
            m = pg.evaluate(RECT, f"#{mid} .mega__panel")
            c = pg.evaluate(RECT, ".header__in")
            check(m is not None and m["w"] > 700 and abs(m["t"] - (hd["b"] + 10)) <= 2.5, f"{mid} still full-width mega panel ({m['w']:.0f}px)")
            pg.mouse.move(W / 2, 700, steps=3); pg.wait_for_timeout(500)
        # header must not wrap / overflow horizontally
        check(pg.evaluate("document.documentElement.scrollWidth <= document.documentElement.clientWidth"), "no horizontal scroll")
        check(pg.evaluate("document.querySelector('.site-header').getBoundingClientRect().height") < 90, "header height unchanged (<90px)")
        ctx.close()

    # ------------------------------------------------------------------ keyboard + touch
    ctx = b.new_context(viewport={"width": 1280, "height": 800}, locale="fa-IR")
    pg = ctx.new_page(); pg.goto(a.base + a.page, wait_until="networkidle")
    print("\n[keyboard]")
    pg.focus("[aria-controls=mega-more]"); pg.keyboard.press("Enter"); pg.wait_for_timeout(400)
    check(pg.evaluate("document.querySelector('#mega-more').parentElement.dataset.open") == "true", "Enter on the button opens the dropdown")
    pg.keyboard.press("Escape"); pg.wait_for_timeout(300)
    check(pg.evaluate("document.querySelector('#mega-more').parentElement.dataset.open") is None, "Escape closes it")
    ctx.close()
    ctx = b.new_context(viewport={"width": 1280, "height": 800}, has_touch=True, is_mobile=False, locale="fa-IR")
    pg = ctx.new_page(); pg.goto(a.base + a.page, wait_until="networkidle")
    print("\n[tablet landscape, touch (hover:none emulated by click)]")
    pg.click("[aria-controls=mega-more]"); pg.wait_for_timeout(400)
    check(pg.evaluate("document.querySelector('#mega-more').parentElement.dataset.open") == "true", "click opens the dropdown")
    pn = pg.evaluate(RECT, "#mega-more .mega__panel"); bt = pg.evaluate(RECT, "[aria-controls=mega-more]")
    check(abs(pn["r"] - bt["r"]) <= 1.5 or abs((pn["l"] + pn["r"]) / 2 - (bt["l"] + bt["r"]) / 2) <= 1.5, "panel aligned with the button after click as well")
    ctx.close()

    # ------------------------------------------------------------------ mobile / tablet (burger mode)
    for W in (360, 390, 430, 768, 1020, 1024, 1100, 1200, 1235):
        ctx = b.new_context(viewport={"width": W, "height": 800}, device_scale_factor=2 if W < 500 else 1, is_mobile=W < 500, has_touch=W < 500, locale="fa-IR")
        pg = ctx.new_page(); pg.goto(a.base + a.page, wait_until="networkidle")
        print(f"\n[mobile {W}px]")
        ac = pg.evaluate(RECT, ".header__actions")
        br = pg.evaluate(RECT, ".site-header .brand")
        ct = pg.evaluate(RECT, ".header__in")
        bg = pg.evaluate(RECT, ".burger")
        cs = pg.evaluate("(()=>{const s=getComputedStyle(document.querySelector('.header__in'));return [parseFloat(s.paddingLeft),parseFloat(s.paddingRight)]})()")
        left_edge = ct["l"] + cs[0]; right_edge = ct["r"] - cs[1]
        check(abs(ac["l"] - left_edge) <= 1.5, f"header__actions flush to the LEFT edge (actions.left={ac['l']:.1f}, container content left={left_edge:.1f})")
        check(abs(br["r"] - right_edge) <= 1.5, f"brand flush to the RIGHT edge (brand.right={br['r']:.1f}, content right={right_edge:.1f})")
        check(ac["l"] > br["r"] - 1 or ac["r"] < br["l"] + 1, "actions and brand do not overlap")
        check(bg["l"] <= ac["l"] + 1.5, "burger is the left-most action")
        check(pg.evaluate("document.documentElement.scrollWidth <= document.documentElement.clientWidth"), "no horizontal scroll")
        if W in (390, 768):
            pg.screenshot(path=f"{a.shots}/{a.tag}mobile-{W}-header.png", clip={"x": 0, "y": 0, "width": W, "height": 140})
        # drawer still opens with the moved burger
        if W == 390:
            pg.click(".burger"); pg.wait_for_timeout(600)
            check(pg.evaluate("document.querySelector('#drawer').classList.contains('is-open')"), "drawer opens from the moved burger")
            pg.screenshot(path=f"{a.shots}/{a.tag}mobile-390-drawer.png")
            pg.keyboard.press("Escape"); pg.wait_for_timeout(500)
        ctx.close()
    b.close()

print("\n" + ("ALL CHECKS PASSED" if not fails else f"{len(fails)} CHECK(S) FAILED"))
sys.exit(1 if fails else 0)
