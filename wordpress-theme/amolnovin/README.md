# قالب وردپرس «فناوری اطلاعات آمل نوین» — Sage 11

تبدیل کامل قالب HTML «آمل نوین» (۵۸ صفحه) به یک قالب وردپرس واقعی، ساخته‌شده با **Sage 11** (Acorn 6 + Blade + Vite).
ظاهر، HTML، CSS و جاوااسکریپت همان قالب HTML است؛ فقط محتوا و تنظیمات حالا از پیشخوان وردپرس مدیریت می‌شود.

| | |
|---|---|
| وردپرس | ۶.۶ یا بالاتر (تست‌شده با ۷.۱.۳) |
| PHP | **۸.۳ یا بالاتر** (نیازمندی Acorn 6) |
| پایگاه داده | MySQL ۵.۷+ / MariaDB ۱۰.۴+ |
| Node.js | فقط برای توسعه: ۲۰.۱۹+ |
| زبان / جهت | فارسی، راست‌به‌چپ (`lang="fa" dir="rtl"`) |

---

## ۱. نصب روی سایت زنده (بدون نیاز به Node و Composer)

نسخه‌ی آماده‌ی **production** (`amolnovin-sage-theme.zip`) شامل پوشه‌ی `vendor` (Acorn) و فایل‌های ساخته‌شده‌ی `public/build` است.

1. در پیشخوان: **نمایش ← پوسته‌ها ← افزودن ← بارگذاری پوسته** و zip را آپلود کنید. (اگر آپلود به‌خاطر حجم رد شد، پوشه‌ی `amolnovin` را با FTP یا مدیر فایل در `wp-content/themes/` بریزید.)
2. **فعال‌سازی**.
3. بالای پیشخوان پیام «قالب آمل نوین فعال شد» را می‌بینید؛ روی **«محتوای نمونه را وارد کنید»** بزنید (یا **ابزارها ← ورود محتوای نمونه آمل نوین**).
   با خط فرمان: `wp amol import --reset`
   این کار ۲۰ صفحه، ۲۰ نوع سایت، ۲۰ نمونه‌کار، ۱۴ افزونه‌ی فروشگاه، ۵ مقاله و ۵ منو را می‌سازد و صفحه‌ی اصلی، صفحه‌ی وبلاگ و حریم خصوصی را تنظیم می‌کند.
4. **تنظیمات ← پیوندهای یکتا** را یک بار «ذخیره» کنید (ساختار پیشنهادی: «نام نوشته» `/%postname%/`).
5. **نمایش ← سفارشی‌سازی ← آمل نوین**: شماره‌ی تماس، ایمیل، نشانی، قیمت‌ها، شبکه‌های اجتماعی، شهرهای فوتر و ...
6. در صورت نیاز: **نمایش ← منوها** (منوی اصلی، منوی موبایل و سه ستون فوتر).

> ⚠️ پوشه‌ی `wp-content/cache` باید برای وب‌سرور قابل نوشتن باشد (Acorn نسخه‌ی کامپایل‌شده‌ی ویوها را آنجا نگه می‌دارد). روی Apache فایل `.htaccess` همین پوشه دسترسی مستقیم به `*.blade.php` را می‌بندد؛ روی Nginx این دو خط را اضافه کنید:
> ```nginx
> location ~* \.(blade\.php)$ { deny all; }
> location ~* /(composer\.(json|lock)|package(-lock)?\.json|vite\.config\.js)$ { deny all; }
> ```

## ۲. مدیریت محتوا

| می‌خواهید چه چیزی را عوض کنید؟ | کجا |
|---|---|
| شماره‌ی تماس، واتساپ، ایمیل، نشانی، مختصات نقشه، زمان پاسخ‌گویی | سفارشی‌سازی ← آمل نوین ← تماس و نشانی |
| قیمت‌ها («طراحی از ۱۷ میلیون» و ...) | سفارشی‌سازی ← آمل نوین ← قیمت‌ها و آمار |
| اینستاگرام، تلگرام، یوتیوب، ایتا، بله ... | سفارشی‌سازی ← آمل نوین ← شبکه‌های اجتماعی |
| شهرهای فوتر، Google Tag Manager | سفارشی‌سازی ← آمل نوین ← فوتر و ردیابی |
| منوی همبرگری روی تبلت (۱۰۲۱ تا ۱۲۳۵px) | سفارشی‌سازی ← آمل نوین ← چیدمان |
| منوها (هدر، موبایل، فوتر) و آیکن/رنگ هر آیتم | نمایش ← منوها |
| متن و بخش‌های هر صفحه، نوع سایت، نمونه‌کار، مقاله | ویرایش همان صفحه ← جعبه‌ی **«محتوای ساختاری — آمل نوین»** |
| عنوان سئو، توضیح متا، H1، کلمه‌ی کلیدی، noindex | ویرایش همان صفحه ← جعبه‌ی **«سئو — آمل نوین»** |
| فهرست‌های مشترک (فناوری‌ها، «چرا آمل نوین»، مراحل، کارت‌های قیمت، پرسش‌های صفحه‌ی اصلی، ماشین‌حساب، تیم) | نمایش ← **داده‌های اشتراکی قالب** |
| افزونه‌های فروشگاه | منوی «فروشگاه افزونه» (عنوان = نام، چکیده = توضیح، بقیه در «محتوای ساختاری») |
| درخواست‌های مشاوره‌ی فرم‌ها | منوی «درخواست‌ها» |

### ویرایشگر ساختاری
هر صفحه از چند «بخش» ساخته شده (کارت‌ها، مراحل، جدول، پرسش‌های متداول، فرم، نمونه‌کار ...). در جعبه‌ی «محتوای ساختاری» هر بخش یک کارت است:
فیلدها را مستقیم ویرایش کنید، ردیف‌ها را با ↑ ↓ جابه‌جا و با × حذف کنید، و از منوی **«افزودن بخش جدید»** هر یک از ۲۷ نوع بخش را اضافه کنید. تب «ویرایش خام (JSON)» برای کاربران حرفه‌ای است. با دکمه‌ی عادی «ذخیره/به‌روزرسانی» وردپرس ذخیره می‌شود.
محتوایی که در ویرایشگر بلوک (Gutenberg) خود صفحه بنویسید، **زیر بخش‌ها** نمایش داده می‌شود؛ مقاله‌های وبلاگ مثل هر نوشته‌ی وردپرس با ویرایشگر بلوک ویرایش می‌شوند.

### توکن‌های محتوا
شماره، ایمیل، نشانی و قیمت‌ها در متن‌ها با توکن نوشته شده‌اند تا با یک تغییر در سفارشی‌سازی همه‌جا (متن، Schema، متا، جست‌وجو، RSS) به‌روز شوند:

`[[phone_display]]` `[[phone_intl]]` `[[whatsapp]]` `[[email]]` `[[address]]` `[[street]]` `[[city]]` `[[region]]` `[[maps_query]]`
`[[price_design]]` `[[price_support]]` `[[price_seo]]` `[[support_months]]` `[[response_time]]` `[[founded_j]]` `[[years]]` `[[projects]]` `[[name]]` `[[short]]`

اعداد همیشه با ارقام فارسی نمایش داده می‌شوند (مثل قالب HTML).

## ۳. سئو و داده‌های ساختاریافته

- تگ‌های `title`، `description`، `robots`، `canonical`، `hreflang`، Open Graph، Twitter، geo و آیکن‌ها در `<head>` (کلاس `App\Seo\Head`).
- گراف JSON-LD کامل (`LocalBusiness`، `WebSite`، `WebPage`، `BreadcrumbList`، `FAQPage`، `Service`، `ItemList`، `BlogPosting` ...) — پورت دقیق `schema.py` قالب HTML؛ قیمت‌های Schema از سفارشی‌سازی محاسبه می‌شوند.
- `/sitemap.xml`، `/robots.txt` (شامل خزنده‌های هوش مصنوعی)، `/llms.txt`، `/manifest.webmanifest` و RSS در `/feed/`.
- ایندکس جست‌وجوی سمت کلاینت (Ctrl+K) خودکار با هر ذخیره‌ی محتوا بازسازی می‌شود.
- **اگر Yoast SEO، Rank Math، AIOSEO، SEOPress یا The SEO Framework** فعال باشد، خروجی سئوی قالب خودکار خاموش می‌شود تا تکراری نشود.
- ریدایرکت ۳۰۱ آدرس‌های قدیمی سایت (فارسی‌ها، `/projects/`، `/feed.xml`): `App\Content\Redirects` — با فیلتر `amol/redirects` قابل گسترش است.

## ۴. فرم مشاوره و درخواست‌ها
فرم‌ها به `POST /wp-json/amolnovin/v1/lead` می‌روند (جایگزین `api/lead.php`): اعتبارسنجی موبایل ایران، honeypot، محدودیت ۱ درخواست در ۳۰ ثانیه برای هر IP، ذخیره در منوی «درخواست‌ها» و ایمیل به مدیر. اگر سرور در دسترس نباشد فرم خودکار به واتساپ برمی‌گردد. ایمیل مقصد: سفارشی‌سازی ← تماس و نشانی.

## ۵. توسعه

```bash
cd wp-content/themes/amolnovin
composer install            # Acorn و ابزارهای کدنویسی
npm ci                      # Vite  (فایل .npmrc: legacy-peer-deps)
npm run build               # ساخت public/build
composer lint               # PHPCS با WordPress-Extra
wp acorn optimize           # کش کانفیگ و ویوها (روی سرور production)
```

برای انتشار: `npm run build` ← `composer install --no-dev --optimize-autoloader` ← آپلود همه‌ی فایل‌ها به‌جز `node_modules`.

### افزودن یک نوع بخش جدید
۱. `resources/views/page-sections/<kind>.blade.php` بسازید (متغیرها: `$sec` و `$idx`).
۲. نوع را در `App\Admin\DataEditor::KINDS` و `skeletons()` ثبت کنید تا در منوی «افزودن بخش» بیاید.

### افزودن یک نوع سایت یا نمونه‌کار
از منوی «انواع سایت» / «نمونه‌کارها» ← افزودن؛ در «محتوای ساختاری» یکی از موارد موجود را الگو بگیرید. آدرس‌ها `/services/web-design/{slug}/` و `/projects/{slug}/` هستند.

## ۶. ساختار پوشه‌ها

```
amolnovin/
├── app/
│   ├── Providers/ThemeServiceProvider.php   ← راه‌اندازی ماژول‌ها و دایرکتیوهای Blade (@icon، @rl)
│   ├── Content/    PostTypes · Repo · PageData · Redirects     ← انواع محتوا و داده‌ی هر صفحه
│   ├── Seo/        Head · Schema · Feeds                        ← سئو، JSON-LD، sitemap، llms، manifest
│   ├── Rest/Lead.php                                            ← API فرم مشاوره
│   ├── Admin/      Customizer · DataEditor · SeoBox · MenuFields · ThemeData · ImportPage · Importer · Leads
│   ├── Support/    Site · Persian · Html · Icons · Menus · Data · Headers
│   ├── Console/ImportCommand.php                                ← wp amol import
│   ├── setup.php · filters.php · helpers.php
├── resources/
│   ├── views/      layouts · sections (header/footer) · partials · components (ماکروها) · page-sections (۲۷ نوع بخش) + قالب صفحه‌ها
│   ├── css/        01..05 = CSS قالب HTML (بدون تغییر) · 06-wp.css · editor.css · admin.css
│   ├── js/         main.js (بدون تغییر) · app.js · admin.js (ویرایشگر ساختاری)
│   ├── images/ · fonts/                                         ← ۱۵۲ تصویر و فونت وزیرمتن
│   └── data/       site · common · pages · types · projects · products · posts · icons (.json)
├── public/build/   ← خروجی Vite
├── functions.php · index.php · style.css · theme.json · vite.config.js · composer.json · package.json
```

### نقشه‌ی قالب HTML ← وردپرس

| قالب HTML (Jinja) | قالب وردپرس (Blade) |
|---|---|
| `base.html` | `layouts/app.blade.php` + `app/Seo/Head.php` |
| `partials/header.html` | `sections/header.blade.php` (منوها از «نمایش ← منوها») |
| `partials/footer.html`, `overlays.html` | `sections/footer.blade.php`, `partials/overlays.blade.php` |
| `partials/page_hero.html` | `partials/page-hero.blade.php` |
| `home.html` | `front-page.blade.php` |
| `generic.html` (+ ماکرو `render_section`) | `page.blade.php` + `page-sections/*.blade.php` |
| `type.html` | `single-website_type.blade.php` |
| `portfolio.html` / `project.html` | `template-portfolio.blade.php` / `single-project.blade.php` |
| `store.html` | `template-store.blade.php` |
| `blog.html` / `post.html` | `home.blade.php` / `single.blade.php` |
| `sitemap.html`, `404.html` | `template-sitemap.blade.php`, `404.blade.php` |
| `macros.html` (sec_head، faq، lead_form، cta_band، project_card ...) | `components/*.blade.php` (`<x-sec-head>`, `<x-faq>` ...) |
| `build.py` (sprite آیکن، ارقام فارسی، schema) | `Support/Icons.php`, `Support/Persian.php`, `Seo/Schema.php` + خروجی‌گیر در `Support/Html.php` |
| `api/lead.php` | `Rest/Lead.php` |
| `.htaccess` (ریدایرکت‌ها، هدرها) | `Content/Redirects.php`, `Support/Headers.php` |

## ۷. تفاوت‌های آگاهانه با قالب HTML

۱. **منوی همبرگری بین ۱۰۲۱ تا ۱۲۳۵ پیکسل** (iPad افقی و لپ‌تاپ‌های کوچک). در قالب HTML در این بازه منوی کامل از صفحه بیرون می‌زند (دکمه‌ی «مشاوره‌ی رایگان» بریده می‌شود و اسکرول افقی ایجاد می‌شود). در قالب وردپرس همان هدر موبایل نمایش داده می‌شود. فقط ۹ خط CSS در `06-wp.css`؛ از سفارشی‌سازی ← چیدمان قابل خاموش‌کردن است.
۲. شناسه‌ی بخش «تولید محتوا» در `/services/seo/` از `content` به `section-content` تغییر کرد؛ با `<main id="content">` (هدف لینک «پرش به محتوا») تکراری بود و لینک «تولید محتوا» در منوی میان‌صفحه به بالای صفحه می‌پرید.
۳. فرم‌ها به REST وردپرس می‌روند، نه `api/lead.php`؛ `feed.xml` به `/feed/` (RSS خود وردپرس) تبدیل شد و `/feed.xml` ریدایرکت ۳۰۱ می‌شود.
۴. آدرس‌ها مطلق (بر پایه‌ی `home_url`) هستند و قالب روی `file://` اجرا نمی‌شود (وردپرس سرور می‌خواهد).
۵. تصاویر و فایل‌ها با هش محتوا (`app-XXXX.css`) از Vite سرو می‌شوند.

## ۸. عملکرد

CSS حدود **۱۸ کیلوبایت** و JS حدود **۶ کیلوبایت** (gzip)، تنها یک فایل CSS و یک فایل JS؛ بدون jQuery و بدون CSS بلوک‌های وردپرس در صفحه‌هایی که بلوک ندارند؛ فونت وزیرمتن self-host با preload؛ تصاویر `lazy`؛ آیکن‌ها فقط با نمادهای استفاده‌شده در همان صفحه (SVG sprite). روی سرور production کش صفحه (WP Rocket، LiteSpeed Cache و ...) و فشرده‌سازی Brotli/gzip را فعال کنید.

## ۹. عیب‌یابی

| مشکل | راه‌حل |
|---|---|
| صفحه‌ی خطا درباره‌ی `vendor/autoload.php` | نسخه‌ی production را نصب کنید یا `composer install --no-dev` اجرا کنید. |
| `Vite manifest not found` | `npm run build` را اجرا کنید (یا نسخه‌ی production را نصب کنید). |
| صفحه‌ها ۴۰۴ می‌دهند | تنظیمات ← پیوندهای یکتا ← ذخیره. |
| تغییر Blade دیده نمی‌شود | `wp acorn optimize:clear` یا حذف `wp-content/cache/acorn`. |
| ایمیل فرم نمی‌رسد | درخواست‌ها در منوی «درخواست‌ها» ذخیره می‌شوند؛ برای ایمیل یک افزونه‌ی SMTP نصب کنید. |
| بعد از تغییر محتوا جست‌وجو قدیمی است | ذخیره‌ی هر محتوا ایندکس را بازسازی می‌کند؛ فایل `wp-content/uploads/amolnovin/search-index.js` را پاک کنید تا دوباره ساخته شود. |

## ۱۰. مجوزها
کد قالب و Sage: MIT. فونت Vazirmatn: SIL OFL ۱.۱. آیکن‌ها: Lucide (ISC) و Simple Icons (CC0). متن‌ها، تصاویر و برند «آمل نوین» متعلق به صاحب سایت است.
