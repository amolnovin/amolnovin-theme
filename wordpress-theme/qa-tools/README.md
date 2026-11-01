# ابزارهای تست قالب وردپرس

پایتون ۳ + Playwright. هر اسکریپت `_pages.json` (فهرست ۵۸ صفحه‌ی قالب HTML، کنار همین فایل‌ها) را خودش پیدا می‌کند.

```bash
pip install playwright lxml pillow html5lib && python -m playwright install chromium
```

`STATIC` = قالب HTML روی یک وب‌سرور (مثلاً `python3 -m http.server 8080` داخل `html-template/amolnovin-theme`)،
`WP` = سایت وردپرس آزمایشی که قالب روی آن فعال و محتوای نمونه وارد شده است (ساخت: `./setup_lab.sh`).

| اسکریپت | چه چیزی را می‌سنجد |
|---|---|
| `python3 wp_compare.py --static $STATIC --wp $WP --head` | درخت DOM بدنه، تگ‌های `<head>` و کل JSON-LD هر صفحه (با نرمال‌سازی آدرس، هش فایل‌ها، ترتیب کلاس‌ها ...) |
| `python3 wp_visual.py --static $STATIC --wp $WP --widths 1440,390 --themes light,dark --tol 0` | مقایسه‌ی پیکسلی اسکرین‌شات تمام‌صفحه؛ خروجی تفاوت‌ها در `qa-output/` |
| `python3 wp_qa.py --static $STATIC --wp $WP --site-dir /path/to/wordpress` | sitemap، robots، llms.txt، manifest، RSS، ایندکس جست‌وجو، ریدایرکت‌ها، ۴۰۴، هدرهای امنیتی، تغییر شماره از Customizer، لاگ PHP |
| `QA_BASE=$WP python3 qa_js.py` | رفتار جاوااسکریپت: منوها، جست‌وجو، فیلترها، ماشین‌حساب، فرم، تم، موبایل |
| `QA_BASE=$WP python3 overflow.py 390` | اسکرول افقی همه‌ی صفحه‌ها در یک عرض مشخص |
| `python3 menu_check.py --base $WP` | هدر: دراپ‌داون «بیشتر»، `header__actions`، کیبورد، لمس، منوی موبایل |
| `QA_BASE=$WP QA_SITE_DIR=/path/to/wordpress QA_ADMIN_PASS=... python3 wp_admin_test.py` | پیشخوان: ورود، ویرایشگر ساختاری، ذخیره با ویرایشگر بلوک، افزودن بخش، نمایش در سایت |
| `php snippet-css-scope-test.php` | محدودسازی CSS قطعه‌کدهای افزونه (Custom HTML Snippets 3.0.x) به `.custom-snippet-container` — بدون نیاز به وردپرس |
| `php patch-backup-dir-test.php` | ساخت پوشه‌ی پشتیبان بروزرسانی (`uploads/amolnovin-backups`)، حالت‌های جایگزین و پیام خطای «ساخت پوشه‌ی پشتیبان ناموفق بود» — بدون نیاز به وردپرس |

`router.php` روتر سرور داخلی PHP برای پیوندهای یکتا است: `WP_ROOT=/path/to/wordpress php -S 0.0.0.0:8081 router.php`.
