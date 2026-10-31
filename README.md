# آمل نوین — قالب HTML و قالب وردپرس

وب‌سایت «فناوری اطلاعات آمل نوین» (آمل، مازندران) در دو شکل با **یک طراحی یکسان**:

| پوشه | چیست؟ |
|---|---|
| [`html-template/`](html-template) | قالب **HTML استاتیک** (۵۸ صفحه، فارسی و راست‌به‌چپ) + مولد پایتون + تحقیق کلمات کلیدی و ممیزی سئو |
| [`wordpress-theme/`](wordpress-theme) | **قالب وردپرس** (Sage 11 / Acorn / Blade / Vite) با همان HTML، CSS و جاوااسکریپت؛ محتوا و تنظیمات از پیشخوان وردپرس |

## شروع سریع

**قالب HTML** — خود سایت آماده‌ی آپلود است:

```bash
cd html-template/amolnovin-theme && python3 -m http.server 8080      # http://localhost:8080
# بازسازی از سورس:  cd html-template/amolnovin-generator && pip install jinja2 && python3 build.py
```

**قالب وردپرس** — به PHP ≥ 8.3 و وردپرس ≥ 6.6 نیاز دارد:

```bash
cd wordpress-theme && ./scripts/build-zip.sh        # → dist/amolnovin-sage-theme.zip  (در وردپرس: نمایش ← پوسته‌ها ← بارگذاری)
# پس از فعال‌سازی:  ابزارها ← ورود محتوای نمونه آمل نوین   (یا  wp amol import --reset)
```

## چقدر «یکسان» است؟

قالب وردپرس با ابزارهای خودکار (در `wordpress-theme/qa-tools/`) با قالب HTML مقایسه شده است:

- DOM، `<head>` و JSON-LD همه‌ی ۵۸ صفحه: **۵۸ از ۵۸ یکسان**
- مقایسه‌ی پیکسلی اسکرین‌شات تمام‌صفحه (دسکتاپ و موبایل، روشن و تیره): **همه یکسان**
- تست رفتار جاوااسکریپت ۱۹/۱۹ · بررسی‌های وردپرس ۲۷/۲۷ · پیشخوان ۱۱/۱۱ · اسکرول افقی صفر در همه‌ی عرض‌ها · PHPCS (WordPress-Extra) صفر خطا

جزئیات، معماری و دو تفاوت آگاهانه (منوی همبرگری بین ۱۰۲۱ و ۱۲۳۵ پیکسل و شناسه‌ی یک بخش) در [`wordpress-theme/docs/WORDPRESS-REPORT.md`](wordpress-theme/docs/WORDPRESS-REPORT.md).

## رابطه‌ی دو پوشه

`html-template/amolnovin-generator` منبع اصلی محتوا و طراحی است. دستور
`python3 tools/export_wp.py` داده‌های آن را به JSON برای قالب وردپرس (`wordpress-theme/amolnovin/resources/data/`) تبدیل می‌کند؛ CSS و JS هر دو قالب یکی است.

## مجوزها
کد قالب وردپرس و Sage: MIT · فونت Vazirmatn: SIL OFL 1.1 · آیکن‌ها: Lucide (ISC) و Simple Icons (CC0).
متن‌ها، تصاویر نمونه‌کار و برند «آمل نوین» متعلق به صاحب سایت است.
