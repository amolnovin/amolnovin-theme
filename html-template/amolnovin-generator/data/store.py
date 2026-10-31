# -*- coding: utf-8 -*-
"""WPLM store (plugins by Amol Novin).  Product pages are served dynamically by the live WPLM store
(https://amolnovin.ir/wplm-store/?product=<slug>) so cards link there.  No prices are published -> no price shown."""

STORE_URL = "https://amolnovin.ir/wplm-store/?product="

CATS = [("all", "همه"), ("woo", "ووکامرس و فروشگاه"), ("security", "امنیت و سرعت"), ("notify", "پیامک و اعلان"), ("manage", "مدیریت و تحلیل"), ("special", "کسب‌وکارهای تخصصی")]

PRODUCTS = [
dict(slug="novin-security-suite", name="نوین سکیوریتی سوئیت", cat="security", licensed=True, img="security-suite", icon="shield-check",
     desc="مجموعه‌ی امنیتی وردپرس: فایروال، ورود امن، پایش تغییرات و گزارش رخدادها برای کاهش خطر هک."),
dict(slug="an-webp-converter", name="مبدل تصاویر آمل نوین", cat="security", licensed=True, img="webp-converter", icon="image",
     desc="تبدیل خودکار تصاویر وردپرس (JPG و PNG) به WebP برای سرعت بیشتر و بهبود Core Web Vitals."),
dict(slug="woo-product-filter-pro", name="فیلتر و نمایش پیشرفته‌ی محصولات ووکامرس", cat="woo", licensed=True, img="product-filter", icon="filter",
     desc="فیلتر قیمت، ویژگی و دسته‌بندی بدون بارگذاری مجدد صفحه و نمایش زیبای محصولات."),
dict(slug="woo-novin-checkout", name="سفارشی‌ساز پرداخت و حساب کاربری ووکامرس", cat="woo", licensed=True, img="checkout-customizer", icon="credit-card",
     desc="سفارشی‌سازی صفحه‌ی تسویه‌حساب و حساب کاربری ووکامرس با تجربه‌ی فارسی و ساده."),
dict(slug="amol-wallet", name="کیف پول آمل", cat="woo", licensed=True, img="wallet", icon="wallet",
     desc="کیف پول و اعتبار کاربران برای ووکامرس: شارژ، پرداخت از کیف پول و گزارش تراکنش‌ها."),
dict(slug="amol-gold-pricing", name="قیمت زنده‌ی طلا برای ووکامرس", cat="woo", licensed=True, img="gold-pricing", icon="coins",
     desc="قیمت‌گذاری خودکار محصولات طلا و جواهر بر اساس نرخ روز."),
dict(slug="investment-request-woo", name="درخواست سرمایه‌گذاری برای ووکامرس", cat="woo", licensed=False, img=None, icon="piggy-bank", ph=("#272869", "#13a89e"),
     desc="ثبت و مدیریت درخواست‌های سرمایه‌گذاری روی ووکامرس. این افزونه بدون نیاز به لایسنس ارائه می‌شود."),
dict(slug="wp-bale-eitaa-notifier", name="اعلان‌رسان بله، ایتا، تلگرام و واتساپ", cat="notify", licensed=True, img="bei-notifier", icon="bell-ring",
     desc="ارسال اعلان سفارش، فرم و رخدادهای سایت به پیام‌رسان‌های بله، ایتا، تلگرام و واتساپ."),
dict(slug="ippanel-core", name="هسته‌ی پیامک آیپی‌پنل", cat="notify", licensed=True, img=None, icon="message-square", ph=("#4b50b4", "#2ec7bb"),
     desc="اتصال وردپرس و ووکامرس به پنل پیامک IPPanel برای پیامک سفارش، OTP و اطلاع‌رسانی."),
dict(slug="wp-custom-auth-initial-plugin", name="ورود و ثبت‌نام با کد یک‌بارمصرف", cat="notify", licensed=True, img="login-register-otp", icon="fingerprint",
     desc="ورود و ثبت‌نام سریع با موبایل و پیامک تأیید، همراه با سفارشی‌سازی فرم‌ها."),
dict(slug="wplm-amolnovin-license-manager", name="مدیر لایسنس آمل نوین (WPLM)", cat="manage", licensed=False, img="license-manager", icon="key-round",
     desc="سامانه‌ی صدور و مدیریت لایسنس افزونه‌ها و قالب‌ها؛ بدون نیاز به لایسنس برای استفاده."),
dict(slug="novin-analytics", name="نوین آنالیتیکس", cat="manage", licensed=True, img=None, icon="chart-line", ph=("#0e1038", "#2f7cf6"),
     desc="گزارش آمار بازدید و رفتار کاربران داخل پیشخوان وردپرس، متناسب با نیازهای کسب‌وکار ایرانی."),
dict(slug="novin-gym-pro", name="نوین جیم پرو", cat="special", licensed=True, img="gym-pro", icon="dumbbell",
     desc="مدیریت باشگاه ورزشی: اعضا، اشتراک، حضور و غیاب با QR و گزارش‌های مالی."),
dict(slug="maneli-plant-selector", name="گیاه‌یاب هوشمند مانلی", cat="special", licensed=False, img="plant-selector", icon="sprout",
     desc="با چند پرسش درباره‌ی نور و فضا، گیاه مناسب را به مشتری پیشنهاد می‌دهد؛ ساخته‌شده برای فروشگاه گل و گیاه مانلی."),
]
BY_SLUG = {p["slug"]: p for p in PRODUCTS}
