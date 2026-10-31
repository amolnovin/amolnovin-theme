# -*- coding: utf-8 -*-
"""Single source of truth for brand / contact data.  Edit here, rebuild, done."""
import datetime

BUILD_DATE = datetime.date(2026, 10, 15)       # Gregorian (= 23 Mehr 1405)

SITE = dict(
    name="فناوری اطلاعات آمل نوین",
    short="آمل نوین",
    tagline="طراحی سایت، سئو و پشتیبانی در آمل و مازندران",
    en_name="Amol Novin Information Technology",
    url="https://amolnovin.ir",
    lang="fa", locale="fa_IR",
    # --- contact (NAP) -------------------------------------------------------------
    phone="09332021365", phone_intl="+989332021365", phone_display="0933 202 1365",
    whatsapp="https://wa.me/989332021365",
    email="info@amolnovin.ir",
    street="خیابان طالب آملی، دریای ۳۲", city="آمل", region="مازندران", country="IR", country_name="ایران",
    address="مازندران، آمل، خیابان طالب آملی، دریای ۳۲",
    geo=(36.4696, 52.3507),                      # city-centre of Amol (approximate)
    maps_query="آمل، خیابان طالب آملی، دریای ۳۲",
    response_time="کمتر از ۲ ساعت",
    # --- social (leave empty -> not rendered) -------------------------------------
    social=dict(instagram="", telegram="", youtube="", x="", github="", eitaa="", bale=""),
    # --- business facts -----------------------------------------------------------
    founded_j=1390, founded=2011, years="۱۵", projects="۱۵۰",
    support_months=24,
    price_design=17, price_support=7, price_seo=10,      # million toman ("from")
    form_endpoint="api/lead.php",                        # relative to site root; falls back to WhatsApp
    theme_color="#272869",
    logo="assets/img/brand/avn_new_logo-512-512.webp",
    founder="ایمان حسین‌زاده",
    jalali_year=1405,
)

CITIES = ["آمل", "بابل", "بابلسر", "محمودآباد", "نور", "نوشهر", "چالوس", "رامسر", "ساری", "قائمشهر", "جویبار", "فریدون‌کنار", "تنکابن"]

TEAM = [
    dict(name="ایمان حسین‌زاده", role="مدیر مجموعه و توسعه‌دهنده‌ی ارشد", initials="ا", photo="team/iman-hosseinzadeh.webp",
         text="بیش از ۱۵ سال تجربه در طراحی و توسعه‌ی وب؛ معماری راهکارهای وردپرس، ووکامرس و کدنویسی اختصاصی."),
    dict(name="فائزه جعفری‌نسب", role="طراح و توسعه‌دهنده‌ی وب", initials="ف", photo=None,
         text="طراحی رابط کاربری و پیاده‌سازی صفحات با تمرکز بر تجربه‌ی کاربری و واکنش‌گرایی."),
    dict(name="غزل خالوندی", role="مدیر پروژه", initials="غ", photo=None,
         text="هماهنگی بین مشتری و تیم، زمان‌بندی تحویل و کنترل کیفیت مراحل پروژه."),
    dict(name="زهرا اسماعیلی", role="متخصص تولید محتوا و شبکه‌های اجتماعی", initials="ز", photo=None,
         text="تولید محتوای سئو شده و مدیریت حضور برندها در شبکه‌های اجتماعی."),
]

# ------------------------------------------------------------------ navigation
NAV_SERVICES = [
    ("/services/web-design/", "طراحی سایت", "۲۰ نوع سایت برای هر کسب‌وکار", "layout-template", "#3b3d99"),
    ("/wordpress-web-design/", "طراحی سایت وردپرس", "اختصاصی، سبک و سئو شده", "b-wordpress", "#2f7cf6"),
    ("/services/coding/", "طراحی سایت اختصاصی", "PHP، لاراول و پلتفرم‌های سفارشی", "code-xml", "#6d4aff"),
    ("/services/site-support/", "پشتیبانی سایت در آمل", "بروزرسانی، بکاپ و گزارش ماهانه", "life-buoy", "#0e958c"),
    ("/services/seo/", "سئو و تولید محتوا", "گوگل، GEO و محتوای سئو شده", "chart-line", "#e8662c"),
    ("/services/security/", "امنیت و سرعت سایت", "ضد هک، فایروال و Core Web Vitals", "shield-check", "#e5484d"),
    ("/services/computer-network/", "خدمات کامپیوتر و شبکه", "پشتیبانی سیستم و شبکه در آمل", "network", "#566275"),
    ("/wordpress-learn/", "آموزش وردپرس و هوش مصنوعی", "دوره‌ی حضوری ۳ ماهه", "graduation-cap", "#d98400"),
]
NAV_MORE = [
    ("/wordpress-learn/", "آموزش وردپرس", "graduation-cap"), ("/wplm-store/", "فروشگاه افزونه‌ها", "package"),
    ("/about-us/", "درباره‌ی ما", "building-2"), ("/resume/", "رزومه‌ی مجموعه", "file-text"), ("/contact-us/", "تماس با ما", "phone"),
]
