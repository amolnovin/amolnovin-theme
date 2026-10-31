# -*- coding: utf-8 -*-
"""End-to-end test of the WordPress admin integration (structured data editor, SEO box, menu fields)."""
import os, subprocess, sys, json, urllib.request
from playwright.sync_api import sync_playwright

WP = os.environ.get("QA_BASE", "http://localhost:8081")
SITE_DIR = os.environ.get("QA_SITE_DIR", ".")
ADMIN_USER = os.environ.get("QA_ADMIN_USER", "admin")
ADMIN_PASS = os.environ.get("QA_ADMIN_PASS") or sys.exit("set QA_ADMIN_PASS (the admin password of the test site, e.g. printed by setup_lab.sh)")
SHOTS = os.environ.get("QA_SHOTS", "qa-output/wp-admin")
os.makedirs(SHOTS, exist_ok=True)
res = []
def ok(name, cond, extra=""):
    res.append(bool(cond)); print(("PASS " if cond else "FAIL ") + name, extra)
def wpcli(*a):
    return subprocess.run(["wp", *a, "--allow-root"], capture_output=True, text=True, cwd=SITE_DIR).stdout.strip()
def page_html(path):
    return urllib.request.urlopen(WP + path).read().decode("utf-8")

pid = wpcli("post", "list", "--post_type=page", "--name=seo", "--field=ID")
ok("page 'seo' exists", pid.isdigit(), f"(id {pid})")

with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={"width": 1440, "height": 1000}, locale="fa-IR")
    pg = ctx.new_page(); errs = []
    pg.on("pageerror", lambda e: errs.append(str(e)))
    pg.goto(WP + "/wp-login.php"); pg.fill("#user_login", ADMIN_USER); pg.fill("#user_pass", ADMIN_PASS); pg.click("#wp-submit")
    pg.wait_for_url("**/wp-admin/**")
    ok("login", "/wp-admin/" in pg.url)

    pg.goto(f"{WP}/wp-admin/post.php?post={pid}&action=edit", wait_until="networkidle")
    pg.evaluate("""()=>{try{const d=wp.data.dispatch('core/preferences');d.set('core/edit-post','welcomeGuide',false);d.set('core/edit-post','fullscreenMode',false);
        d.set('core/edit-post','metaBoxesMainIsOpen',true);d.set('core/edit-post','metaBoxesMainOpenHeight',620);}catch(e){}}""")
    pg.keyboard.press("Escape")
    pg.wait_for_selector(".amol-ed__ui .amol-obj", state="attached", timeout=20000)
    ok("structured editor rendered (page definition)", pg.locator(".amol-ed").count() >= 1, f"({pg.locator('.amol-ed__ui input[type=text], .amol-ed__ui textarea').count()} fields)")
    ok("SEO box rendered", pg.locator("#amol-seo").count() == 1)
    ok("no JS errors on the edit screen", not [e for e in errs if "amol" in e.lower()], str(errs[:2]))
    pg.screenshot(path=f"{SHOTS}/page-editor.png", full_page=False)

    # --- edit a field of the first "cards" section (open its card) and change the title
    pg.evaluate("document.querySelectorAll('.amol-ed .amol-card')[0].open = true")
    pg.evaluate("""()=>{const ed=document.querySelector('.amol-ed[data-key=_amol_page]');
        const cards=[...ed.querySelectorAll('.amol-card')]; const sec=cards.find(c=>/\\[کارت‌ها\\]/.test(c.querySelector('summary').textContent)); if(sec){sec.open=true;} }""")
    # first text input whose label is «عنوان» inside the first cards section
    new_title = "عنوان آزمایشی ویرایشگر ✓"
    changed = pg.evaluate("""(t)=>{const ed=document.querySelector('.amol-ed[data-key=_amol_page]');
        const cards=[...ed.querySelectorAll(':scope .amol-arr__list > .amol-row > .amol-card')];
        const sec=cards.find(c=>/\\[کارت‌ها\\]/.test(c.querySelector('summary').textContent)); if(!sec) return false;
        const f=[...sec.querySelectorAll(':scope > .amol-obj > .amol-field')].find(x=>x.querySelector('.amol-label').textContent.trim()==='عنوان'); if(!f) return false;
        const i=f.querySelector('input,textarea'); i.value=t; i.dispatchEvent(new Event('input',{bubbles:true})); return true;}""", new_title)
    ok("changed the title of a cards section", changed)

    # --- add a callout section from the «add section» menu
    pg.evaluate("""()=>{const sel=document.querySelector('.amol-ed[data-key=_amol_page] select.amol-kind-select'); sel.value='callout'; sel.dispatchEvent(new Event('change',{bubbles:true}));}""")
    pg.wait_for_timeout(300)
    added = pg.evaluate("""()=>{const ed=document.querySelector('.amol-ed[data-key=_amol_page]');
        const secField=[...ed.querySelectorAll(':scope > .amol-ed__ui > .amol-obj > .amol-field')].find(f=>{const l=f.querySelector(':scope > .amol-label'); return l && l.textContent.trim()==='بخش‌های صفحه';});
        if(!secField) return false;
        const rows=secField.querySelectorAll(':scope > .amol-arr > .amol-arr__list > .amol-row'); const c=rows[rows.length-1].querySelector(':scope > .amol-card');
        const f=[...c.querySelectorAll(':scope > .amol-obj > .amol-field')].find(x=>{const l=x.querySelector(':scope > .amol-label'); return l && l.textContent.trim()==='متن';});
        if(!f) return false; const i=f.querySelector('textarea,input'); i.value='بخش افزوده‌شده از ویرایشگر ساختاری'; i.dispatchEvent(new Event('input',{bubbles:true})); return true;}""")
    ok("added a callout section with text", added)
    pg.screenshot(path=f"{SHOTS}/page-editor-edited.png", full_page=False)

    # --- Update
    pg.click(".editor-post-publish-button")
    pg.wait_for_function("() => !wp.data.select('core/editor').isSavingPost() && !wp.data.select('core/editor').isEditedPostDirty()", timeout=30000)
    pg.wait_for_timeout(1500)
    html = page_html("/services/seo/")
    ok("edited title visible on the front-end", new_title in html)
    ok("added section visible on the front-end", "بخش افزوده‌شده از ویرایشگر ساختاری" in html)
    ok("page stays valid (no PHP error text)", "Fatal error" not in html and "Warning:" not in html)
    b.close()

# restore the original content
print(wpcli("amol", "import"))
html = page_html("/services/seo/")
ok("content restored by the importer", "عنوان آزمایشی ویرایشگر" not in html)
print(f"\n{sum(res)}/{len(res)} passed")
sys.exit(0 if all(res) else 1)
