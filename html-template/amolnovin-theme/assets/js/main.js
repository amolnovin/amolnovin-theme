/* ==========================================================================
   Amol Novin — main.js  (vanilla, no dependencies, progressive enhancement)
   Theme · header · mega menu · drawer · reveal · counters · rotator · forms ·
   calculator · filters · TOC · search palette · lightbox · map · chooser
   ========================================================================== */
(function () {
  'use strict';
  var d = document, w = window, root = d.documentElement;
  var $ = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };
  var on = function (el, ev, fn, o) { el && el.addEventListener(ev, fn, o || false); };
  var BASE = root.getAttribute('data-root') || './';
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var toFa = function (s) { return String(s).replace(/[0-9]/g, function (x) { return FA[x]; }); };
  var norm = function (s) { return String(s).replace(/[۰-۹]/g, function (c) { return FA.indexOf(c); }).replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 1632; }); };
  var fmt = function (n) { return new Intl.NumberFormat('fa-IR').format(n); };
  var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- file:// support: make folder links open index.html ---------- */
  function fixFileLinks(scope) {
    if (location.protocol !== 'file:') return;
    $$('a[href]', scope).forEach(function (a) {
      var h = a.getAttribute('href');
      if (!h || /^(https?:|mailto:|tel:|javascript:|#)/.test(h) || /\.[a-z0-9]{2,5}([?#]|$)/i.test(h.split('#')[0])) return;
      a.setAttribute('href', h.replace(/\/(?=[?#]|$)/, '/index.html'));
    });
  }
  fixFileLinks(d);

  /* ---------- theme ---------- */
  on($('[data-theme-toggle]'), 'click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('avn-theme', next); } catch (e) {}
  });

  /* ---------- header, progress, to-top ---------- */
  var header = $('.site-header'), bar = $('.progress-bar span'), toTop = $('[data-to-top]');
  var article = $('[data-article]');
  function onScroll() {
    var y = w.scrollY || root.scrollTop;
    header && header.classList.toggle('is-stuck', y > 8);
    toTop && toTop.classList.toggle('is-show', y > 700);
    if (bar && article) {
      var r = article.getBoundingClientRect(), h = article.offsetHeight - w.innerHeight * .5;
      bar.style.width = Math.max(0, Math.min(100, (-r.top / Math.max(h, 1)) * 100)) + '%';
    }
  }
  on(w, 'scroll', onScroll, { passive: true }); onScroll();
  on(toTop, 'click', function () { w.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

  /* ---------- mega menus ---------- */
  var megas = $$('.has-mega'), closeT;
  function closeMegas(except) {
    megas.forEach(function (li) {
      if (li === except) return;
      li.removeAttribute('data-open');
      var b = $('button', li); b && b.setAttribute('aria-expanded', 'false');
    });
  }
  /* compact dropdowns (.mega--menu, e.g. «بیشتر») are centred under their own button; keep them fully inside the viewport */
  function place(li) {
    var p = $('.mega--menu .mega__panel', li); if (!p) return;
    p.style.translate = '';
    var r = p.getBoundingClientRect(), vw = d.documentElement.clientWidth, g = 12, s = 0;
    if (r.left < g) s = g - r.left; else if (r.right > vw - g) s = vw - g - r.right;
    if (s) p.style.translate = Math.round(s) + 'px 0';
  }
  megas.forEach(function (li) {
    var btn = $('button', li);
    function open() { clearTimeout(closeT); closeMegas(li); li.setAttribute('data-open', 'true'); btn.setAttribute('aria-expanded', 'true'); place(li); }
    function close() { li.removeAttribute('data-open'); btn.setAttribute('aria-expanded', 'false'); }
    on(btn, 'click', function (e) { e.stopPropagation(); var hov = w.matchMedia('(hover: hover)').matches; if (hov) { open(); } else { li.getAttribute('data-open') ? close() : open(); } });
    on(li, 'mouseenter', function (e) { if (w.matchMedia('(hover: hover)').matches) open(); });
    on(li, 'mouseleave', function () { if (w.matchMedia('(hover: hover)').matches) closeT = setTimeout(close, 160); });
    on(li, 'focusout', function (e) { if (!li.contains(e.relatedTarget)) close(); });
  });
  on(d, 'click', function (e) { if (!e.target.closest('.has-mega')) closeMegas(); });
  on(d, 'keydown', function (e) { if (e.key === 'Escape') { closeMegas(); closeDrawer(); closeSearch(); closeLightbox(); } });

  /* ---------- mobile drawer ---------- */
  var drawer = $('#drawer'), burger = $('[data-menu-open]'), lastFocus;
  function openDrawer() { lastFocus = d.activeElement; drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); burger.setAttribute('aria-expanded', 'true'); root.style.overflow = 'hidden'; setTimeout(function () { var c = $('[data-menu-close]', drawer.querySelector('.drawer__head')); c && c.focus(); }, 60); }
  function closeDrawer() { if (!drawer || !drawer.classList.contains('is-open')) return; drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); burger && burger.setAttribute('aria-expanded', 'false'); root.style.overflow = ''; lastFocus && lastFocus.focus && lastFocus.focus(); }
  on(burger, 'click', openDrawer);
  $$('[data-menu-close]').forEach(function (b) { on(b, 'click', closeDrawer); });
  $$('.drawer a').forEach(function (a) { on(a, 'click', closeDrawer); });

  /* ---------- reveal on scroll ---------- */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in w && !reduce) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
    reveals.forEach(function (el) { io.observe(el); });
  } else { reveals.forEach(function (el) { el.classList.add('is-in'); }); }

  /* ---------- counters ---------- */
  var counters = $$('[data-count]');
  function runCount(el) {
    var end = parseFloat(el.getAttribute('data-count')), suf = el.getAttribute('data-suffix') || '', t0 = null, dur = 1400;
    if (reduce || isNaN(end)) { el.textContent = fmt(end) + suf; return; }
    function step(t) { if (!t0) t0 = t; var p = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(Math.round(end * e)) + suf; if (p < 1) requestAnimationFrame(step); }
    requestAnimationFrame(step);
  }
  if ('IntersectionObserver' in w) {
    var cio = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { runCount(e.target); cio.unobserve(e.target); } }); }, { threshold: .4 });
    counters.forEach(function (c) { cio.observe(c); });
  }

  /* ---------- hero word rotator ---------- */
  $$('[data-rotate]').forEach(function (el) {
    var words; try { words = JSON.parse(el.getAttribute('data-rotate')); } catch (e) { return; }
    if (!words || words.length < 2 || reduce) return;
    var i = 0;
    setInterval(function () {
      i = (i + 1) % words.length;
      var out = el.animate([{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(-8px)' }], { duration: 220, fill: 'forwards' });
      out.onfinish = function () {
        el.textContent = toFa(words[i]);
        el.animate([{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 260, fill: 'forwards' });
      };
    }, 2400);
  });

  /* ---------- toast ---------- */
  var toastEl = $('#toast'), toastT;
  function toast(msg) { if (!toastEl) return; toastEl.textContent = msg; toastEl.classList.add('is-show'); clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-show'); }, 2200); }

  /* ---------- lead forms ---------- */
  $$('[data-lead-form]').forEach(function (form) {
    var status = $('.form-status', form), btn = $('button[type=submit]', form);
    function err(input, msg) { var f = input.closest('.field'), e = f && $('.err', f); input.setAttribute('aria-invalid', msg ? 'true' : 'false'); if (e) e.textContent = msg || ''; return !msg; }
    $$('input,textarea,select', form).forEach(function (i) { on(i, 'input', function () { err(i, ''); }); });
    on(form, 'submit', function (ev) {
      ev.preventDefault();
      var name = form.elements.name, phone = form.elements.phone, ok = true;
      ok = err(name, name.value.trim().length < 2 ? 'لطفاً نام خود را وارد کنید.' : '') && ok;
      var p = norm(phone.value).replace(/[\s\-()]/g, '');
      ok = err(phone, /^(\+98|0098|0)?9\d{9}$/.test(p) ? '' : 'شماره‌ی موبایل معتبر وارد کنید (مثل 09123456789).') && ok;
      if (!ok) { (form.querySelector('[aria-invalid="true"]') || {}).focus && form.querySelector('[aria-invalid="true"]').focus(); return; }
      if (form.elements.website && form.elements.website.value) return;
      var data = { name: name.value.trim(), phone: p, service: form.elements.service ? form.elements.service.value : '', message: form.elements.message ? form.elements.message.value.trim() : '', page: form.getAttribute('data-page') || d.title, website: '' };
      var text = 'سلام، من ' + data.name + ' هستم.\nشماره‌ی تماس: ' + data.phone + '\nدرخواست: ' + data.service + (data.message ? '\nتوضیحات: ' + data.message : '') + '\n(از صفحه‌ی: ' + data.page + ')';
      var wa = (form.getAttribute('data-wa') || '') + '?text=' + encodeURIComponent(text);
      btn.disabled = true; var old = btn.innerHTML; btn.innerHTML = 'در حال ارسال…';
      function fallback() {
        status.className = 'form-status is-warn';
        status.innerHTML = 'اطلاعات شما آماده است. برای ثبت سریع‌تر، پیام را مستقیم در واتساپ ارسال کنید یا تماس بگیرید.<div class="btn-row"><a class="btn btn--wa btn--sm" target="_blank" rel="noopener" href="' + wa + '">ارسال در واتساپ</a><a class="btn btn--ghost btn--sm" href="tel:' + (form.getAttribute('data-phone') || '') + '">تماس تلفنی</a></div>';
      }
      function done() { status.className = 'form-status is-ok'; status.textContent = 'درخواست شما ثبت شد؛ کارشناس ما در کمتر از ۲ ساعت با شما تماس می‌گیرد. سپاس از اعتماد شما.'; form.reset(); }
      var ep = form.getAttribute('data-endpoint');
      if (location.protocol === 'file:' || !ep) { fallback(); btn.disabled = false; btn.innerHTML = old; return; }
      fetch(ep, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function (j) { j && j.ok ? done() : fallback(); })
        .catch(fallback)
        .then(function () { btn.disabled = false; btn.innerHTML = old; });
    });
  });

  /* ---------- cost calculator ---------- */
  var calc = $('[data-calc]');
  if (calc) {
    var cfg = JSON.parse(calc.getAttribute('data-calc'));
    var out = { total: $('[data-calc-total]', calc), range: $('[data-calc-range]', calc), time: $('[data-calc-time]', calc), sum: $('[data-calc-sum]', calc) };
    var pages = $('input[name=pages]', calc), pagesOut = $('[data-calc-pages]', calc);
    function compute() {
      var typeEl = $('input[name=ctype]:checked', calc), t = cfg.types[typeEl ? typeEl.value : 0];
      var n = parseInt(pages.value, 10), extra = Math.max(0, n - t.pages_incl), price = t.base + Math.ceil(extra / 5) * cfg.per5pages;
      var rows = [['نوع سایت', t.label], ['تعداد صفحات', fmt(n) + ' صفحه']], days = t.days;
      $$('input[name=opt]:checked', calc).forEach(function (o) { var op = cfg.options[o.value]; price += op.price; days += op.days || 0; rows.push([op.label, '+' + fmt(op.price) + ' م']); });
      var lang = parseInt($('select[name=lang]', calc).value, 10); if (lang > 1) { price += (lang - 1) * cfg.per_lang; rows.push(['زبان‌ها', fmt(lang) + ' زبان']); }
      var lo = Math.round(price), hi = Math.round(price * 1.25);
      out.total.firstChild.nodeValue = fmt(lo) + ' تا ' + fmt(hi) + ' ';
      out.time.textContent = fmt(days) + ' تا ' + fmt(days + 10) + ' روز کاری';
      out.sum.innerHTML = rows.map(function (r) { return '<div><dt>' + r[0] + '</dt><dd>' + r[1] + '</dd></div>'; }).join('');
      pagesOut.textContent = fmt(n);
      pages.style.setProperty('--p', ((n - pages.min) / (pages.max - pages.min) * 100) + '%');
      calc._summary = 'برآورد: ' + t.label + '، ' + n + ' صفحه، ' + fmt(lo) + ' تا ' + fmt(hi) + ' میلیون تومان';
    }
    $$('input,select', calc).forEach(function (i) { on(i, 'input', compute); on(i, 'change', compute); });
    compute();
    on($('[data-calc-send]', calc), 'click', function (e) {
      var f = $('#f-calc'); if (!f) return;
      var m = f.elements.message; if (m) m.value = calc._summary;
      f.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
    });
  }

  /* ---------- filters (portfolio / store) ---------- */
  $$('[data-filter-group]').forEach(function (grp) {
    var items = $$(grp.getAttribute('data-filter-group'));
    var buttons = $$('[data-filter]', grp.closest('section') || d), q = $('[data-filter-search]', grp.closest('section') || d);
    var empty = $('.empty-state', grp.closest('section') || d), count = $('[data-filter-count]', grp.closest('section') || d);
    var state = { f: 'all', q: '' };
    function apply() {
      var shown = 0, qq = state.q.trim().toLowerCase();
      items.forEach(function (it) {
        var okF = state.f === 'all' || (it.getAttribute('data-cat') || '').split(' ').indexOf(state.f) > -1 || (it.getAttribute('data-services') || '').split(' ').indexOf(state.f) > -1 || it.getAttribute('data-lic') === state.f;
        var okQ = !qq || (it.getAttribute('data-search') || '').toLowerCase().indexOf(qq) > -1;
        var show = okF && okQ; it.hidden = !show; if (show) { shown++; it.classList.add('is-in'); }
      });
      count && (count.textContent = fmt(shown));
      empty && empty.classList.toggle('is-show', shown === 0);
    }
    buttons.forEach(function (b) { on(b, 'click', function () { state.f = b.getAttribute('data-filter'); buttons.forEach(function (x) { var a = x === b; x.classList.toggle('is-active', a); x.setAttribute('aria-pressed', a); }); apply(); }); });
    q && on(q, 'input', function () { state.q = norm(q.value); apply(); });
    apply();
  });

  /* ---------- article TOC + scrollspy ---------- */
  var prose = $('[data-toc-source]');
  if (prose) {
    var hs = $$('h2', prose), tocs = $$('[data-toc]');
    hs.forEach(function (h, i) { if (!h.id) h.id = 'sec-' + (i + 1); });
    tocs.forEach(function (ol) { ol.innerHTML = hs.map(function (h) { return '<li><a href="#' + h.id + '">' + h.textContent + '</a></li>'; }).join(''); });
    var links = $$('[data-toc] a');
    if ('IntersectionObserver' in w && hs.length) {
      var so = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { links.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + e.target.id); }); } }); }, { rootMargin: '-90px 0px -65% 0px' });
      hs.forEach(function (h) { so.observe(h); });
    }
  }
  /* sub-navigation highlight */
  var sub = $$('.subnav a');
  if (sub.length && 'IntersectionObserver' in w) {
    var secs = sub.map(function (a) { return $(a.getAttribute('href')); }).filter(Boolean);
    var sio = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) sub.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + e.target.id); }); }); }, { rootMargin: '-120px 0px -60% 0px' });
    secs.forEach(function (s) { sio.observe(s); });
  }

  /* ---------- search palette ---------- */
  var sm = $('#search-modal'), si = $('#search-input'), sr = $('#search-results'), idx = w.AVN_INDEX || [], active = -1, sFocus;
  function openSearch() { if (!sm) return; sFocus = d.activeElement; sm.classList.add('is-open'); sm.setAttribute('aria-hidden', 'false'); root.style.overflow = 'hidden'; setTimeout(function () { si.focus(); si.select(); }, 40); render(''); }
  function closeSearch() { if (!sm || !sm.classList.contains('is-open')) return; sm.classList.remove('is-open'); sm.setAttribute('aria-hidden', 'true'); root.style.overflow = ''; sFocus && sFocus.focus && sFocus.focus(); }
  function href(u) { var h = BASE + u.replace(/^\//, ''); return location.protocol === 'file:' ? h.replace(/\/$/, '/index.html') : h; }
  function render(q) {
    var n = norm(q).trim().toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک'), list = idx;
    if (n) {
      var parts = n.split(/\s+/);
      list = idx.map(function (it) {
        var hay = (it.t + ' ' + it.k + ' ' + it.d).toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک'), sc = 0;
        parts.forEach(function (p) { if (it.t.toLowerCase().indexOf(p) > -1) sc += 4; else if (it.k.toLowerCase().indexOf(p) > -1) sc += 3; else if (hay.indexOf(p) > -1) sc += 1; else sc -= 99; });
        return { it: it, sc: sc };
      }).filter(function (x) { return x.sc > 0; }).sort(function (a, b) { return b.sc - a.sc; }).map(function (x) { return x.it; });
    } else { list = idx.filter(function (i) { return ['/services/web-design/', '/wordpress-web-design/', '/services/seo/', '/services/site-support/', '/portfolio/', '/price-wordpress/', '/contact-us/'].indexOf(i.u) > -1; }); }
    list = list.slice(0, 9); active = list.length ? 0 : -1;
    sr.innerHTML = list.length ? list.map(function (it, i) { return '<a class="search-item' + (i === 0 ? ' is-active' : '') + '" role="option" href="' + href(it.u) + '"><span><strong>' + it.t + '</strong><small>' + it.d.slice(0, 92) + '…</small></span><span class="tag">' + it.c + '</span></a>'; }).join('') : '<div class="search-empty">نتیجه‌ای پیدا نشد. عبارت دیگری را امتحان کنید یا <a href="' + href('/contact-us/') + '">با ما تماس بگیرید</a>.</div>';
  }
  on($('[data-search-open]'), 'click', openSearch);
  on($('[data-search-close]'), 'click', closeSearch);
  on(sm, 'click', function (e) { if (e.target === sm) closeSearch(); });
  on(si, 'input', function () { render(si.value); });
  on(si, 'keydown', function (e) {
    var items = $$('.search-item', sr); if (!items.length) return;
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); items[active] && items[active].classList.remove('is-active'); active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length; items[active].classList.add('is-active'); items[active].scrollIntoView({ block: 'nearest' }); }
    if (e.key === 'Enter' && items[active]) { e.preventDefault(); location.href = items[active].getAttribute('href'); }
  });
  on(d, 'keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); sm.classList.contains('is-open') ? closeSearch() : openSearch(); }
    else if (e.key === '/' && !/input|textarea|select/i.test((d.activeElement || {}).tagName || '')) { e.preventDefault(); openSearch(); }
  });

  /* ---------- lightbox ---------- */
  var lb = $('#lightbox');
  function openLightbox(src, alt) { if (!lb) return; var im = $('img', lb); im.src = src; im.alt = alt || ''; lb.classList.add('is-open'); root.style.overflow = 'hidden'; }
  function closeLightbox() { if (!lb || !lb.classList.contains('is-open')) return; lb.classList.remove('is-open'); root.style.overflow = ''; }
  $$('[data-lightbox]').forEach(function (el) { on(el, 'click', function () { var im = el.tagName === 'IMG' ? el : $('img', el); openLightbox(el.getAttribute('data-lightbox') || im.src, im.alt); }); });
  on(lb, 'click', function (e) { if (e.target === lb || e.target.closest('[data-lightbox-close]')) closeLightbox(); });

  /* ---------- map (loaded on demand) ---------- */
  $$('[data-map-load]').forEach(function (b) { on(b, 'click', function () { var card = b.closest('[data-map]'); var f = d.createElement('iframe'); f.src = card.getAttribute('data-src'); f.loading = 'lazy'; f.title = 'نقشه‌ی دفتر آمل نوین'; f.referrerPolicy = 'no-referrer-when-downgrade'; card.appendChild(f); $('.map-card__in', card).style.display = 'none'; }); });

  /* ---------- copy ---------- */
  $$('[data-copy]').forEach(function (b) { on(b, 'click', function () { var v = b.getAttribute('data-copy') || location.href; (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast('کپی شد ✓'); }, function () { toast('کپی نشد؛ لطفاً دستی کپی کنید'); }); }); });
  $$('[data-share]').forEach(function (b) { on(b, 'click', function () { if (navigator.share) navigator.share({ title: d.title, url: location.href }).catch(function () {}); else { navigator.clipboard && navigator.clipboard.writeText(location.href); toast('لینک صفحه کپی شد ✓'); } }); });

  /* ---------- site-type chooser ---------- */
  var ch = $('[data-chooser]');
  if (ch) {
    var data = JSON.parse(ch.getAttribute('data-chooser')), sel = $('select', ch), outEl = $('.chooser__out', ch);
    function show() {
      var t = data[sel.value]; if (!t) { outEl.classList.remove('is-show'); return; }
      var h = href('/services/web-design/' + t.slug + '/');
      outEl.innerHTML = '<div class="card card--feature"><h3>پیشنهاد ما: ' + t.title + '</h3><p>' + t.tldr + '</p><div class="btn-row mt-2"><a class="btn btn--primary" href="' + h + '">مشاهده‌ی ساختار و امکانات</a><a class="btn btn--ghost" href="#consult">مشاوره‌ی رایگان</a></div></div>';
      outEl.classList.add('is-show');
    }
    on(sel, 'change', show);
  }

  /* ---------- tel / wa click tracking hook (dataLayer if present) ---------- */
  on(d, 'click', function (e) {
    var a = e.target.closest('a[href^="tel:"],a[href*="wa.me"]'); if (!a) return;
    w.dataLayer = w.dataLayer || []; w.dataLayer.push({ event: a.href.indexOf('tel:') === 0 ? 'click_call' : 'click_whatsapp' });
  });

  fixFileLinks(d);
})();
