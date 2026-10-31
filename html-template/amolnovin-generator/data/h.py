# -*- coding: utf-8 -*-
"""tiny helpers for page definitions"""


def C(icon, title, text, href=None, color=None, cta=None):
    return dict(icon=icon, title=title, text=text, href=href, color=color, cta=cta)


def S(title, text, time=None):
    return dict(title=title, text=text, time=time)


HERO_CTA = [("دریافت مشاوره‌ی رایگان", "#consult", "btn--cta"), ("مشاهده‌ی نمونه‌کارها", "/portfolio/", "btn--light")]
HERO_CTA_PRICE = [("دریافت مشاوره‌ی رایگان", "#consult", "btn--cta"), ("هزینه طراحی سایت", "/price-wordpress/", "btn--light")]


def form(service="", title=None, **kw):
    d = dict(kind="form", service=service)
    if title:
        d["title"] = title
    d.update(kw)
    return d


def faq(items, **kw):
    d = dict(kind="faq", items=items, id=kw.pop("id", "faq"))
    d.update(kw)
    return d


def meta(*pairs):
    return list(pairs)


FEATURED = {"design": 17, "support": 7, "seo": 10}
