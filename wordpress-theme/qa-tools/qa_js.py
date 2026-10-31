import sys
from playwright.sync_api import sync_playwright
import os
BASE = os.environ.get("QA_BASE", "http://localhost:8080")
res = []
def ok(name, cond, extra=""):
    res.append((name, bool(cond), extra)); print(("PASS " if cond else "FAIL ") + name, extra)
with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={"width": 1440, "height": 900}, locale="fa-IR")
    pg = ctx.new_page(); errs = []
    pg.on("pageerror", lambda e: errs.append(str(e)))
    pg.on("console", lambda m: errs.append(m.text) if m.type == "error" else None)
    pg.goto(BASE + "/", wait_until="networkidle")
    # mega menu hover
    pg.hover("button[aria-controls=mega-services]"); pg.wait_for_timeout(500)
    ok("mega services opens on hover", pg.evaluate("getComputedStyle(document.querySelector('#mega-services')).visibility") == "visible")
    pg.mouse.move(700, 600); pg.wait_for_timeout(500)
    ok("mega closes on leave", pg.evaluate("getComputedStyle(document.querySelector('#mega-services')).visibility") == "hidden")
    pg.click("button[aria-controls=mega-types]"); pg.wait_for_timeout(400)
    ok("mega types opens on click", pg.evaluate("document.querySelector('#mega-types').parentElement.dataset.open") == "true")
    pg.keyboard.press("Escape"); pg.wait_for_timeout(300)
    # theme
    pg.click("[data-theme-toggle]"); ok("theme toggles to dark", pg.evaluate("document.documentElement.dataset.theme") == "dark")
    pg.click("[data-theme-toggle]")
    # search palette
    pg.keyboard.press("Control+k"); pg.wait_for_timeout(400)
    ok("search opens (Ctrl+K)", pg.evaluate("document.querySelector('#search-modal').classList.contains('is-open')"))
    pg.fill("#search-input", "قیمت"); pg.wait_for_timeout(300)
    n = pg.evaluate("document.querySelectorAll('.search-item').length"); ok("search returns results for «قیمت»", n > 0, f"({n})")
    pg.fill("#search-input", "پزشکی"); pg.wait_for_timeout(300)
    first = pg.evaluate("(document.querySelector('.search-item strong')||{}).textContent"); ok("search «پزشکی» -> medical page", first and "پزشکی" in first, first)
    pg.keyboard.press("Escape")
    # quick form validation + fallback
    pg.fill("#f-quick-name", "x"); pg.fill("#f-quick-phone", "123"); pg.click("#f-quick button[type=submit]"); pg.wait_for_timeout(300)
    ok("form validation errors", pg.evaluate("document.querySelectorAll('#f-quick [aria-invalid=true]').length") == 2)
    pg.fill("#f-quick-name", "علی رضایی"); pg.fill("#f-quick-phone", "۰۹۱۲۳۴۵۶۷۸۹"); pg.click("#f-quick button[type=submit]"); pg.wait_for_timeout(1200)
    st = pg.evaluate("document.querySelector('#f-quick .form-status').className"); ok("form submit shows status (fallback/ok)", "is-warn" in st or "is-ok" in st, st)
    # rotator + counters
    pg.evaluate("window.scrollTo(0, 0)"); pg.wait_for_timeout(3000)
    ok("rotator changes text", pg.evaluate("document.querySelector('.rotator').textContent") != "سایت شرکتی")
    # chooser
    pg.goto(BASE + "/services/web-design/", wait_until="networkidle")
    pg.select_option("#chooser-sel", "1"); pg.wait_for_timeout(300)
    ok("chooser shows recommendation", pg.evaluate("document.querySelector('.chooser__out').classList.contains('is-show')") and "فروشگاهی" in pg.evaluate("document.querySelector('.chooser__out').textContent"))
    # portfolio filters
    pg.goto(BASE + "/portfolio/", wait_until="networkidle")
    total = pg.evaluate("document.querySelectorAll('.projects .project:not([hidden])').length")
    pg.click("[data-filter=store]"); pg.wait_for_timeout(200)
    st = pg.evaluate("document.querySelectorAll('.projects .project:not([hidden])').length"); ok("portfolio filter store", 0 < st < total, f"({st}/{total})")
    pg.click("[data-filter=all]"); pg.fill("[data-filter-search]", "مانلی"); pg.wait_for_timeout(200)
    ok("portfolio search", pg.evaluate("document.querySelectorAll('.projects .project:not([hidden])').length") == 1)
    pg.fill("[data-filter-search]", "zzzzqq"); pg.wait_for_timeout(200)
    ok("portfolio empty state", pg.evaluate("document.querySelector('.empty-state').classList.contains('is-show')"))
    # calculator
    pg.goto(BASE + "/price-wordpress/", wait_until="networkidle")
    t0 = pg.evaluate("document.querySelector('[data-calc-total]').textContent")
    pg.click("input[name=ctype][value='3']", force=True); pg.wait_for_timeout(200)
    t1 = pg.evaluate("document.querySelector('[data-calc-total]').textContent")
    pg.click("input[name=opt][value='2']", force=True); pg.wait_for_timeout(200)
    t2 = pg.evaluate("document.querySelector('[data-calc-total]').textContent")
    ok("calculator updates", t0 != t1 and t1 != t2, f"{t0.strip()[:12]} | {t1.strip()[:12]} | {t2.strip()[:12]}")
    # blog filter + toc
    pg.goto(BASE + "/blog-n/", wait_until="networkidle")
    pg.click("[data-filter='سئو']"); pg.wait_for_timeout(200)
    ok("blog filter", pg.evaluate("document.querySelectorAll('.posts .post-card:not([hidden])').length") == 1)
    pg.goto(BASE + "/website-design-cost-1405/", wait_until="networkidle")
    ok("TOC generated", pg.evaluate("document.querySelectorAll('[data-toc] a').length") >= 4)
    # mobile drawer
    m = b.new_context(viewport={"width": 390, "height": 800}, is_mobile=True, has_touch=True, locale="fa-IR"); mp = m.new_page()
    mp.goto(BASE + "/", wait_until="networkidle")
    mp.click("[data-menu-open]"); mp.wait_for_timeout(500)
    ok("mobile drawer opens", mp.evaluate("document.querySelector('#drawer').classList.contains('is-open')"))
    ok("no horizontal overflow (mobile)", mp.evaluate("document.documentElement.scrollWidth <= window.innerWidth + 1"), str(mp.evaluate("document.documentElement.scrollWidth")))
    print("console errors:", errs[:5])
    b.close()
print(f"\n{sum(1 for r in res if r[1])}/{len(res)} passed")
