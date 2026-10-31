# -*- coding: utf-8 -*-
"""Page registry: content + SEO metadata for every page.  register(add, ctx) is called by build.py"""
import re
from config import SITE, BUILD_DATE
from data import common, portfolio, store
from data import pages_a, pages_b, pages_c
import schema
import util


def glass(items):
    cards = "".join(f'<div class="glass-card" style="--i:{i}"><span class="ico"><svg class="i" aria-hidden="true"><use href="#i-{ic}"/></svg></span><span><strong>{big}</strong><small>{small}</small></span></div>' for i, (ic, big, small) in enumerate(items))
    return f'<div class="glass-stack" aria-hidden="true">{cards}</div>'


def collage(imgs):
    return '<div class="collage" aria-hidden="true">' + "".join(f'<img class="collage__{c}" src="@asset:{p}" width="960" height="600" alt="">' for c, p in zip("abc", imgs)) + "</div>"


HERO_VIS = {
    "/price-wordpress/": glass([("layout-template", "طراحی سایت از ۱۷ میلیون", "تومان · شروع از"), ("life-buoy", "پشتیبانی از ۷ میلیون", "تومان در ماه"), ("chart-line", "سئو از ۱۰ میلیون", "تومان در ماه")]),
    "/about-us/": "<div style=\"display:grid;gap:1.4rem;justify-items:center\"><img class=\"logo-badge\" src=\"@asset:img/brand/avn_new_logo-512-512.webp\" width=\"150\" height=\"150\" alt=\"لوگوی فناوری اطلاعات آمل نوین\">" + glass([("calendar-days", "فعال از ۱۳۹۰", "در آمل، مازندران"), ("briefcase", "۱۵۰+ پروژه", "فروشگاه، شرکتی، صنعتی، بین‌المللی"), ("badge-check", "پروانه‌ی کسب رسمی", "اتحادیه‌ی خدمات رایانه‌ای آمل")]) + "</div>",
    "/contact-us/": glass([("phone-call", util.fa(SITE["phone_display"]), "تماس و واتساپ، ۲۴ ساعته"), ("clock", "پاسخ در کمتر از ۲ ساعت", "فرم، ایمیل و پیام‌رسان"), ("map-pin", "آمل، طالب آملی، دریای ۳۲", "جلسه‌ی حضوری با هماهنگی")]),
    "/resume/": glass([("layers", "۹ خدمت تخصصی", "از UI/UX تا سئو و امنیت"), ("cpu", "۱۳ فناوری روز", "WordPress، Laravel، React و ..."), ("layout-grid", "۲۰ نوع سایت", "هر کدام با ساختار تخصصی")]),
    "/services/computer-network/": glass([("wrench", "عیب‌یابی و رفع مشکل", "نرم‌افزار و سیستم"), ("network", "شبکه‌ی اداری", "مودم، روتر و وای‌فای"), ("hard-drive", "بکاپ و بازیابی", "نگهداری از اطلاعات")]),
}


def fmt_faq(items):
    f = dict(design=SITE["price_design"], seo=SITE["price_seo"], support=SITE["price_support"], months=SITE["support_months"])
    return [(q, a.format(**f)) for q, a in items]


def faq_from_sections(page):
    items = []
    for s in page.get("sections", []):
        if s.get("kind") == "faq":
            items += list(s["items"])
    return items


def register(add, ctx):
    TYPES, PROJECTS, POSTS, POSTS_SORTED, PRODUCTS = ctx["TYPES"], ctx["PROJECTS"], ctx["POSTS"], ctx["POSTS_SORTED"], ctx["PRODUCTS"]
    U = SITE["url"]

    # ---------------------------------------------------------------- home
    add("/", "home.html", kind="home", priority=1.0, changefreq="weekly",
        title="طراحی سایت در آمل | وردپرس، سئو و پشتیبانی سایت | آمل نوین",
        desc="فناوری اطلاعات آمل نوین؛ طراحی سایت در آمل با وردپرس و کدنویسی اختصاصی، سئو و تولید محتوا، پشتیبانی سایت و امنیت. از ۱۷ میلیون تومان با ۲ سال پشتیبانی رایگان؛ مشاوره‌ی رایگان.",
        h1="طراحی سایت در آمل؛ سایتی که دیده می‌شود و مشتری می‌آورد", kw="طراحی سایت در آمل", og="default",
        kws=["شرکت طراحی سایت در آمل", "طراحی سایت آمل", "طراحی سایت وردپرس در آمل", "پشتیبانی سایت در آمل", "سئو و تولید محتوا", "طراحی سایت اختصاصی"],
        faq=fmt_faq(common.HOME_FAQ), short_title="صفحه‌ی اصلی — آمل نوین", cat_label="صفحه")

    # ---------------------------------------------------------------- generic content pages
    for mod in (pages_a, pages_b, pages_c):
        for p in mod.pages(TYPES):
            path, tpl = p.pop("path"), p.pop("template")
            p["faq"] = faq_from_sections(p)
            if p.get("faq"):
                p["faq"] = fmt_faq_plain(p["faq"])
            if path in HERO_VIS:
                p.setdefault("hero", {})["visual"] = ("html", HERO_VIS[path])
            kind = p.pop("kind", "generic")
            if path.startswith("/services/") or path in ("/wordpress-web-design/", "/web-design-amol/", "/wordpress-learn/", "/price-wordpress/"):
                canonical = U + path
                if path not in ("/services/", "/price-wordpress/", "/wordpress-learn/"):
                    p.setdefault("ld", []).append(schema.service(re.sub(r"<[^>]+>", "", p["h1"]), p["desc"], canonical, p.get("kw", "طراحی سایت"),
                                                                 price_from={"/services/site-support/": SITE["price_support"], "/services/seo/": SITE["price_seo"]}.get(path, SITE["price_design"] if path in ("/services/web-design/", "/wordpress-web-design/", "/web-design-amol/") else None)))
            add(path, tpl, kind=kind, **p)

    # ---------------------------------------------------------------- website types
    for t in TYPES:
        path = f"/services/web-design/{t['slug']}/"
        canonical = U + path
        add(path, "type.html", kind="type", t=t, priority=0.8, og="types-" + t["slug"],
            title=t["meta_title"], desc=t["meta_desc"], h1=f"{t['title']}؛ <em>حرفه‌ای، سریع و سئو شده</em>", eyebrow="طراحی انواع سایت",
            lead=t["lead"], tldr=t["tldr"], kw=t["kw"], kws=t["kws"], short_title=t["title"], cat_label="نوع سایت",
            crumbs=[("خدمات", "/services/"), ("طراحی انواع سایت", "/services/web-design/"), (t["name"], None)],
            hero=dict(cta=[("دریافت مشاوره‌ی رایگان", "#consult", "btn--cta"), ("هزینه طراحی سایت", "/price-wordpress/", "btn--light")],
                      chips=["ریسپانسیو", "سئو پایه", "SSL", f"{SITE['support_months']} ماه پشتیبانی"], visual=("mock", t["slug"], f"نمونه‌ی ساختار {t['title']}"),
                      meta=[("clock", t["time"]), ("banknote", f"از {SITE['price_design']} میلیون تومان")]),
            faq=fmt_faq_plain(t["faq"]),
            ld=[schema.service(t["title"], t["meta_desc"], canonical, "طراحی وب‌سایت", price_from=SITE["price_design"])])

    # ---------------------------------------------------------------- portfolio + projects
    detail = [p for p in portfolio.PROJECTS if p["detail"]]
    add("/portfolio/", "portfolio.html", kind="portfolio", priority=0.9, schema_type="CollectionPage", og="portfolio",
        title="نمونه کار طراحی سایت | پروژه‌های واقعی آمل نوین (فروشگاه، شرکتی، صنعتی)",
        desc="نمونه کار طراحی سایت وردپرس و فروشگاه در آمل نوین: مانلی، کمپینگ سایانا، بریان گوشت، DHD Rental Furniture و ... با مطالعه‌ی موردی هر پروژه.",
        h1="نمونه کار <em>طراحی سایت، پشتیبانی و سئو</em>", eyebrow="نمونه‌کار", kw="نمونه کار طراحی سایت", kws=["نمونه کار طراحی سایت وردپرس", "نمونه کار طراحی فروشگاه", "نمونه کار طراحی سایت در آمل"],
        lead="پروژه‌های واقعی و فعال؛ از فروشگاه‌های آنلاین و سایت‌های صنعتی تا سایت‌های بین‌المللی. برای هر پروژه‌ی شاخص مطالعه‌ی موردی شامل چالش، راه‌حل و خروجی‌ها را بخوانید.",
        crumbs=[("نمونه‌کارها", None)], short_title="نمونه‌کارها", cat_label="نمونه‌کار",
        hero=dict(cta=[("پروژه‌ی من هم همین‌طور بشود", "/contact-us/", "btn--cta"), ("هزینه طراحی سایت", "/price-wordpress/", "btn--light")], chips=["فروشگاهی", "شرکتی", "صنعتی", "بین‌المللی", "املاک و اقامتی"],
                  visual=("html", collage(["img/portfolio/sayana-camp-store.webp", "img/portfolio/maneli-plants-store.webp", "img/portfolio/beryon-meat-company.webp"])),
                  meta=[("images", f"{len(portfolio.PROJECTS)} نمونه‌ی شاخص"), ("globe", "داخلی و بین‌المللی")]),
        faq=[("آیا نمونه‌کارها پروژه‌های واقعی هستند؟", "بله؛ همه‌ی سایت‌های این صفحه پروژه‌های واقعی کارفرمایان آمل نوین‌اند و در صورت داشتن آدرس عمومی، لینک بازدید زنده کنار آن‌ها آمده است."),
             ("آیا می‌توانم نمونه‌کار مشابه حوزه‌ی خودم را ببینم؟", "برای حوزه‌هایی که نمونه‌ی عمومی ندارند، در جلسه‌ی مشاوره نمونه‌های مرتبط یا ساختار پیشنهادی را ارائه می‌کنیم؛ همچنین صفحه‌ی هر نوع سایت ساختار آن را نشان می‌دهد."),
             ("چرا بعضی نمونه‌کارها لینک بازدید ندارند؟", "برخی کارفرمایان آدرس سایت را عمومی نکرده‌اند یا آدرس را تغییر داده‌اند؛ آمار و جزئیات بیشتر را می‌توانید در مشاوره درخواست کنید."),
             ("آیا بعد از پروژه از سایت پشتیبانی می‌کنید؟", f"بله؛ {SITE['support_months']} ماه پشتیبانی فنی رایگان و امکان پکیج پشتیبانی ماهانه. بسیاری از سایت‌های این صفحه هنوز تحت پشتیبانی ما هستند.")],
        ld=[schema.itemlist("نمونه‌کارهای آمل نوین", [(p["title"], f"{U}/projects/{p['slug']}/") for p in detail], U + "/portfolio/")])

    for i, p in enumerate(detail):
        same = [q["slug"] for q in portfolio.PROJECTS if q["cat"] == p["cat"] and q["slug"] != p["slug"] and q["detail"]]
        others = [q["slug"] for q in portfolio.PROJECTS if q["slug"] != p["slug"] and q["detail"] and q["slug"] not in same]
        rel = (same + others)[:3]
        path = f"/projects/{p['slug']}/"
        canonical = U + path
        creative = {"@type": "CreativeWork", "@id": canonical + "#project", "name": p["title"], "description": p["overview"], "inLanguage": "fa-IR",
                    "creator": {"@id": schema.ORG_ID}, "genre": "طراحی وب‌سایت", "image": f"{U}/assets/img/portfolio/{p['img']}-lg.webp",
                    "keywords": ", ".join(p["tech"]), "about": {"@type": "Thing", "name": dict(portfolio.CATS)[p["cat"]]}}
        if p.get("url"):
            creative["url"] = p["url"]
            creative["sameAs"] = [p["url"]]
        add(path, "project.html", kind="project", p=p, priority=0.7, og="projects-" + p["slug"], h1_suffix="مطالعه‌ی موردی", h1=f"{p['title']}: مطالعه‌ی موردی",
            title=f"{p.get('seo_name') or p['title']} | مطالعه‌ی موردی {dict(portfolio.CATS)[p['cat']]} | آمل نوین",
            desc=trim(f"{p.get('seo_name') or p['title']}: {p['card']} مطالعه‌ی موردی آمل نوین." + (" چالش، راه‌حل و فناوری‌های پروژه را ببینید." if len(p['card']) < 70 else "")),
            lead=p["overview"], kw=p.get("seo_name") or p["title"], kws=[f"طراحی سایت {dict(portfolio.CATS)[p['cat']]}"], short_title=p["title"], cat_label="نمونه‌کار",
            crumbs=[("نمونه‌کارها", "/portfolio/"), (p["title"], None)], prev=detail[i - 1] if i > 0 else None, next=detail[i + 1] if i + 1 < len(detail) else None, related=rel, ld=[creative])

    # ---------------------------------------------------------------- store
    add("/wplm-store/", "store.html", kind="store", priority=0.7, schema_type="CollectionPage", og="wplm-store",
        title="افزونه وردپرس و ووکامرس | فروشگاه افزونه‌های آمل نوین (WPLM)",
        desc=f"افزونه وردپرس و ووکامرس: فروشگاه {len(PRODUCTS)} افزونه‌ی اختصاصی آمل نوین، امنیت، مبدل WebP، فیلتر محصولات، کیف پول، اعلان‌رسان بله و ایتا، مدیریت باشگاه و گیاه‌یاب هوشمند.",
        h1="فروشگاه <em>افزونه وردپرس و ووکامرس</em>", eyebrow="فروشگاه افزونه (WPLM)", kw="افزونه وردپرس", kws=["افزونه ووکامرس فارسی", "افزونه امنیتی وردپرس", "افزونه کیف پول ووکامرس", "افزونه فیلتر محصولات ووکامرس"],
        lead="افزونه‌هایی که برای نیاز واقعی پروژه‌های خودمان نوشته‌ایم و حالا برای شما هم در دسترس است: سبک، سریع و با پشتیبانی مستقیم توسعه‌دهنده.",
        crumbs=[("فروشگاه افزونه‌ها", None)], short_title="فروشگاه افزونه‌ها", cat_label="فروشگاه",
        hero=dict(cta=[("مشاهده‌ی افزونه‌ها", "#products", "btn--cta"), ("سفارش افزونه‌ی اختصاصی", "/services/coding/", "btn--light")], chips=["وردپرس", "ووکامرس", "پشتیبانی توسعه‌دهنده", "بروزرسانی منظم"],
                  visual=("html", collage(["img/store/security-suite.webp", "img/store/gold-pricing.webp", "img/store/webp-converter.webp"])), meta=[("package", f"{len(PRODUCTS)} افزونه"), ("key-round", "مدیریت لایسنس WPLM")]),
        sections=[
            dict(kind="cards", id="popular", alt=True, eyebrow="دسته‌های پرطرفدار", title="افزونه‌های پرکاربرد برای وردپرس و ووکامرس فارسی", cols=4, items=[
                dict(icon="shield-check", title="افزونه امنیتی وردپرس", text="فایروال، حفاظت ورود و پایش رخدادها در «نوین سکیوریتی سوئیت».", color="#e5484d"),
                dict(icon="wallet", title="افزونه کیف پول ووکامرس", text="اعتبار کاربران، شارژ و پرداخت از کیف پول در «کیف پول آمل».", color="#0e958c"),
                dict(icon="filter", title="افزونه فیلتر محصولات ووکامرس", text="فیلتر پیشرفته‌ی قیمت و ویژگی بدون بارگذاری مجدد صفحه.", color="#6d4aff"),
                dict(icon="b-woocommerce", title="افزونه ووکامرس فارسی", text="سفارشی‌ساز پرداخت و حساب کاربری، پیامک و ورود با کد یک‌بارمصرف.", color="#96588a")]),
            dict(kind="steps", id="how", eyebrow="روش خرید", center=True, title="خرید و فعال‌سازی افزونه چطور انجام می‌شود؟", items=[
                dict(title="انتخاب افزونه", text="افزونه‌ی موردنیاز را انتخاب و صفحه‌ی جزئیات آن را بررسی کنید."), dict(title="خرید و دریافت لایسنس", text="پس از خرید، کلید لایسنس و فایل افزونه در اختیار شما قرار می‌گیرد."),
                dict(title="نصب و فعال‌سازی", text="افزونه را در وردپرس نصب و با کلید لایسنس فعال کنید."), dict(title="پشتیبانی", text="در صورت نیاز با تیم پشتیبانی آمل نوین در ارتباط باشید.")]),
            dict(kind="faq", id="faq", items=[
                ("آیا همه‌ی افزونه‌ها نیاز به لایسنس دارند؟", "خیر؛ سه افزونه (مدیر لایسنس WPLM، گیاه‌یاب هوشمند مانلی و درخواست سرمایه‌گذاری ووکامرس) بدون نیاز به لایسنس ارائه می‌شوند و بقیه با کلید لایسنس فعال می‌شوند."),
                ("افزونه‌ها با وردپرس و ووکامرس فارسی سازگارند؟", "افزونه‌ها برای وردپرس فارسی و بسترهای ایرانی (تقویم، درگاه‌ها، پیامک) توسعه یافته‌اند؛ مشخصات دقیق سازگاری در صفحه‌ی هر افزونه آمده است."),
                ("اگر افزونه‌ی اختصاصی لازم داشته باشم چه کنم؟", "برای ساخت افزونه یا ماژول اختصاصی با ما تماس بگیرید؛ جزئیات در صفحه‌ی <a href=\"@/services/coding/\">طراحی سایت اختصاصی</a>."),
                ("خرید و جزئیات هر افزونه کجاست؟", "روی دکمه‌ی «مشاهده و خرید» هر کارت بزنید تا به صفحه‌ی محصول در فروشگاه WPLM منتقل شوید."),
            ]),
            dict(kind="cta", title="افزونه‌ی اختصاصی لازم دارید؟", text="اگر قابلیتی را در بازار پیدا نکردید، آن را برای شما کدنویسی می‌کنیم؛ همان‌گونه که این افزونه‌ها را ساخته‌ایم.", primary=("مشاوره‌ی افزونه‌ی اختصاصی", "/contact-us/"), secondary=("طراحی سایت اختصاصی", "/services/coding/")),
        ],
        ld=[{"@type": "ItemList", "@id": U + "/wplm-store/#list", "name": "افزونه‌های آمل نوین", "numberOfItems": len(PRODUCTS),
             "itemListElement": [{"@type": "ListItem", "position": i, "item": {"@type": "SoftwareApplication", "name": p["name"], "description": p["desc"], "applicationCategory": "BusinessApplication", "operatingSystem": "WordPress", "url": store.STORE_URL + p["slug"], "publisher": {"@id": schema.ORG_ID}}} for i, p in enumerate(PRODUCTS, 1)]}])
    ctx_faq = None

    # ---------------------------------------------------------------- blog index + posts
    add("/blog-n/", "blog.html", kind="blog", priority=0.8, schema_type="CollectionPage", og="blog-n",
        title="وبلاگ آمل نوین | راهنمای طراحی سایت، سئو و پشتیبانی سایت",
        desc="وبلاگ آمل نوین: مقالات آموزشی درباره‌ی هزینه طراحی سایت، طراحی سایت اختصاصی و وردپرس، پشتیبانی سایت، سئو و تولید محتوا، GEO و انتخاب شرکت طراحی سایت در آمل.",
        h1="وبلاگ <em>آمل نوین</em>؛ راهنمای طراحی سایت، سئو و پشتیبانی", eyebrow="وبلاگ", kw="وبلاگ آمل نوین", kws=["آموزش طراحی سایت", "راهنمای سئو", "نکات پشتیبانی سایت"],
        lead="آموزش طراحی سایت، راهنمای سئو و نکات پشتیبانی سایت؛ مقاله‌هایی کاربردی و بدون حاشیه برای صاحبان کسب‌وکار: از هزینه‌ی طراحی سایت تا سئو و آمادگی برای جستجوی هوش مصنوعی.",
        crumbs=[("وبلاگ", None)], short_title="وبلاگ", cat_label="وبلاگ", hero=dict(chips=["راهنمای خرید", "سئو", "پشتیبانی", "فنی"], visual=("html", collage(["img/blog/business-website-guide.webp", "img/blog/ai-seo-geo-guide.webp", "img/blog/why-wordpress.webp"]))),
        sections=[dict(kind="cta", title="سوالی دارید که اینجا جوابش نیست؟", text="بپرسید؛ پاسخ شما را با مقاله‌ی بعدی می‌دهیم یا مستقیم مشاوره می‌دهیم.", primary=("مشاوره‌ی رایگان", "/contact-us/"))],
        ld=[{"@type": "Blog", "@id": U + "/blog-n/#blog", "name": f"وبلاگ {SITE['name']}", "url": U + "/blog-n/", "inLanguage": "fa-IR", "publisher": {"@id": schema.ORG_ID}},
            schema.itemlist("مقالات", [(p["title"], f"{U}/{p['slug']}/") for p in POSTS_SORTED], U + "/blog-n/")])

    for p in POSTS_SORTED:
        add(f"/{p['slug']}/", "post.html", kind="post", post=p, slug=p["slug"], priority=0.7, og="post-" + p["slug"], date=p["date"], cat=p["cat"], published=p["date"], modified=p["date"],
            title=p.get("seo_title") or f"{p['title']} | آمل نوین", desc=p["desc"], h1=p["title"], kw=p["kw"], kws=[], short_title=p["title"], cat_label="مقاله",
            faq=fmt_faq_plain(p.get("faq", [])), crumbs=[("وبلاگ", "/blog-n/"), (p["title"], None)])

    # ---------------------------------------------------------------- html sitemap + 404
    add("/sitemap/", "sitemap.html", kind="sitemap", priority=0.3, changefreq="monthly",
        title="نقشه‌ی سایت آمل نوین | فهرست همه‌ی صفحات و خدمات", desc="نقشه‌ی سایت آمل نوین: فهرست کامل صفحات شامل خدمات طراحی سایت، ۲۰ نوع سایت، نمونه‌کارها، مقالات وبلاگ، فروشگاه افزونه و صفحات اطلاعاتی.",
        h1="نقشه‌ی <em>سایت</em>", eyebrow="همه‌ی صفحات", kw="نقشه‌ی سایت", lead="مرور سریع تمام صفحات وب‌سایت آمل نوین.", crumbs=[("نقشه‌ی سایت", None)], short_title="نقشه‌ی سایت",
        groups=[("صفحات اصلی", [("خانه", "/"), ("طراحی سایت در آمل", "/web-design-amol/"), ("درباره‌ی ما", "/about-us/"), ("رزومه‌ی مجموعه", "/resume/"), ("تماس با ما", "/contact-us/"), ("هزینه طراحی سایت", "/price-wordpress/")]),
                ("خدمات", [("همه‌ی خدمات", "/services/"), ("طراحی سایت وردپرس", "/wordpress-web-design/")] + [(n, u) for (u, n, *_r) in __import__("config").NAV_SERVICES if u not in ("/wordpress-web-design/",)]),
                ("انواع سایت", [(t["title"], f"/services/web-design/{t['slug']}/") for t in TYPES]),
                ("نمونه‌کارها", [("همه‌ی نمونه‌کارها", "/portfolio/")] + [(p["title"], f"/projects/{p['slug']}/") for p in detail]),
                ("وبلاگ و فروشگاه", [("وبلاگ", "/blog-n/")] + [(p["title"], f"/{p['slug']}/") for p in POSTS_SORTED] + [("فروشگاه افزونه‌ها", "/wplm-store/")]),
                ("قوانین", [("حریم خصوصی", "/privacy-policy/")])])
    add("/404.html", "404.html", kind="error", noindex=True, abs_links=True, priority=0.0,
        title="صفحه پیدا نشد (۴۰۴) | آمل نوین", desc="صفحه‌ی مورد نظر پیدا نشد. به صفحه‌ی اصلی آمل نوین برگردید یا با ما تماس بگیرید.", h1="صفحه پیدا نشد", crumbs=[])


def trim(s, n=158):
    if len(s) <= n:
        return s
    cut = s[:n].rsplit(" ", 1)[0].rstrip("،.؛: ")
    return cut + "…"


def fmt_faq_plain(items):
    return list(items)
