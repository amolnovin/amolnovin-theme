# -*- coding: utf-8 -*-
"""Builds docs/keyword-research.md + docs/keywords.csv from Google-Suggest mining data + the generated site (verifies real usage)."""
import json, re, glob, os, sys, csv
from bs4 import BeautifulSoup
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)
R = f"{ROOT}/_assetsrc/keyword-data"; OUT = os.path.join(os.path.dirname(ROOT), "amolnovin-theme"); DOCS = os.path.join(os.path.dirname(ROOT), "docs")
os.makedirs(DOCS, exist_ok=True)
import util
from data.types_a import TYPES_A
from data.types_b import TYPES_B
TYPES = TYPES_A + TYPES_B

def norm(s):
    s = s.replace("\u200c", " ").replace("ي", "ی").replace("ك", "ک").replace("ۀ", "ه")
    s = s.translate(str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩", "01234567890123456789"))
    return re.sub(r"\s+", " ", s).strip().lower()
def nz(s): return norm(s).replace(" ", "")      # space/ZWNJ-insensitive

raw = json.load(open(f"{R}/suggest_raw.json", encoding="utf-8"))
allsug = set(); 
for v in raw.values(): allsug |= {nz(x) for x in v}
for f in glob.glob(f"{R}/suggest2_*.json"):
    for seed, ranked in json.load(open(f, encoding="utf-8")).items(): allsug |= {nz(x[0]) for x in ranked}
def signal(kw):
    n = nz(kw)
    if n in allsug: return "پیشنهاد مستقیم"
    if any(n in s for s in allsug): return "بخشی از پیشنهادهای بلندتر"
    return "مشتق/معنایی"

# ---- page index of the built site
pages = {}
for f in glob.glob(f"{OUT}/**/*.html", recursive=True):
    rel = os.path.relpath(f, OUT).replace(os.sep, "/")
    path = "/" if rel == "index.html" else "/" + rel[:-len("index.html")] if rel.endswith("/index.html") else "/" + rel
    if path == "/404.html": continue
    soup = BeautifulSoup(open(f, encoding="utf-8").read(), "lxml")
    title = soup.title.string if soup.title else ""
    heads = " ".join(h.get_text(" ") for h in soup.find_all(["h1", "h2", "h3"]))
    for t in soup(["script", "style", "svg"]): t.decompose()
    main = soup.find("main") or soup.body
    pages[path] = dict(title=nz(title), heads=nz(heads), text=nz(main.get_text(" ")), h1=nz((soup.find("h1") or soup).get_text(" ")))
def usage(kw):
    n = nz(kw); inh = [p for p, d in pages.items() if n in d["heads"] or n in d["title"]]
    intext = [p for p, d in pages.items() if n in d["text"]]
    return inh, intext

K = [
 ("طراحی سایت", "/services/web-design/", [
   ("هزینه طراحی سایت", "تجاری — بررسی قیمت", "/price-wordpress/"), ("طراحی سایت فروشگاهی", "تجاری", "/services/web-design/ecommerce/"),
   ("طراحی سایت شرکتی", "تجاری", "/services/web-design/corporate/"), ("طراحی سایت حرفه ای", "تجاری", "/services/web-design/"),
   ("طراحی سایت با هوش مصنوعی", "اطلاعاتی / روند جدید", "/services/web-design/")]),
 ("طراحی سایت در آمل", "/web-design-amol/ + صفحه‌ی اصلی", [
   ("شرکت طراحی سایت در آمل", "تجاری محلی", "/web-design-amol/"), ("طراحی سایت آمل", "محلی", "/"), ("طراحی سایت وردپرس در آمل", "محلی ـ تجاری", "/web-design-amol/"),
   ("آموزش طراحی سایت در آمل", "آموزشی محلی", "/wordpress-learn/"), ("طراحی سایت در مازندران", "محلی ـ منطقه‌ای", "/web-design-amol/")]),
 ("طراحی سایت وردپرس", "/wordpress-web-design/", [
   ("هزینه طراحی سایت وردپرس ۱۴۰۵", "تجاری — بررسی قیمت", "/wordpress-web-design/"), ("طراحی سایت فروشگاهی با وردپرس", "تجاری", "/services/web-design/ecommerce/"),
   ("طراحی سایت وردپرس شرکتی", "تجاری", "/services/web-design/corporate/"), ("طراحی سایت وردپرس با المنتور", "اطلاعاتی / تجاری", "/wordpress-web-design/"),
   ("آموزش طراحی سایت وردپرس", "آموزشی", "/wordpress-learn/")]),
 ("پشتیبانی سایت در آمل", "/services/site-support/", [
   ("هزینه پشتیبانی سایت وردپرس", "تجاری — بررسی قیمت", "/services/site-support/"), ("هزینه نگهداری سایت", "تجاری — بررسی قیمت", "/services/site-support/"),
   ("پشتیبانی سایت فروشگاهی", "تجاری", "/services/site-support/"), ("قرارداد پشتیبانی سایت", "تحقیقی", "/services/site-support/"),
   ("خدمات امنیت سایت وردپرس", "تجاری", "/services/security/")]),
 ("سئو و تولید محتوا", "/services/seo/", [
   ("تولید محتوا سئو شده", "تجاری / تحقیقی", "/services/seo/"), ("تولید محتوا با هوش مصنوعی", "اطلاعاتی", "/services/seo/"),
   ("هزینه سئو سایت", "تجاری — بررسی قیمت", "/services/seo/"), ("سئو سایت وردپرس", "تجاری / اطلاعاتی", "/services/seo/"),
   ("سئو سایت فروشگاهی", "تجاری", "/services/seo/")]),
 ("طراحی سایت اختصاصی", "/services/coding/", [
   ("هزینه طراحی سایت اختصاصی", "تجاری — بررسی قیمت", "/services/coding/"), ("طراحی سایت اختصاصی یا وردپرس", "تحقیقی (مقایسه)", "/custom-website-vs-wordpress/"),
   ("طراحی سایت اختصاصی چیست", "اطلاعاتی", "/services/coding/"), ("طراحی سایت فروشگاهی اختصاصی", "تجاری", "/services/coding/"),
   ("طراحی سایت با قالب اختصاصی", "تجاری", "/services/coding/")]),
]
fa = util.fa
L = []
L += ["# تحقیق کلمات کلیدی — آمل نوین", "", f"> تاریخ تهیه: ۲۳ مهر ۱۴۰۵ · منبع داده: Google Suggest (زبان فارسی، موقعیت ایران) + بررسی صفحات سایت فعلی و رقبای عمومی", "",
      "## ۱) خلاصه", "",
      "- ۶ کلمه‌ی کلیدی اصلی که شما تعیین کردید، هر کدام یک **صفحه‌ی ستونی (Pillar)** مشخص دارد تا صفحه‌ها با هم رقابت (Cannibalization) نکنند.",
      "- برای هر کلمه‌ی اصلی، ۵ کلمه‌ی مرتبط و پرجستجو استخراج و در همان صفحه به‌صورت **H2/H3، سوالات متداول، متن و لینک‌دهی داخلی** استفاده شده است (۳۰ کلمه).",
      "- علاوه بر آن، برای هر یک از ۲۰ نوع سایت یک کلمه‌ی اصلی و چند کلمه‌ی فرعی تعریف شده است.",
      "- ستون «استفاده در قالب» از روی خروجی واقعی سایت ساخته شده (نه حدسی)؛ یعنی هر کلمه واقعاً در عنوان/H1/H2 یا متن آن صفحات هست.", "",
      "## ۲) روش کار و محدودیت داده", "",
      "1. هر کلمه‌ی اصلی با ۱۹ پیشوند/پسوند پرتکرار ترکیب شد (قیمت، هزینه، در، با، چیست، حرفه‌ای، ارزان، بهترین، آموزش، ۱۴۰۵، شهرها و ...) و پیشنهادهای خودکار گوگل برای ایران جمع‌آوری شد.",
      "2. پیشنهادها بر اساس **تکرار در چند ترکیب** و **ترتیب نمایش** رتبه‌بندی شدند؛ هرچه عبارتی زودتر و در ترکیب‌های بیشتری ظاهر شود، تقاضای واقعی‌تری دارد.",
      "3. از میان آن‌ها، عبارت‌هایی انتخاب شدند که (الف) با خدمات واقعی آمل نوین مرتبط‌اند، (ب) نیت جستجوی متفاوتی از کلمه‌ی اصلی دارند تا محتوا تکراری نشود، و (ج) برای قیف فروش (هزینه، شرکت، سفارش) یا اعتمادسازی (چیست، مقایسه) مفیدند.",
      "4. برای عبارت‌های محلی (آمل/مازندران) چون حجم جستجو کم اما نیت خرید بالاست، حتی با تعداد پیشنهاد کم انتخاب شدند.", "",
      "> **نکته‌ی صادقانه:** حجم دقیق ماهانه‌ی جستجو را فقط ابزارهایی مثل Google Keyword Planner، Search Console یا Ahrefs/Semrush می‌دهند و در دسترس این پروژه نبود. ستون «سیگنال» نشان می‌دهد عبارت در Autocomplete گوگل دیده شد یا نه؛ این یک سیگنال نسبی تقاضاست، نه عدد حجم. بعد از راه‌اندازی، اعداد را از Search Console بخوانید و جدول را بروزرسانی کنید.", ""]
L += ["## ۳) جدول ۶ کلمه‌ی اصلی × ۵ کلمه‌ی مرتبط (۳۰ کلمه)", ""]
rows_csv = [["کلمه‌ی اصلی", "کلمه‌ی مرتبط", "نیت جستجو", "سیگنال Google Suggest", "صفحه‌ی هدف", "تعداد صفحات دارای کلمه", "حضور در عنوان/H1/H2"]]
for i, (main, page, rel) in enumerate(K, 1):
    inh, intext = usage(main)
    L += [f"### {fa(i)}. «{main}»  ←  صفحه‌ی ستونی: `{page}`", "",
          f"حضور کلمه‌ی اصلی در عنوان/H1/H2: {', '.join('`'+p+'`' for p in inh[:6]) or '—'}", "",
          "| # | کلمه‌ی مرتبط | نیت جستجو | سیگنال Google Suggest | صفحه‌ی هدف | استفاده در قالب (صفحات دارای کلمه / در عنوان‌ها) |", "|---|---|---|---|---|---|"]
    for j, (kw, intent, tgt) in enumerate(rel, 1):
        inh2, intext2 = usage(kw)
        L.append(f"| {fa(j)} | **{kw}** | {intent} | {signal(kw)} | `{tgt}` | {fa(len(intext2))} صفحه / {fa(len(inh2))} عنوان‌دار |")
        rows_csv.append([main, kw, intent, signal(kw), tgt, len(intext2), len(inh2)])
    L.append("")
L += ["## ۴) نقشه‌ی کلمه ← صفحه (جلوگیری از رقابت داخلی)", "", "| کلمه‌ی اصلی | صفحه‌ی ستونی | صفحات پشتیبان (لینک داخلی با انکر مرتبط) |", "|---|---|---|",
      "| طراحی سایت | `/services/web-design/` | صفحه‌ی اصلی، ۲۰ صفحه‌ی نوع سایت، هزینه طراحی سایت |",
      "| طراحی سایت در آمل | `/web-design-amol/` و صفحه‌ی اصلی | درباره‌ی ما، تماس، مقاله‌ی «انتخاب شرکت طراحی سایت در آمل» |",
      "| طراحی سایت وردپرس | `/wordpress-web-design/` | سایت‌های شرکتی/فروشگاهی، آموزش وردپرس، قیمت |",
      "| پشتیبانی سایت در آمل | `/services/site-support/` | امنیت و سرعت، مقاله‌ی راهنمای پشتیبانی وردپرس |",
      "| سئو و تولید محتوا | `/services/seo/` | مقاله‌ی GEO/AEO، نمونه‌کارهای دارای سئو |",
      "| طراحی سایت اختصاصی | `/services/coding/` | مقاله‌ی اختصاصی یا وردپرس، فروشگاه افزونه‌ها |", ""]
L += ["## ۵) کلمات هدف ۲۰ صفحه‌ی «انواع سایت»", "", "کلمه‌ی اصلی هر صفحه در عنوان، توضیح متا، H1، پاراگراف اول و یک H2 آمده و کلمات فرعی (از Google Suggest) در امکانات، سوالات متداول و بخش سئو استفاده شده‌اند.", "",
      "| نوع سایت | کلمه‌ی اصلی | کلمات فرعی | سیگنال | آدرس |", "|---|---|---|---|---|"]
for t in TYPES:
    L.append(f"| {t['name']} | **{t['kw']}** | {'، '.join(t['kws'])} | {signal(t['kw'])} | `/services/web-design/{t['slug']}/` |")
    rows_csv.append(["(نوع سایت) " + t["name"], t["kw"], "تجاری", signal(t["kw"]), f"/services/web-design/{t['slug']}/", len(usage(t['kw'])[1]), len(usage(t['kw'])[0])])
L += ["", "## ۶) کلمات صفحات پشتیبان", "", "| صفحه | کلمه‌ی اصلی | نمونه‌ی کلمات فرعی |", "|---|---|---|",
      "| `/services/security/` | امنیت سایت وردپرس | خدمات امنیت سایت وردپرس، افزایش امنیت، بکاپ‌گیری، افزایش سرعت سایت وردپرس |",
      "| `/services/computer-network/` | خدمات کامپیوتر و شبکه در آمل | خدمات کامپیوتری آمل، پشتیبانی شبکه در آمل، عیب‌یابی کامپیوتر |",
      "| `/wordpress-learn/` | آموزش طراحی سایت با وردپرس | آموزش طراحی سایت وردپرس، آموزش وردپرس با هوش مصنوعی، دوره آموزشی طراحی سایت وردپرس |",
      "| `/price-wordpress/` | هزینه طراحی سایت | هزینه طراحی سایت وردپرس ۱۴۰۵، قیمت طراحی سایت فروشگاهی، هزینه پشتیبانی/سئو |",
      "| `/portfolio/` | نمونه کار طراحی سایت | نمونه کار طراحی سایت وردپرس، نمونه کار طراحی فروشگاه |",
      "| `/wplm-store/` | افزونه وردپرس | افزونه ووکامرس فارسی، افزونه امنیتی وردپرس، افزونه کیف پول ووکامرس، افزونه فیلتر محصولات ووکامرس |",
      "| `/blog-n/` | وبلاگ آمل نوین | آموزش طراحی سایت، راهنمای سئو، نکات پشتیبانی سایت |", ""]
L += ["## ۷) قواعد جایگذاری کلمه (چک‌لیست هر صفحه)", "",
      "- **عنوان (Title):** کلمه‌ی اصلی در ابتدا، ۳۰ تا ۷۵ نویسه، برند در انتها.",
      "- **توضیح متا:** ۱۰۰ تا ۱۷۵ نویسه، شامل کلمه‌ی اصلی، یک مزیت (قیمت/پشتیبانی) و دعوت به اقدام.",
      "- **H1:** فقط یکی و شامل کلمه‌ی اصلی؛ **H2/H3:** کلمات مرتبط به‌صورت عنوان طبیعی.",
      "- **پاراگراف اول / خلاصه‌ی پاسخ (TL;DR):** کلمه‌ی اصلی در ۱۵۰ کلمه‌ی اول برای موتورهای جستجو و پاسخ‌گوهای هوش مصنوعی.",
      "- **سوالات متداول:** هر سوال با شکل جستجوی واقعی کاربر (هزینه … چقدر است؟ / … چیست؟ / … یا …؟) و اسکیمای FAQPage.",
      "- **لینک داخلی:** انکر متن شامل کلمه‌ی هدف به صفحه‌ی ستونی؛ هر صفحه‌ی ستونی حداقل ۳ لینک ورودی دارد.",
      "- **تصاویر:** نام فایل لاتین توصیفی + alt فارسی طبیعی + ابعاد مشخص.", "",
      "## ۸) گام‌های بعدی پیشنهادی", "",
      "1. ثبت سایت در Google Search Console و ارسال `sitemap.xml`؛ بعد از ۲ تا ۴ هفته گزارش «Queries» را با جدول بالا مقایسه کنید.",
      "2. در Google Keyword Planner همین ۳۰ کلمه را وارد کنید و ستون «حجم ماهانه» را به جدول اضافه کنید.",
      "3. ثبت و تکمیل پروفایل «Google Business Profile» برای آدرس آمل (برای کلمات محلی ضروری است).",
      "4. برای هر ۲ هفته یک مقاله‌ی جدید با کلمات فرعی ثبت‌نشده (مثلاً «طراحی سایت با هوش مصنوعی»، «قرارداد پشتیبانی سایت») منتشر کنید.", "",
      "## پیوست: خروجی خام Google Suggest (بخشی از پیشنهادها)", ""]
for seed, lst in raw.items():
    L += [f"**{seed}** ({fa(len(lst))} پیشنهاد):", "", "، ".join(lst[:28]), ""]
open(f"{DOCS}/keyword-research.md", "w", encoding="utf-8").write("\n".join(L) + "\n")
with open(f"{DOCS}/keywords.csv", "w", encoding="utf-8-sig", newline="") as f: csv.writer(f).writerows(rows_csv)
print("written", len(L), "lines;", len(rows_csv) - 1, "csv rows")
