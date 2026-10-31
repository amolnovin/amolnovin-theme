# قالب وردپرس آمل نوین (Sage 11)

تبدیل کامل قالب HTML [`../html-template`](../html-template) به قالب وردپرس. راهنمای کامل نصب، مدیریت محتوا، سئو و توسعه در [`amolnovin/README.md`](amolnovin/README.md) است.

| مسیر | چیست؟ |
|---|---|
| [`amolnovin/`](amolnovin) | **خود قالب** (سورس Sage: `app/`، `resources/views`، `resources/data` ...) |
| [`docs/WORDPRESS-REPORT.md`](docs/WORDPRESS-REPORT.md) | بررسی قالب HTML، معماری، نتایج تست و تفاوت‌های آگاهانه |
| [`qa-tools/`](qa-tools) | ابزارهای مقایسه و تست (DOM، پیکسل، JS، پیشخوان، ریسپانسیو) |
| [`scripts/build-zip.sh`](scripts/build-zip.sh) | ساخت zip آماده‌ی نصب (با `vendor` و `public/build`) |
| [`updates/`](updates) | کاتالوگ بروزرسانی با پچ (`manifest.json` + `manifest.schema.json` + `patches/`) |
| [`scripts/make-patch.sh`](scripts/make-patch.sh) | ساخت پچ افزایشی از `git diff` و ثبت آن در کاتالوگ |
| [`docs/THEME-SETTINGS.md`](docs/THEME-SETTINGS.md) | راهنمای تب‌های «تنظیمات قالب آمل نوین» |
| [`docs/UPDATES.md`](docs/UPDATES.md) | چرخه‌ی انتشار و بروزرسانی با پچ |
| [`docs/CHANGELOG.md`](docs/CHANGELOG.md) | تغییرات هر نسخه |

## نیازمندی‌ها
PHP **۸.۳+** · وردپرس ۶.۶+ (تست‌شده با ۷.۱.۳) · MySQL ۵.۷+/MariaDB ۱۰.۴+ · Composer ۲ و Node ۲۰.۱۹+ فقط برای ساخت.

## نصب

### راه ۱ — zip (پیشنهادی)
```bash
./scripts/build-zip.sh                 # → dist/amolnovin-sage-theme.zip
```
در وردپرس: **نمایش ← پوسته‌ها ← افزودن ← بارگذاری** ← فعال‌سازی ← **ابزارها ← ورود محتوای نمونه آمل نوین** (یا `wp amol import --reset`) ← **تنظیمات ← پیوندهای یکتا ← ذخیره**.

### راه ۲ — روی سرور (SSH)
```bash
cp -r amolnovin /var/www/html/wp-content/themes/amolnovin && cd /var/www/html/wp-content/themes/amolnovin
composer install --no-dev --optimize-autoloader
npm ci && npm run build
wp theme activate amolnovin && wp amol import --reset
```

### توسعه
```bash
cd amolnovin && composer install && npm ci && npm run build
composer lint            # PHPCS (WordPress-Extra)
```

## تولید دوباره‌ی داده‌ها از قالب HTML
`resources/data/*.json` خروجی مولد پایتون است:

```bash
cd ../html-template/amolnovin-generator && pip install jinja2 && python3 tools/export_wp.py
```

## تنظیمات قالب و هماهنگی افزونه‌ها

بعد از فعال‌سازی، منوی **آمل نوین** در پیشخوان هشت تب دارد: بهینه‌سازی سرعت، سئو، هدر و فوتر، شمارنده‌ها،
فرم‌ها و کپچا، افزونه‌ها و فروشگاه، و بروزرسانی قالب. همه‌ی مقادیر در یک ردیف (`amol_options`) ذخیره می‌شوند و
هر تب مستقل ذخیره می‌شود.

| افزونه | چه چیزی از قالب با آن هماهنگ می‌شود |
|---|---|
| **Custom HTML Snippets 3.0.0+** | شورت‌کد `[my_snippet id="…"]` در محتوا/خلاصه/بخش‌ها، بخش «قطعه کد سفارشی»، خلاصه‌ی بالای وبلاگ |
| **WP Plugin Market Manager 1.2+** | صفحه‌ی `/wplm-store/` با `[wplm_store]` (محصول، سبد، پرداخت، نتیجه، حساب کاربری) |
| **WP Math Captcha 1.3+** | کپچا در فرم مشاوره (سمت سرور در `/wp-json/amolnovin/v1/lead`)، دیدگاه‌ها، تازه‌سازی چالش |
| **Novin Security Suite 1.3+** | باز ماندن REST فرم، جلوگیری از هدرهای امنیتی تکراری، نمایش وضعیت در پنل |

صفحه‌ی `/blog-n/` صفحه‌ی نوشته‌های وردپرس است (`تنظیمات ← خواندن ← برگه‌ی نوشته‌ها`)، پس همه‌ی نوشته‌ها،
دسته‌بندی‌ها، تصاویر شاخص و صفحه‌بندی وردپرس کار می‌کنند.

## انتشار نسخه

```bash
git tag v1.1.0 && git push origin v1.1.0     # workflow «Release theme» zip کامل را می‌سازد
cd wordpress-theme && ./scripts/make-patch.sh v1.0.0 HEAD 1.1.0 "یادداشت نسخه" 1.0.0
```

## ابزارهای تست
به [`qa-tools/README.md`](qa-tools/README.md) نگاه کنید. برای ساخت یک سایت وردپرس آزمایشی: `qa-tools/setup_lab.sh`.
