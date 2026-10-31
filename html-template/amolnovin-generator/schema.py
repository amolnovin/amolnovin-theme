# -*- coding: utf-8 -*-
"""JSON-LD graph builders (schema.org)."""
from config import SITE, BUILD_DATE
import util

URL = SITE["url"]
ORG_ID = f"{URL}/#organization"
SITE_ID = f"{URL}/#website"
IRR = 10   # toman -> rial


def absu(path):
    return URL + path


def organization():
    soc = [v for v in SITE["social"].values() if v]
    node = {
        "@type": ["ProfessionalService", "LocalBusiness"], "@id": ORG_ID,
        "name": SITE["name"], "alternateName": [SITE["short"], SITE["en_name"]],
        "url": URL + "/", "logo": {"@type": "ImageObject", "url": absu("/assets/img/brand/avn_new_logo-512-512.webp"), "width": 512, "height": 512},
        "image": absu("/assets/img/og/default.jpg"),
        "description": "فناوری اطلاعات آمل نوین؛ طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا و امنیت سایت در آمل، مازندران.",
        "telephone": SITE["phone_intl"], "email": SITE["email"],
        "address": {"@type": "PostalAddress", "streetAddress": SITE["street"], "addressLocality": SITE["city"], "addressRegion": SITE["region"], "addressCountry": SITE["country"]},
        "geo": {"@type": "GeoCoordinates", "latitude": SITE["geo"][0], "longitude": SITE["geo"][1]},
        "areaServed": [{"@type": "City", "name": "آمل"}, {"@type": "AdministrativeArea", "name": "مازندران"}, {"@type": "Country", "name": "ایران"}],
        "foundingDate": str(SITE["founded"]),
        "founder": {"@type": "Person", "name": SITE["founder"]},
        "priceRange": f"از {SITE['price_design']} میلیون تومان",
        "knowsAbout": ["طراحی سایت", "طراحی سایت وردپرس", "طراحی سایت اختصاصی", "پشتیبانی سایت", "سئو و تولید محتوا", "ووکامرس", "امنیت سایت وردپرس"],
        "contactPoint": [{"@type": "ContactPoint", "telephone": SITE["phone_intl"], "contactType": "customer service", "availableLanguage": ["fa"], "areaServed": "IR"}],
    }
    if soc:
        node["sameAs"] = soc
    return node


def website():
    return {"@type": "WebSite", "@id": SITE_ID, "url": URL + "/", "name": SITE["name"], "alternateName": SITE["short"], "inLanguage": "fa-IR", "publisher": {"@id": ORG_ID}}


def breadcrumb(page, canonical):
    items = [("خانه", "/")] + [(l, p) for l, p in page.get("crumbs", [])]
    els = []
    for i, (label, path) in enumerate(items, 1):
        el = {"@type": "ListItem", "position": i, "name": label}
        el["item"] = canonical if i == len(items) else absu(path or page["path"])
        els.append(el)
    return {"@type": "BreadcrumbList", "@id": canonical + "#breadcrumb", "itemListElement": els}


def webpage(page, canonical, og_image):
    t = page.get("schema_type", "WebPage")
    node = {"@type": t, "@id": canonical + "#webpage", "url": canonical, "name": page["title"], "description": page["desc"], "inLanguage": "fa-IR",
            "isPartOf": {"@id": SITE_ID}, "about": {"@id": ORG_ID}, "primaryImageOfPage": {"@type": "ImageObject", "url": og_image},
            "datePublished": page.get("published", "2026-10-15"), "dateModified": page.get("modified", BUILD_DATE.isoformat())}
    if page.get("path") != "/":
        node["breadcrumb"] = {"@id": canonical + "#breadcrumb"}
    return node


def faq(items, canonical):
    return {"@type": "FAQPage", "@id": canonical + "#faq", "mainEntity": [
        {"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": util.strip_tags(a.replace('href="@', 'href="'))}} for q, a in items]}


def service(name, desc, canonical, service_type, price_from=None, unit=None, area=True):
    node = {"@type": "Service", "@id": canonical + "#service", "name": name, "description": desc, "serviceType": service_type, "url": canonical,
            "provider": {"@id": ORG_ID}, "inLanguage": "fa-IR"}
    if area:
        node["areaServed"] = [{"@type": "City", "name": "آمل"}, {"@type": "AdministrativeArea", "name": "مازندران"}, {"@type": "Country", "name": "ایران"}]
    if price_from:
        node["offers"] = {"@type": "Offer", "priceCurrency": "IRR", "priceSpecification": {"@type": "PriceSpecification", "priceCurrency": "IRR", "minPrice": price_from * 1_000_000 * IRR},
                          "url": canonical, "availability": "https://schema.org/InStock"}
    return node


def offer_catalog():
    def off(name, desc, price, path):
        return {"@type": "Offer", "itemOffered": {"@type": "Service", "name": name, "description": desc, "url": absu(path)},
                "priceSpecification": {"@type": "PriceSpecification", "priceCurrency": "IRR", "minPrice": price * 1_000_000 * IRR}}
    return {"@type": "OfferCatalog", "name": "خدمات آمل نوین", "itemListElement": [
        off("طراحی سایت وردپرس", "طراحی سایت وردپرس اختصاصی، ریسپانسیو و سئو شده", SITE["price_design"], "/wordpress-web-design/"),
        off("پشتیبانی سایت", "پشتیبانی و نگهداری ماهانه‌ی سایت", SITE["price_support"], "/services/site-support/"),
        off("سئو و تولید محتوا", "برنامه‌ی سئو و تولید محتوای ماهانه", SITE["price_seo"], "/services/seo/")]}


def article(post, canonical, og_image):
    return {"@type": "BlogPosting", "@id": canonical + "#article", "headline": post["title"], "description": post["desc"], "inLanguage": "fa-IR",
            "mainEntityOfPage": {"@id": canonical + "#webpage"}, "image": [og_image], "datePublished": post["date"], "dateModified": post["date"],
            "author": {"@type": "Organization", "@id": ORG_ID, "name": SITE["name"]}, "publisher": {"@id": ORG_ID}, "articleSection": post["cat"], "wordCount": post.get("words", 0)}


def itemlist(name, items, canonical):
    return {"@type": "ItemList", "@id": canonical + "#list", "name": name, "numberOfItems": len(items),
            "itemListElement": [{"@type": "ListItem", "position": i, "url": u, "name": n} for i, (n, u) in enumerate(items, 1)]}


def graph(page, canonical, og_image):
    nodes = [organization(), website(), webpage(page, canonical, og_image)]
    if page["path"] != "/":
        nodes.append(breadcrumb(page, canonical))
    for extra in page.get("ld", []):
        nodes.append(extra)
    if page.get("faq") and page.get("faq_schema", True):
        nodes.append(faq(page["faq"], canonical))
    if page["path"] == "/":
        nodes[0]["hasOfferCatalog"] = offer_catalog()
    return {"@context": "https://schema.org", "@graph": util.fa_obj(nodes)}
