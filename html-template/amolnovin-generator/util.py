# -*- coding: utf-8 -*-
import re, json, html
from markupsafe import Markup

FA_DIGITS = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")
AR2FA = str.maketrans("٠١٢٣٤٥٦٧٨٩", "۰۱۲۳۴۵۶۷۸۹")
JMONTHS = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"]


# ---------------------------------------------------------------- Jalali
def g2j(gy, gm, gd):
    g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
    gy2 = gy + 1 if gm > 2 else gy
    days = 355666 + (365 * gy) + ((gy2 + 3) // 4) - ((gy2 + 99) // 100) + ((gy2 + 399) // 400) + gd + g_d_m[gm - 1]
    jy = -1595 + (33 * (days // 12053)); days %= 12053
    jy += 4 * (days // 1461); days %= 1461
    if days > 365:
        jy += (days - 1) // 365; days = (days - 1) % 365
    jm, jd = (1 + days // 31, 1 + days % 31) if days < 186 else (7 + (days - 186) // 30, 1 + (days - 186) % 30)
    return jy, jm, jd


def jdate(iso):
    y, m, d = map(int, iso.split("-"))
    jy, jm, jd = g2j(y, m, d)
    return f"{jd} {JMONTHS[jm - 1]} {jy}"


# ---------------------------------------------------------------- digits
def fa(s):
    return str(s).translate(FA_DIGITS)


_DIG = re.compile(r"(?<![A-Za-z0-9])[0-9]+(?:[.,/:\-][0-9]+)*(?![A-Za-z])")
_TAG = re.compile(r"(<!--.*?-->|<[^>]+>)", re.S)
_SKIP = {"script", "style", "svg", "textarea", "code", "pre", "kbd"}
_ATTR = re.compile(r'(\s(?:alt|title|aria-label)=")([^"]*)(")')
_META = re.compile(r'<meta\s+(?:name|property)="(?:description|og:title|og:description|og:image:alt|og:site_name|twitter:title|twitter:description|twitter:image:alt)"')


def _conv_text(t):
    return _DIG.sub(lambda m: m.group(0).translate(FA_DIGITS), t)


def fa_digits_html(doc):
    out, skip = [], 0
    for part in _TAG.split(doc):
        if not part:
            continue
        if part.startswith("<!--"):
            out.append(part); continue
        if part.startswith("<"):
            m = re.match(r"<(/?)([a-zA-Z0-9]+)", part)
            if m:
                name, closing = m.group(2).lower(), bool(m.group(1))
                if name in _SKIP:
                    if closing: skip = max(0, skip - 1)
                    elif not part.rstrip().endswith("/>"): skip += 1
                if not skip or name in _SKIP:
                    part = _ATTR.sub(lambda a: a.group(1) + _conv_text(a.group(2)) + a.group(3), part)
                    if name == "meta" and _META.match(part):
                        part = re.sub(r'(content=")([^"]*)(")', lambda a: a.group(1) + _conv_text(a.group(2)) + a.group(3), part)
            out.append(part)
        else:
            out.append(part if skip else _conv_text(part))
    return "".join(out)


# ---------------------------------------------------------------- urls
def rel(from_path, to_path):
    """Relative URL from page `from_path` ('/a/b/') to `to_path` ('/x/y/' or '/assets/f.css')."""
    frm = [s for s in from_path.strip("/").split("/") if s]
    anchor = ""
    if "#" in to_path:
        to_path, anchor = to_path.split("#", 1); anchor = "#" + anchor
    query = ""
    if "?" in to_path:
        to_path, query = to_path.split("?", 1); query = "?" + query
    is_dir = to_path.endswith("/")
    to = [s for s in to_path.strip("/").split("/") if s]
    n = 0
    while n < len(frm) and n < len(to) and frm[n] == to[n]:
        n += 1
    ups = len(frm) - n
    path = "../" * ups + "/".join(to[n:])
    if is_dir and to[n:]:
        path += "/"
    if not path:
        path = "./"
    return path + query + anchor


def root_prefix(from_path):
    return rel(from_path, "/")


# ---------------------------------------------------------------- text
def strip_tags(h):
    return html.unescape(re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", h))).strip()


def read_minutes(h, wpm=170):
    words = len(strip_tags(h).split())
    return max(1, round(words / wpm))


def slug_id(text, i=0):
    return f"s{i}"


def ld(data):
    """JSON-LD string safe for <script>."""
    return Markup(json.dumps(data, ensure_ascii=False, separators=(",", ":")).replace("<", "\\u003c").replace(">", "\\u003e").replace("&", "\\u0026"))


def fa_obj(o):
    """convert digits inside all strings of a JSON-LD-ish structure (except url-like / numeric fields)"""
    if isinstance(o, str):
        return o if o.startswith(("http", "tel:", "mailto:", "+", "@")) or re.fullmatch(r"[\d\-:T+Z.]+", o) else _conv_text(o)
    if isinstance(o, list):
        return [fa_obj(x) for x in o]
    if isinstance(o, dict):
        return {k: (v if k in ("@id", "@type", "url", "telephone", "email", "datePublished", "dateModified", "price", "minPrice", "priceCurrency", "image", "logo", "sameAs", "@context", "inLanguage", "latitude", "longitude", "item", "contentUrl") else fa_obj(v)) for k, v in o.items()}
    return o
