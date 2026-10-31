/**
 * Amol Novin — structured data editor (admin).
 *
 * Turns the JSON document of a page / entity into a form: text fields, text areas, checkboxes, lists, tuples (rows)
 * and repeatable cards with reorder / remove / add. «sections» gets an «add section» menu built from the section kinds.
 * The hidden textarea (name="amol_json[...]") always holds the current JSON, so WordPress' own Save button is enough.
 */
(function () {
  'use strict';

  var CFG = window.AMOL_ADMIN || { kinds: {}, skeletons: {}, icons: [] };

  var LABELS = {
    kind: 'نوع', id: 'شناسه‌ی لینک (anchor)', nav: 'متن لینک ناوبری میان‌صفحه', alt: 'پس‌زمینه‌ی روشن', dark: 'پس‌زمینه‌ی تیره', center: 'وسط‌چین',
    eyebrow: 'برچسب بالای تیتر', title: 'عنوان', desc: 'توضیح کوتاه', text: 'متن', html: 'محتوای HTML', icon: 'آیکن', color: 'رنگ', href: 'لینک', cta: 'دکمه', cta2: 'دکمه‌ی دوم',
    primary: 'دکمه‌ی اصلی', secondary: 'دکمه‌ی دوم', items: 'آیتم‌ها', cols: 'تعداد ستون', slugs: 'نامک‌ها (slug)', points: 'نکته‌ها', points_title: 'عنوان کارت نکته‌ها', cls: 'کلاس چیدمان',
    mock: 'تصویر نمونه (slug نوع سایت)', img: 'مسیر تصویر', img_w: 'عرض تصویر', img_h: 'ارتفاع تصویر', img_alt: 'متن جایگزین تصویر', hero: 'بخش بالای صفحه (Hero)', chips: 'چیپ‌ها',
    meta: 'اطلاعات کوتاه', visual: 'تصویر Hero', lead: 'خلاصه‌ی زیر عنوان', tldr: 'خلاصه‌ی پاسخ', sections: 'بخش‌های صفحه', faq: 'پرسش‌های متداول', crumbs: 'مسیر صفحه (breadcrumb)',
    subnav: 'منوی میان‌صفحه', note: 'یادداشت', time: 'زمان', name: 'نام', price: 'قیمت', unit: 'واحد', features: 'امکانات', pop: 'پلن ویژه', flag: 'برچسب پلن', head: 'سرستون‌ها', rows: 'ردیف‌ها',
    hl: 'ستون برجسته (شماره از ۰)', n: 'عدد', suffix: 'پسوند', label: 'برچسب', small: 'متن کوچک', win: 'برنده', open_first: 'اولین مورد باز باشد', warn: 'هشدار', limit: 'حداکثر تعداد (۰ = همه)',
    link: 'لینک «مشاهده‌ی همه»', service: 'نوع درخواست پیش‌فرض', form_title: 'عنوان فرم', fid: 'شناسه‌ی فرم', cols_: 'ستون‌ها', priority: 'اولویت نقشه‌ی سایت', changefreq: 'تکرار به‌روزرسانی',
    short_title: 'عنوان کوتاه', cat_label: 'برچسب دسته در جست‌وجو', kws: 'کلمات کلیدی مرتبط', og: 'کلید تصویر OG', schema_type: 'نوع Schema', ld: 'گره‌های Schema (JSON-LD)', groups: 'گروه‌های نقشه‌ی سایت',
    slug: 'نامک', short: 'توضیح کوتاه', kw: 'کلمه‌ی کلیدی', audience: 'مخاطبان', intro: 'مقدمه (پاراگراف‌ها)', structure: 'ساختار صفحات (عنوان|توضیح)', tech: 'فناوری', seo: 'سئو', related: 'مرتبط',
    projects: 'نمونه‌کارها', plugins: 'افزونه‌ها', url: 'آدرس سایت', domain: 'دامنه', full: 'تصویر تمام‌صفحه', cat: 'دسته', type: 'نوع سایت', services: 'خدمات', start: 'شروع همکاری', detail: 'دارای صفحه‌ی اختصاصی',
    featured: 'ویژه', card: 'متن کارت', overview: 'معرفی', challenge: 'چالش', solution: 'راه‌حل', deliver: 'خروجی‌ها', outcome: 'نتیجه', licensed: 'نیازمند لایسنس', ph: 'رنگ‌های جایگزین تصویر', desc_: 'توضیح'
  };
  var TUPLES = {
    faq: ['پرسش', 'پاسخ'], cta: ['متن', 'لینک', 'کلاس دکمه'], primary: ['متن', 'لینک'], secondary: ['متن', 'لینک'], meta: ['آیکن', 'متن'], crumbs: ['عنوان', 'مسیر (خالی = بدون لینک)'],
    features: ['آیکن', 'عنوان', 'توضیح'], seo: ['عنوان', 'توضیح'], why: ['آیکن', 'عنوان', 'متن'], process: ['عنوان', 'متن'], tech: ['نام', 'آیکن', 'رنگ'], points_: [],
    nav_services: ['لینک', 'عنوان', 'توضیح', 'آیکن', 'رنگ'], visual: ['نوع (mock | img | html)', 'مقدار', 'متن جایگزین', 'عرض', 'ارتفاع']
  };
  var KIND_TUPLES = { faq: ['پرسش', 'پاسخ'], modules: ['عنوان', 'برچسب', 'نکته‌ها'], facts: ['آیکن', 'برچسب', 'مقدار'], timeline: ['تاریخ', 'عنوان', 'متن'], tech: ['نام', 'آیکن', 'رنگ'] };
  var LONG = { html: 1, text: 1, desc: 1, lead: 1, tldr: 1, note: 1, overview: 1, challenge: 1, solution: 1, outcome: 1, card: 1, intro: 1 };
  var SHORT = { id: 1, kind: 1, icon: 1, color: 1, href: 1, slug: 1, mock: 1, cls: 1, nav: 1, fid: 1, og: 1, img: 1, type: 1, cat: 1, unit: 1, suffix: 1, kw: 1, domain: 1, url: 1, start: 1 };

  function h(tag, attrs, kids) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'class') e.className = attrs[k]; else if (k === 'text') e.textContent = attrs[k]; else e.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) e.appendChild(c); });
    return e;
  }
  function isObj(v) { return v && typeof v === 'object' && !Array.isArray(v); }
  function isPrim(v) { return v === null || typeof v !== 'object'; }
  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  function blank(v) {
    if (typeof v === 'string') return '';
    if (typeof v === 'number') return 0;
    if (typeof v === 'boolean') return false;
    if (Array.isArray(v)) return v.length ? [blank(v[0])] : [];
    if (isObj(v)) { var o = {}; Object.keys(v).forEach(function (k) { o[k] = blank(v[k]); }); return o; }
    return '';
  }
  function label(key, ctx) {
    if (ctx && ctx.tupleLabels && typeof key === 'number') return ctx.tupleLabels[key] || ('#' + (key + 1));
    return LABELS[key] || key;
  }
  var iconList;
  function icons() {
    if (!iconList) {
      iconList = h('datalist', { id: 'amol-icon-list' });
      (CFG.icons || []).forEach(function (n) { iconList.appendChild(h('option', { value: n })); });
      document.body.appendChild(iconList);
    }
    return iconList;
  }

  // ---------------------------------------------------------------- widgets: every builder returns { el, get }
  function primitive(value, key, ctx, onChange) {
    var k = typeof key === 'string' ? key : (ctx && ctx.parentKey) || '';
    var input, get;
    if (typeof value === 'boolean') {
      input = h('input', { type: 'checkbox' }); input.checked = value;
      get = function () { return input.checked; };
      input.addEventListener('change', onChange);
      return { el: h('label', { class: 'amol-check' }, [input, h('span', { text: ' ' + (label(key, ctx) || '') })]), get: get, inline: true };
    }
    if (typeof value === 'number') {
      input = h('input', { type: 'number', step: 'any', class: 'small-text' }); input.value = value;
      get = function () { return input.value === '' ? 0 : Number(input.value); };
    } else {
      var str = value === null || value === undefined ? '' : String(value);
      var long = LONG[k] || str.length > 70 || /[\n<]/.test(str);
      if (long && !SHORT[k]) {
        input = h('textarea', { rows: Math.min(10, Math.max(3, Math.ceil(str.length / 90) + (str.split('\n').length - 1))), dir: 'auto' }); input.value = str;
      } else {
        input = h('input', { type: 'text', dir: /^[\x00-\x7f]*$/.test(str) && str ? 'ltr' : 'auto' }); input.value = str;
        if (k === 'icon' || (ctx && ctx.iconCell)) { input.setAttribute('list', 'amol-icon-list'); icons(); }
      }
      var orig = value;
      get = function () { return value === null && input.value === '' ? null : input.value; };
    }
    input.addEventListener('input', onChange);
    return { el: input, get: get };
  }

  function fieldWrap(key, ctx, w) {
    if (w.inline) return h('div', { class: 'amol-field amol-field--check' }, [w.el]);
    return h('div', { class: 'amol-field' }, [h('label', { class: 'amol-label', text: label(key, ctx) }), w.el]);
  }

  function objectBuilder(obj, ctx, onChange) {
    var box = h('div', { class: 'amol-obj' }), parts = [];
    Object.keys(obj).forEach(function (k) {
      var w = build(obj[k], { parentKey: k, parentKind: obj.kind || ctx.parentKind, key: k }, onChange, k);
      box.appendChild(fieldWrap(k, ctx, w));
      parts.push([k, w]);
    });
    return { el: box, get: function () { var o = {}; parts.forEach(function (p) { o[p[0]] = p[1].get(); }); return o; } };
  }

  function arrayBuilder(arr, key, ctx, onChange) {
    var wrap = h('div', { class: 'amol-arr' }), list = h('div', { class: 'amol-arr__list' }), footer = h('div', { class: 'amol-arr__foot' });
    var kindOfItems = arr.length ? (isObj(arr[0]) ? 'cards' : (Array.isArray(arr[0]) ? 'tuples' : 'list')) : null;
    var tupleLabels = KIND_TUPLES[ctx.parentKind] && key === 'items' ? KIND_TUPLES[ctx.parentKind] : (TUPLES[key] || null);
    var template = null, isSections = key === 'sections';
    if (!kindOfItems) {
      var sk = CFG.skeletons[ctx.parentKind] && CFG.skeletons[ctx.parentKind][key];
      if (sk && sk.length) { template = clone(sk[0]); kindOfItems = isObj(template) ? 'cards' : (Array.isArray(template) ? 'tuples' : 'list'); }
      else kindOfItems = (key === 'items' || key === 'cols' || key === 'sections') ? 'cards' : 'list';
    }
    if (!template && arr.length) template = blank(arr[0]);
    if (template === null || template === undefined) template = kindOfItems === 'cards' ? {} : (kindOfItems === 'tuples' ? ['', ''] : '');

    var rows = [];
    function summary(val, i) {
      if (isObj(val)) {
        var t = val.title || val.name || val.label || val.q || val.slug || '';
        return (val.kind && CFG.kinds[val.kind] ? '[' + CFG.kinds[val.kind] + '] ' : '') + (typeof t === 'string' ? t.replace(/<[^>]+>/g, '').slice(0, 60) : '') || ('#' + (i + 1));
      }
      if (Array.isArray(val)) { return (typeof val[0] === 'string' ? val[0].replace(/<[^>]+>/g, '').slice(0, 60) : '') || ('#' + (i + 1)); }
      return '';
    }
    function addRow(val, open) {
      var i = rows.length, row = h('div', { class: 'amol-row amol-row--' + kindOfItems });
      var childCtx = { parentKey: key, parentKind: isObj(val) ? (val.kind || ctx.parentKind) : ctx.parentKind, tupleLabels: tupleLabels, iconCell: false };
      var w;
      if (kindOfItems === 'cards') {
        w = build(val, childCtx, onChange, key + '[]');
        var det = h('details', { class: 'amol-card' }, [h('summary', { text: summary(val, i) }), w.el]);
        if (open) det.open = true;
        row.appendChild(det); row.det = det;
      } else if (kindOfItems === 'tuples') {
        var cells = [], inner = h('div', { class: 'amol-tuple' });
        val.forEach(function (c, ci) {
          var cctx = { parentKey: key, parentKind: ctx.parentKind, tupleLabels: tupleLabels, iconCell: (tupleLabels && /آیکن/.test(tupleLabels[ci] || '')) };
          var cw = Array.isArray(c) ? arrayBuilder(c, key + '_cell', cctx, onChange) : primitive(c, ci, cctx, onChange);
          inner.appendChild(h('div', { class: 'amol-field' }, [h('label', { class: 'amol-label', text: label(ci, cctx) }), cw.el]));
          cells.push(cw);
        });
        row.appendChild(inner); w = { get: function () { return cells.map(function (c) { return c.get(); }); } };
      } else {
        w = primitive(val, key, { parentKey: key }, onChange); row.appendChild(w.el);
      }
      var ctl = h('div', { class: 'amol-ctl' });
      [['↑', 'بالا', -1], ['↓', 'پایین', 1]].forEach(function (b) {
        var btn = h('button', { type: 'button', class: 'button button-small', title: b[1], 'aria-label': b[1], text: b[0] });
        btn.addEventListener('click', function () {
          var sib = b[2] < 0 ? row.previousElementSibling : row.nextElementSibling;
          if (sib) { list.insertBefore(row, b[2] < 0 ? sib : sib.nextSibling); onChange(); }
        });
        ctl.appendChild(btn);
      });
      var del = h('button', { type: 'button', class: 'button button-small amol-del', title: 'حذف', 'aria-label': 'حذف', text: '×' });
      del.addEventListener('click', function () { if (confirm('این مورد حذف شود؟')) { list.removeChild(row); rows = rows.filter(function (r) { return r.row !== row; }); onChange(); } });
      ctl.appendChild(del); row.appendChild(ctl);
      list.appendChild(row); rows.push({ row: row, w: w });
    }
    arr.forEach(function (v) { addRow(v, false); });
    wrap.appendChild(list);

    if (isSections) {
      var sel = h('select', { class: 'amol-kind-select' }, [h('option', { value: '', text: '— افزودن بخش جدید —' })]);
      Object.keys(CFG.kinds).forEach(function (k) { sel.appendChild(h('option', { value: k, text: CFG.kinds[k] })); });
      sel.addEventListener('change', function () {
        if (!sel.value) return;
        addRow(clone(CFG.skeletons[sel.value] || { kind: sel.value }), true); sel.value = ''; onChange();
      });
      footer.appendChild(sel);
    } else {
      var add = h('button', { type: 'button', class: 'button', text: '+ افزودن' });
      add.addEventListener('click', function () { addRow(clone(template), true); onChange(); });
      footer.appendChild(add);
    }
    wrap.appendChild(footer);
    return { el: wrap, get: function () { return Array.prototype.map.call(list.children, function (rowEl) { var r = rows.filter(function (x) { return x.row === rowEl; })[0]; return r ? r.w.get() : null; }).filter(function (v) { return v !== null || true; }); } };
  }

  function build(value, ctx, onChange, key) {
    if (Array.isArray(value)) return arrayBuilder(value, key, ctx, onChange);
    if (isObj(value)) return objectBuilder(value, ctx, onChange);
    return primitive(value, key, ctx, onChange);
  }

  // ---------------------------------------------------------------- mount
  function mount(root) {
    var hidden = root.querySelector('.amol-ed__json'), ui = root.querySelector('.amol-ed__ui');
    var data;
    try { data = JSON.parse(hidden.value || '{}'); } catch (e) { data = {}; ui.appendChild(h('p', { class: 'notice notice-error', text: 'JSON ذخیره‌شده نامعتبر است؛ ویرایش خام را باز کنید.' })); }
    var tree, raw = null, rawMode = false;

    function sync() { if (!rawMode && tree) hidden.value = JSON.stringify(tree.get()); }
    function render() {
      ui.innerHTML = '';
      tree = build(data, { parentKey: '', parentKind: data && data.kind }, sync, '');
      ui.appendChild(tree.el);
      sync();
    }
    var bar = h('div', { class: 'amol-ed__bar' });
    var toggle = h('button', { type: 'button', class: 'button button-small', text: 'ویرایش خام (JSON)' });
    toggle.addEventListener('click', function () {
      if (!rawMode) {
        raw = h('textarea', { class: 'amol-ed__raw', rows: 18, dir: 'ltr' }); raw.value = JSON.stringify(tree.get(), null, 2);
        ui.innerHTML = ''; ui.appendChild(raw); rawMode = true; toggle.textContent = 'بازگشت به فرم';
        raw.addEventListener('input', function () { hidden.value = raw.value; });
      } else {
        try { data = JSON.parse(raw.value); } catch (e) { alert('JSON نامعتبر است: ' + e.message); return; }
        rawMode = false; toggle.textContent = 'ویرایش خام (JSON)'; render();
      }
    });
    bar.appendChild(toggle);
    root.insertBefore(bar, ui);
    render();
  }

  function init() { document.querySelectorAll('.amol-ed').forEach(function (r) { if (!r.dataset.ready) { r.dataset.ready = '1'; mount(r); } }); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
