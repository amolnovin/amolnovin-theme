# قالب وردپرس آمل نوین (Sage 11)

تبدیل کامل قالب HTML [`../html-template`](../html-template) به قالب وردپرس. راهنمای کامل نصب، مدیریت محتوا، سئو و توسعه در [`amolnovin/README.md`](amolnovin/README.md) است.

| مسیر | چیست؟ |
|---|---|
| [`amolnovin/`](amolnovin) | **خود قالب** (سورس Sage: `app/`، `resources/views`، `resources/data` ...) |
| [`docs/WORDPRESS-REPORT.md`](docs/WORDPRESS-REPORT.md) | بررسی قالب HTML، معماری، نتایج تست و تفاوت‌های آگاهانه |
| [`qa-tools/`](qa-tools) | ابزارهای مقایسه و تست (DOM، پیکسل، JS، پیشخوان، ریسپانسیو) |
| [`scripts/build-zip.sh`](scripts/build-zip.sh) | ساخت zip آماده‌ی نصب (با `vendor` و `public/build`) |

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

## ابزارهای تست
به [`qa-tools/README.md`](qa-tools/README.md) نگاه کنید. برای ساخت یک سایت وردپرس آزمایشی: `qa-tools/setup_lab.sh`.
