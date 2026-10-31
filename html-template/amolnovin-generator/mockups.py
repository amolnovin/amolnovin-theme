"""Procedural SVG website mock-ups (one per website type) + UI patterns.
All shapes, no text -> safe to use as <img src="*.svg">.  Palette = brand navy / teal / amber + a per-type accent."""
import json, os, random

ROOT = os.path.dirname(os.path.abspath(__file__))
ICONS = json.load(open(f"{ROOT}/data/icons.json"))

INK, MUTED, SOFT, LINE = "#1a1d4a", "#dfe3f4", "#f1f3fb", "#e3e6f3"
TEAL, NAVY, AMBER = "#13a89e", "#272869", "#ffb21f"


def mix(c, w=0.85, base="#ffffff"):
    c, b = c.lstrip("#"), base.lstrip("#")
    r = [round(int(c[i:i + 2], 16) * (1 - w) + int(b[i:i + 2], 16) * w) for i in (0, 2, 4)]
    return "#%02x%02x%02x" % tuple(r)


def R(x, y, w, h, fill=MUTED, rx=8, op=1, stroke=None, sw=1):
    s = f' stroke="{stroke}" stroke-width="{sw}"' if stroke else ""
    o = f' opacity="{op}"' if op != 1 else ""
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill}"{o}{s}/>'


def C(cx, cy, r, fill=MUTED, op=1, stroke=None, sw=1):
    s = f' stroke="{stroke}" stroke-width="{sw}"' if stroke else ""
    o = f' opacity="{op}"' if op != 1 else ""
    return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{fill}"{o}{s}/>'


def P(points, fill=MUTED, op=1):
    pts = " ".join(f"{x},{y}" for x, y in points)
    o = f' opacity="{op}"' if op != 1 else ""
    return f'<polygon points="{pts}" fill="{fill}"{o}/>'


def LN(x, y, w, h=7, fill=MUTED):           # a "text line" bar (RTL: anchored at its right edge by caller)
    return R(x, y, w, h, fill, rx=h / 2)


def lines(xr, y, widths, gap=14, h=7, fill=MUTED):   # xr = right edge
    return "".join(LN(xr - w, y + i * gap, w, h, fill) for i, w in enumerate(widths))


def IC(name, x, y, size=24, color=INK, sw=2):
    ic = ICONS.get(name)
    if not ic:
        return ""
    s = size / 24
    return (f'<g transform="translate({x},{y}) scale({s:.3f})" fill="none" stroke="{color}" stroke-width="{sw}" '
            f'stroke-linecap="round" stroke-linejoin="round">{ic["body"]}</g>')


def GRAD(id_, c1, c2, x2="1", y2="1"):
    return f'<linearGradient id="{id_}" x1="0" y1="0" x2="{x2}" y2="{y2}"><stop offset="0" stop-color="{c1}"/><stop offset="1" stop-color="{c2}"/></linearGradient>'


def nav(A, btn=AMBER):
    return (R(560, 60, 44, 14, A, 7) + R(498, 63, 40, 8, MUTED, 4) + R(448, 63, 40, 8, MUTED, 4) + R(398, 63, 40, 8, MUTED, 4)
            + R(28, 58, 76, 22, btn, 8))


def card(x, y, w, h, A, icon=None, fill="#fff", lw=(0.7, 0.45)):
    s = R(x, y, w, h, fill, 14, stroke=LINE)
    if icon:
        s += R(x + w - 46, y + 12, 32, 32, mix(A, .82), 10) + IC(icon, x + w - 41, y + 17, 22, A)
    s += lines(x + w - 14, y + (54 if icon else 16), [w * lw[0] - 14, w * lw[1] - 14], 12, 6)
    return s


# ---------------------------------------------------------------- layouts (content area y 96..420)
def L_corporate(A):
    return (nav(A) + lines(604, 108, [250, 200, 150], 22, 12, INK) + lines(604, 188, [270, 230], 14, 7)
            + R(504, 232, 100, 30, A, 10) + R(396, 232, 98, 30, "none", 10, stroke=A, sw=2)
            + R(28, 104, 280, 172, f"url(#g1)", 18) + IC("building-2", 118, 148, 100, "#fff", 1.4)
            + card(28 + 0, 300, 186, 104, A, "briefcase") + card(227, 300, 186, 104, A, "users") + card(426, 300, 186, 104, A, "trending-up"))


def L_ecommerce(A):
    s = nav(A, A) + R(28, 100, 584, 96, "url(#g1)", 18) + C(120, 148, 40, "#fff", .18) + IC("percent", 98, 126, 44, "#fff", 2)
    s += lines(580, 124, [200, 150], 22, 12, "#fff") + R(480, 168, 100, 18, AMBER, 9)
    for i in range(4):
        x = 28 + i * 148
        s += R(x, 212, 136, 196, "#fff", 14, stroke=LINE) + R(x + 8, 220, 120, 96, mix([A, TEAL, NAVY, AMBER][i], .8), 10)
        s += C(x + 68, 268, 26, mix([A, TEAL, NAVY, AMBER][i], .35), .55) + lines(x + 128, 328, [96, 60], 14, 7)
        s += R(x + 76, 372, 52, 24, A if i % 2 == 0 else AMBER, 8) + R(x + 8, 372, 28, 24, SOFT, 8) + IC("shopping-cart", x + 12, 376, 16, INK)
    return s


def L_educational(A):
    s = nav(A) + R(28, 100, 330, 188, "url(#g1)", 18) + C(193, 194, 34, "#fff", .22) + P([(182, 178), (182, 210), (210, 194)], "#fff")
    s += R(44, 262, 298, 8, "#fff", 4, .3) + R(44, 262, 190, 8, AMBER, 4)
    s += lines(604, 110, [220, 170], 22, 12, INK) + lines(604, 168, [240, 200, 150], 14, 7)
    for i in range(3):
        s += R(378 + 0, 220 + i * 0, 0, 0) if False else ""
    s += R(378, 236, 234, 52, "#fff", 12, stroke=LINE) + IC("circle-check", 576, 250, 24, TEAL) + lines(566, 252, [120], 0, 8)
    for i in range(3):
        x = 28 + i * 200
        s += R(x, 306, 184, 104, "#fff", 14, stroke=LINE) + R(x + 10, 316, 164, 40, mix([A, TEAL, NAVY][i], .78), 10)
        s += IC("book-open", x + 80, 324, 24, [A, TEAL, NAVY][i]) + lines(x + 174, 366, [120, 80], 13, 6) + R(x + 10, 392, 164, 6, MUTED, 3) + R(x + 10, 392, 40 + i * 50, 6, TEAL, 3)
    return s


def L_medical(A):
    s = nav(A) + R(28, 100, 584, 150, mix(A, .88), 18) + C(520, 175, 56, "#fff") + C(520, 175, 48, mix(A, .6)) + IC("stethoscope", 494, 149, 52, "#fff", 1.7)
    s += lines(440, 124, [260, 200, 160], 24, 12, INK) + R(240, 210, 110, 28, A, 10) + R(28 + 20, 112, 150, 126, "#fff", 16, stroke=LINE)
    for r in range(3):
        for c in range(7):
            s += C(66 + c * 17, 150 + r * 24, 5, A if (r, c) == (1, 3) else MUTED)
    s += R(48, 214, 110, 16, A, 8)
    for i, ic in enumerate(["heart-pulse", "pill", "syringe", "microscope"]):
        x = 28 + i * 148
        s += R(x, 270, 136, 138, "#fff", 14, stroke=LINE) + R(x + 52, 284, 40, 40, mix(A, .82), 13) + IC(ic, x + 60, 292, 24, A) + lines(x + 108, 342, [80, 56], 14, 7) + R(x + 24, 384, 88, 12, SOFT, 6)
    return s


def L_lawyer(A):
    s = nav(A) + R(28, 100, 584, 164, "url(#g2)", 18) + IC("scale", 468, 120, 112, "#fff", 1.2)
    s += lines(430, 126, [250, 200, 140], 24, 12, "#fff") + R(250, 210, 120, 30, AMBER, 10) + R(28 + 20, 214, 0, 0, "none")
    for i, ic in enumerate(["gavel", "file-signature", "landmark", "handshake"]):
        x = 28 + i * 148
        s += card(x, 284, 136, 124, A, ic)
    return s


def L_realestate(A):
    s = nav(A) + R(28, 96, 584, 58, "#fff", 29, stroke=LINE) + R(40, 108, 84, 34, A, 17) + IC("search", 66, 117, 18, "#fff")
    for i in range(3):
        s += R(148 + i * 138, 112, 118, 26, SOFT, 13)
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 172, 186, 238, "#fff", 16, stroke=LINE) + R(x + 8, 180, 170, 112, mix([A, TEAL, NAVY][i], .8), 12)
        s += P([(x + 40, 262), (x + 93, 206), (x + 146, 262)], [A, TEAL, NAVY][i], .85) + R(x + 52, 262, 82, 24, mix([A, TEAL, NAVY][i], .35), 4, .8)
        s += R(x + 84, 270, 18, 16, "#fff", 3, .9) + lines(x + 176, 306, [120, 80], 14, 7) + R(x + 12, 366, 74, 24, A, 8) + R(x + 96, 366, 78, 24, SOFT, 8)
    return s


def L_restaurant(A):
    s = nav(A) + R(28, 100, 584, 160, mix(A, .9), 18) + C(176, 180, 66, "#fff") + C(176, 180, 52, mix(A, .55)) + C(176, 180, 30, "#fff", .9) + IC("utensils", 156, 160, 40, A, 1.8)
    s += lines(604, 122, [250, 200, 140], 24, 12, INK) + R(504, 214, 100, 28, A, 10)
    for i in range(4):
        x = 28 + (i % 2) * 296
        y = 280 + (i // 2) * 66
        s += R(x, y, 284, 54, "#fff", 12, stroke=LINE) + C(x + 258, y + 27, 18, mix([A, TEAL, AMBER, NAVY][i], .6)) + lines(x + 226, y + 14, [120, 76], 16, 7) + R(x + 14, y + 17, 44, 20, mix(A, .85), 8)
    return s


def L_hotel(A):
    s = nav(A) + R(28, 100, 584, 212, "url(#g1)", 18) + C(500, 160, 28, AMBER) + P([(28, 312), (150, 200), (250, 270), (350, 190), (480, 312)], "#fff", .22) + P([(180, 312), (300, 230), (420, 312)], "#fff", .3)
    s += R(28, 280, 584, 32, "#fff", 0, .25) + lines(590, 122, [220, 160], 24, 12, "#fff")
    s += R(60, 296, 520, 60, "#fff", 18, stroke=LINE) + R(70, 310, 96, 32, A, 12)
    for i in range(3):
        s += R(190 + i * 130, 312, 112, 28, SOFT, 10)
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 376, 186, 40, "#fff", 12, stroke=LINE) + IC("bed-double", x + 150, 386, 20, [A, TEAL, NAVY][i]) + lines(x + 138, 388, [80], 0, 7)
    return s


def L_travel(A):
    s = nav(A) + R(28, 100, 330, 200, mix(A, .9), 18) + "".join(R(44 + i * 52, 118, 36, 164, mix(A, .8), 8, .6) for i in range(6))
    s += '<path d="M60 260 C120 160 190 280 250 180 S320 150 336 130" fill="none" stroke="%s" stroke-width="3" stroke-dasharray="2 8" stroke-linecap="round"/>' % A
    s += C(60, 260, 9, TEAL) + C(336, 130, 11, AMBER) + IC("plane", 238, 150, 34, A, 1.8)
    s += lines(604, 114, [220, 170, 130], 24, 12, INK) + lines(604, 190, [240, 190], 14, 7) + R(504, 240, 100, 30, A, 10)
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 316, 186, 96, "#fff", 14, stroke=LINE) + R(x + 8, 324, 56, 56, mix([A, TEAL, NAVY][i], .72), 12) + IC("map-pin", x + 24, 340, 24, [A, TEAL, NAVY][i]) + lines(x + 176, 330, [104, 70], 14, 7) + R(x + 90, 384, 80, 18, AMBER, 9)
    return s


def L_news(A):
    s = nav(A) + R(28, 94, 584, 22, mix(A, .9), 11) + R(548, 98, 56, 14, A, 7) + lines(534, 102, [220], 0, 6)
    s += R(260, 128, 352, 172, "url(#g1)", 16) + C(436, 200, 38, "#fff", .2) + IC("newspaper", 410, 174, 52, "#fff", 1.6) + lines(596, 262, [260, 180], 16, 9, "#fff")
    for i in range(4):
        y = 128 + i * 44
        s += R(28, y, 220, 38, "#fff", 10, stroke=LINE) + R(34, y + 5, 42, 28, mix([A, TEAL, NAVY, AMBER][i], .7), 7) + lines(236, y + 9, [130, 90], 12, 6)
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 316, 186, 94, "#fff", 14, stroke=LINE) + R(x + 8, 324, 170, 38, mix([A, TEAL, NAVY][i], .78), 10) + lines(x + 176, 372, [140, 100], 13, 6)
    return s


def L_personal(A):
    s = nav(A) + R(28, 100, 230, 310, "#fff", 18, stroke=LINE) + C(143, 168, 46, "url(#g1)") + IC("user", 119, 144, 48, "#fff", 1.6)
    s += lines(200, 232, [110, 80], 16, 9, INK) + "".join(C(100 + i * 30, 290, 11, mix([A, TEAL, NAVY, AMBER][i], .6)) for i in range(4))
    for i, w in enumerate([160, 130, 180]):
        s += R(48, 326 + i * 22, 190, 8, MUTED, 4) + R(48, 326 + i * 22, w, 8, [A, TEAL, AMBER][i], 4)
    s += lines(604, 108, [300, 220], 22, 12, INK)
    for i in range(6):
        x = 276 + (i % 3) * 114
        y = 150 + (i // 3) * 132
        s += R(x, y, 104, 120, mix([A, TEAL, NAVY, AMBER, A, TEAL][i], .8), 14, stroke=LINE) + IC(["palette", "camera", "code-xml", "pen-tool", "image", "megaphone"][i], x + 38, y + 38, 28, [A, TEAL, NAVY, AMBER, A, TEAL][i])
    return s


def L_gym(A):
    s = nav(A, A) + R(28, 100, 584, 150, "url(#g2)", 18) + IC("dumbbell", 60, 120, 110, AMBER, 1.3) + lines(604, 126, [240, 190, 140], 24, 12, "#fff") + R(504, 206, 100, 28, AMBER, 10)
    for r in range(4):
        for c in range(6):
            on = (r * 3 + c * 2) % 5 == 0
            s += R(52 + c * 56, 270 + r * 32, 48, 24, A if on else SOFT, 8, .95 if on else 1)
    s += R(404, 266, 208, 140, "#fff", 16, stroke=LINE) + IC("flame", 548, 280, 36, AMBER, 1.8) + lines(596, 330, [140, 100], 16, 8) + R(420, 372, 176, 24, A, 10)
    return s


def L_industrial(A):
    s = nav(A) + R(28, 100, 584, 140, "url(#g2)", 18) + IC("factory", 56, 118, 104, "#fff", 1.3) + IC("cog", 156, 150, 56, AMBER, 1.5)
    s += lines(604, 124, [250, 200, 150], 22, 12, "#fff") + R(504, 202, 100, 28, AMBER, 10)
    for i in range(6):
        x = 28 + (i % 3) * 198
        y = 256 + (i // 3) * 82
        s += R(x, y, 186, 72, "#fff", 12, stroke=LINE) + R(x + 120, y + 10, 52, 52, mix([A, TEAL, NAVY][i % 3], .78), 10) + IC(["settings", "wrench", "box", "boxes", "hammer", "truck"][i], x + 132, y + 22, 28, [A, TEAL, NAVY][i % 3]) + lines(x + 108, y + 16, [80, 56], 16, 7)
    return s


def L_service(A):
    s = nav(A) + lines(604, 108, [260, 200], 22, 12, INK) + lines(604, 160, [280, 220, 170], 14, 7) + R(504, 236, 100, 30, A, 10)
    for i, ic in enumerate(["wrench", "sparkles", "truck", "shield-check", "zap", "headset"]):
        x = 28 + (i % 3) * 92
        y = 104 + (i // 3) * 86
        s += R(x, y, 84, 78, "#fff", 14, stroke=LINE) + R(x + 26, y + 10, 32, 32, mix(A, .82), 10) + IC(ic, x + 32, y + 16, 20, A) + R(x + 14, y + 54, 56, 7, MUTED, 4)
    s += R(28, 286, 584, 124, "#fff", 18, stroke=LINE)
    for i in range(3):
        s += R(48 + i * 190, 306, 170, 38, SOFT, 11)
    s += R(48, 360, 360, 34, A, 12) + C(560, 356, 26, "#25d366") + IC("phone-call", 548, 344, 24, "#fff", 2)
    return s


def L_religious(A):
    s = nav(A) + R(28, 100, 584, 150, "url(#g2)", 18) + IC("landmark", 56, 118, 110, "#fff", 1.2) + C(540, 140, 8, AMBER) + C(500, 168, 5, "#fff", .6)
    s += lines(604, 130, [240, 190, 140], 24, 12, "#fff") + R(504, 210, 100, 28, AMBER, 10)
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 268, 186, 70, "#fff", 12, stroke=LINE) + R(x + 126, 278, 48, 50, mix(A, .82), 10) + lines(x + 108, 284, [80, 56], 16, 7)
    s += R(28, 352, 584, 58, "#fff", 14, stroke=LINE) + IC("hand-heart", 560, 365, 32, A, 1.7) + R(48, 372, 440, 10, MUTED, 5) + R(48, 372, 300, 10, TEAL, 5) + R(48, 392, 90, 12, AMBER, 6)
    return s


def L_institute(A):
    s = nav(A) + lines(604, 108, [250, 190], 22, 12, INK) + lines(604, 160, [270, 210], 14, 7) + R(504, 214, 100, 30, A, 10)
    s += R(28, 100, 320, 160, mix(A, .88), 18) + IC("graduation-cap", 118, 124, 100, A, 1.4)
    for i in range(4):
        s += C(76 + i * 70, 300, 24, mix([A, TEAL, NAVY, AMBER][i], .6)) + IC("user", 66 + i * 70, 290, 20, "#fff")
    for i in range(3):
        x = 28 + i * 198
        s += R(x, 338, 186, 72, "#fff", 12, stroke=LINE) + IC("calendar-days", x + 144, 350, 24, [A, TEAL, NAVY][i]) + lines(x + 134, 352, [90, 60], 14, 7) + R(x + 12, 382, 70, 18, [A, TEAL, NAVY][i], 9)
    return s


def L_beauty(A):
    s = nav(A, A) + "".join(R(28 + (i % 2) * 150, 100 + (i // 2) * 120, 142, 112, mix([A, TEAL, AMBER, NAVY][i], .72), 16) + C(99 + (i % 2) * 150, 156 + (i // 2) * 120, 26, "#fff", .7) + IC(["sparkles", "scissors", "heart", "wand-sparkles"][i], 87 + (i % 2) * 150, 144 + (i // 2) * 120, 24, [A, TEAL, AMBER, NAVY][i]) for i in range(4))
    s += lines(604, 110, [260, 200], 22, 12, INK) + lines(604, 160, [280, 220], 14, 7)
    s += R(336, 196, 276, 214, "#fff", 18, stroke=LINE) + lines(596, 214, [140], 0, 9, INK)
    for i in range(6):
        s += R(352 + (i % 3) * 86, 244 + (i // 3) * 36, 76, 28, A if i == 4 else SOFT, 10)
    s += lines(596, 330, [180, 130], 14, 7) + R(352, 372, 244, 28, A, 12)
    return s


def L_interior(A):
    s = nav(A) + "".join(R(28 + i * 74, 98, 62, 22, A if i == 0 else SOFT, 11) for i in range(5))
    tiles = [(28, 134, 190, 150), (228, 134, 130, 96), (368, 134, 244, 96), (228, 240, 190, 170), (28, 294, 190, 116), (428, 240, 184, 170)]
    cols = [A, "#c8a27a", TEAL, "#9aa0e6", AMBER, "#8b6f55"]
    for (x, y, w, h), c in zip(tiles, cols):
        s += R(x, y, w, h, mix(c, .55), 16) + R(x + 10, y + h - 30, w * .5, 8, "#fff", 4, .8) + R(x + 10, y + h - 16, w * .3, 6, "#fff", 3, .55)
    s += IC("ruler", 62, 170, 44, "#fff", 1.6) + IC("lamp-ceiling", 296, 172, 36, "#fff", 1.5) + IC("sofa", 480, 168, 52, "#fff", 1.5)
    return s


def L_landing(A):
    s = nav(A, A) + R(204, 102, 232, 24, mix(A, .85), 12) + lines(470, 140, [310, 240], 28, 16, INK) + lines(416, 204, [250, 190], 14, 7)
    s += R(206, 244, 130, 38, AMBER, 13) + R(346, 244, 100, 38, "none", 13, stroke=A, sw=2)
    for i, ic in enumerate(["zap", "shield-check", "rocket"]):
        x = 28 + i * 198
        s += R(x, 308, 186, 102, "#fff", 14, stroke=LINE) + R(x + 126, 320, 44, 44, mix(A, .82), 13) + IC(ic, x + 137, 331, 22, A) + lines(x + 116, 326, [80, 56], 16, 7) + R(x + 14, 376, 120, 12, SOFT, 6)
    return s


def L_agri(A):
    s = nav(A) + R(28, 100, 584, 136, "url(#g1)", 18) + IC("wheat", 56, 116, 100, "#fff", 1.3) + C(520, 150, 30, AMBER, .9) + IC("sprout", 160, 160, 50, "#fff", 1.5)
    s += lines(604, 124, [240, 190, 140], 22, 12, "#fff") + R(504, 196, 100, 28, AMBER, 10)
    for i in range(4):
        x = 28 + i * 148
        s += R(x, 254, 136, 156, "#fff", 14, stroke=LINE) + R(x + 8, 262, 120, 78, mix([A, AMBER, TEAL, NAVY][i], .78), 10) + C(x + 68, 301, 24, mix([A, AMBER, TEAL, NAVY][i], .35), .8) + IC(["leaf", "apple", "wheat", "tree-palm"][i], x + 56, 289, 24, "#fff", 1.8) + lines(x + 128, 350, [96, 60], 14, 7) + R(x + 12, 380, 112, 18, SOFT, 9)
    return s


LAYOUTS = {
    "corporate": L_corporate, "ecommerce": L_ecommerce, "educational": L_educational, "medical": L_medical, "lawyer": L_lawyer,
    "real-estate": L_realestate, "restaurant": L_restaurant, "hotel-villa": L_hotel, "travel-agency": L_travel, "news-magazine": L_news,
    "personal-portfolio": L_personal, "sports-gym": L_gym, "industrial": L_industrial, "service-business": L_service,
    "religious-charity": L_religious, "training-institute": L_institute, "beauty-salon": L_beauty, "interior-architecture": L_interior,
    "landing-page": L_landing, "agriculture": L_agri,
}


def mockup(slug, accent, uid=None):
    """Return a standalone SVG string for the website type `slug`."""
    uid = uid or slug
    fn = LAYOUTS.get(slug, L_corporate)
    body = fn(accent).replace("url(#g1)", f"url(#g1-{uid})").replace("url(#g2)", f"url(#g2-{uid})")
    defs = (f'<defs>{GRAD("g1-" + uid, accent, mix(accent, .35, NAVY))}{GRAD("g2-" + uid, NAVY, "#0e1038")}'
            f'<filter id="sh-{uid}" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="0" dy="14" stdDeviation="14" flood-color="#0a0b24" flood-opacity=".25"/></filter></defs>')
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 440" role="img" aria-hidden="true" focusable="false">{defs}'
            f'<rect x="8" y="8" width="624" height="424" rx="22" fill="#fff" stroke="{LINE}" filter="url(#sh-{uid})"/>'
            f'<path d="M8 30a22 22 0 0 1 22-22h580a22 22 0 0 1 22 22v18H8z" fill="{SOFT}"/>'
            f'<circle cx="32" cy="30" r="5" fill="#ff6b6b"/><circle cx="50" cy="30" r="5" fill="#ffc247"/><circle cx="68" cy="30" r="5" fill="#3ddc97"/>'
            f'<rect x="200" y="20" width="240" height="20" rx="10" fill="#fff" stroke="{LINE}"/><circle cx="216" cy="30" r="4" fill="{TEAL}"/><rect x="228" y="27" width="120" height="6" rx="3" fill="{MUTED}"/>'
            f'<g transform="translate(0,-4)">{body}</g></svg>')


# ---------------------------------------------------------------- UI patterns
def circuit_svg(seed=7, size=560, color="#2ec7bb"):
    rnd = random.Random(seed)
    g = 14
    out = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 {size} {size}" fill="none">']
    n = size // g
    dirs = [(1, 0), (-1, 0), (0, 1), (0, -1), (1, 1), (-1, 1), (1, -1), (-1, -1)]
    used = set()
    for _ in range(26):
        x, y = rnd.randint(3, n - 4), rnd.randint(3, n - 4)
        pts = [(x, y)]
        d = rnd.choice(dirs)
        ok = True
        for _s in range(rnd.randint(3, 5)):
            ln = rnd.randint(2, 6)
            for _k in range(ln):
                nx, ny = x + d[0], y + d[1]
                if not (2 <= nx <= n - 3 and 2 <= ny <= n - 3) or (nx, ny) in used:
                    ok = False
                    break
                x, y = nx, ny
                used.add((x, y))
            pts.append((x, y))
            nd = rnd.choice([dd for dd in dirs if dd != d and dd != (-d[0], -d[1])])
            d = nd
            if not ok:
                break
        if len(pts) < 3:
            continue
        path = "M" + " L".join(f"{px * g} {py * g}" for px, py in pts)
        out.append(f'<path d="{path}" stroke="{color}" stroke-opacity=".28" stroke-width="1.3" stroke-linejoin="round"/>')
        sx, sy = pts[0]
        ex, ey = pts[-1]
        out.append(f'<circle cx="{sx * g}" cy="{sy * g}" r="2.4" fill="{color}" fill-opacity=".55"/>')
        out.append(f'<circle cx="{ex * g}" cy="{ey * g}" r="4" stroke="{color}" stroke-opacity=".5" stroke-width="1.3"/>')
    out.append("</svg>")
    return "".join(out)


def map_pattern_svg(size=420):
    rnd = random.Random(3)
    s = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 {size} {size}" fill="none">']
    for i in range(7):
        y = 30 + i * 58 + rnd.randint(-8, 8)
        s.append(f'<path d="M-10 {y} C {size*.25} {y-34}, {size*.6} {y+40}, {size+10} {y-6}" stroke="#13a89e" stroke-opacity=".16" stroke-width="{rnd.choice([2, 3, 6])}" stroke-linecap="round"/>')
    for i in range(6):
        x = 20 + i * 70 + rnd.randint(-8, 8)
        s.append(f'<path d="M{x} -10 C {x+30} {size*.3}, {x-30} {size*.7}, {x+10} {size+10}" stroke="#272869" stroke-opacity=".10" stroke-width="{rnd.choice([2, 4])}" stroke-linecap="round"/>')
    for _ in range(14):
        x, y, w, h = rnd.randint(0, size - 60), rnd.randint(0, size - 40), rnd.randint(24, 56), rnd.randint(16, 36)
        s.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="#272869" fill-opacity=".05"/>')
    s.append("</svg>")
    return "".join(s)


if __name__ == "__main__":
    out = f"{ROOT}/static/img/ui"
    os.makedirs(out, exist_ok=True)
    open(f"{out}/circuit.svg", "w").write(circuit_svg())
    open(f"{out}/map-pattern.svg", "w").write(map_pattern_svg())
    print("ui patterns written")
