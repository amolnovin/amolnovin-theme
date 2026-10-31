@extends('layouts.app')

@section('content')
@php
  $rotator = amol_common('rotator', []);
  $tech = amol_common('tech', []);
  $why = amol_common('why', []);
  $process = amol_common('process', []);
  $plans = amol_common('plans', []);
  $types = amol_types();
  $products = \App\Content\Repo::products();
  $projBy = \App\Content\Repo::projectBy();
  $postBy = \App\Content\Repo::postBy();
@endphp

{{-- ============================== HERO ============================== --}}
<section class="hero" aria-labelledby="hero-title">
  <div class="container hero__grid">
    <div class="hero__copy">
      <a class="hero__pill" href="{{ amol_url('/about-us/') }}"><span class="dot"></span> فناوری اطلاعات آمل نوین · فعال از ۱۳۹۰ · آمل، مازندران</a>
      <h1 id="hero-title">طراحی سایت در آمل؛ <em>سایتی که دیده می‌شود</em> و مشتری می‌آورد</h1>
      <p class="hero__lead">آمل نوین، شرکت طراحی سایت وردپرس و اختصاصی در آمل و مازندران؛ از طراحی رابط کاربری و سئو و تولید محتوا تا پشتیبانی سایت. سریع، امن، ریسپانسیو و آماده‌ی دیده‌شدن در گوگل و پاسخ‌های هوش مصنوعی.</p>
      <p class="hero__rotator"><span>طراحی می‌کنیم:</span> <span class="rotator" data-rotate="{{ wp_json_encode($rotator, JSON_UNESCAPED_UNICODE) }}">{{ $rotator[0] ?? '' }}</span></p>
      <div class="btn-row">
        <a class="btn btn--cta btn--lg" href="#consult">@icon('message-circle') دریافت مشاوره‌ی رایگان</a>
        <a class="btn btn--light btn--lg" href="{{ amol_url('/portfolio/') }}">مشاهده‌ی نمونه‌کارها @icon('arrow-left', 'i--arrow')</a>
      </div>
      <ul class="hero__trust">
        <li>@icon('check') ریسپانسیو و موبایل‌محور</li>
        <li>@icon('check') سئو پایه و SSL</li>
        <li>@icon('check') ۲ سال پشتیبانی فنی رایگان</li>
        <li>@icon('check') قرارداد کتبی و پروانه‌ی کسب</li>
      </ul>
    </div>
    <div class="hv" aria-hidden="true">
      <div class="hv__win">
        <div class="hv__bar"><i></i><i></i><i></i><span class="hv__url">@icon('lock') amolnovin.ir</span></div>
        <div class="hv__body">
          <div class="hv__nav"><b></b><span></span><span></span><span></span><em></em></div>
          <div class="hv__hero">
            <div class="hv__title">سایتی بسازید که مشتری بیاورد<small>طراحی سایت حرفه‌ای، سئو و پشتیبانی</small><span class="hv__cta">مشاوره‌ی رایگان</span></div>
            <div class="hv__art"><img src="{{ amol_asset('img/brand/logo-mark-light.svg') }}" width="120" height="61" alt=""></div>
          </div>
          <div class="hv__cards"><div style="--c:#13a89e"><i></i><i></i></div><div style="--c:#272869"><i></i><i></i></div><div style="--c:#ffb21f"><i></i><i></i></div></div>
        </div>
      </div>
      <div class="hv__chip hv__chip--cwv"><span class="ico">@icon('gauge')</span><span>Core Web Vitals<small>سرعت و تجربه‌ی کاربری عالی</small></span><span class="hv__dots"><i></i><i></i><i></i></span></div>
      <div class="hv__chip hv__chip--ssl"><span class="ico">@icon('shield-check')</span><span>SSL فعال<small>اتصال امن و رمزنگاری‌شده</small></span></div>
      <div class="hv__chip hv__chip--lead"><span class="ico">@icon('bell-ring')</span><span>درخواست جدید ثبت شد<small>از فرم مشاوره‌ی سایت</small></span></div>
      <div class="hv__phone"><i></i><i></i><b></b></div>
    </div>
  </div>
</section>

{{-- ============================== QUICK CONSULT ============================== --}}
<div class="quickbar" id="consult"><div class="container"><div class="quick reveal">
  <div><h2>مشاوره و برآورد رایگان</h2><p>پاسخ در {{ $site['response_time'] }} · بدون تعهد · ساعت پاسخ‌گویی ۲۴/۷</p></div>
  <x-lead-form id="f-quick" service="" variant="quick" cta="درخواست مشاوره" />
</div></div></div>

{{-- ============================== TECH STRIP ============================== --}}
<section class="strip" aria-label="فناوری‌های مورد استفاده">
  <div class="container"><p class="strip__label">با استانداردهای روز وب و ابزارهایی که کسب‌وکار شما را رشد می‌دهند</p></div>
  <div class="marquee"><ul class="marquee__track">
    @foreach ([0, 1] as $rep)@foreach ($tech as $t)<li class="tech" style="--tc:{{ $t[2] }}" @if ($rep) aria-hidden="true" @endif>@icon($t[1]) {{ $t[0] }}</li>@endforeach @endforeach
  </ul></div>
</section>

{{-- ============================== QUICK FACTS (answer-first) ============================== --}}
<section class="section section--tight" aria-labelledby="facts-title">
  <div class="container">
    <h2 id="facts-title" class="sr-only">آمل نوین در یک نگاه</h2>
    <dl class="facts reveal">
      <div><dt>@icon('building-2', 'i--sm') چه کسی هستیم؟</dt><dd>{{ $site['name'] }}؛ دارای پروانه‌ی کسب رسمی، فعال از سال {{ $site['founded_j'] }} در آمل، مازندران</dd></div>
      <div><dt>@icon('layout-template', 'i--sm') چه می‌کنیم؟</dt><dd>طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا، امنیت و سرعت</dd></div>
      <div><dt>@icon('banknote', 'i--sm') هزینه از</dt><dd>طراحی سایت {{ $site['price_design'] }} میلیون تومان · پشتیبانی {{ $site['price_support'] }} میلیون در ماه · سئو {{ $site['price_seo'] }} میلیون در ماه</dd></div>
      <div><dt>@icon('clock', 'i--sm') زمان تحویل</dt><dd>سایت شرکتی ۱۰ تا ۲۰ روز کاری · فروشگاهی ۲۰ تا ۴۵ روز کاری</dd></div>
      <div><dt>@icon('life-buoy', 'i--sm') پشتیبانی</dt><dd>{{ $site['support_months'] }} ماه پشتیبانی فنی رایگان با قرارداد کتبی</dd></div>
    </dl>
  </div>
</section>

{{-- ============================== SERVICES (bento) ============================== --}}
<section class="section" id="services" aria-labelledby="services-title">
  <div class="container">
    <x-sec-head eyebrow="خدمات آمل نوین" title="خدمات طراحی سایت، سئو و پشتیبانی؛ همه در یک مجموعه" desc="از ایده تا رتبه‌گیری و پشتیبانی؛ هر مرحله را متخصص همان حوزه انجام می‌دهد تا سایت شما فقط «ساخته» نشود، «نتیجه» بدهد." :center="false" id="services-title" />
    <div class="bento">
      <article class="card card--hover card--dark bento__item bento__item--4 bento__item--dark reveal">
        <span class="card__icon">@icon('b-wordpress')</span>
        <h3>طراحی سایت وردپرس اختصاصی</h3>
        <p>طراحی سایت وردپرس با قالب پایه‌ی سبک Hello Elementor، طراحی UI/UX اختصاصی و کد تمیز؛ بدون قالب‌های آماده‌ی سنگین. مناسب سایت شرکتی، فروشگاهی، آموزشی و هر کسب‌وکار دیگر.</p>
        <div class="bento__tags"><span class="chip">ریسپانسیو</span><span class="chip">سئو پایه</span><span class="chip">SSL</span><span class="chip">Core Web Vitals</span><span class="chip">۲ سال پشتیبانی</span></div>
        <a class="card__link" href="{{ amol_url('/wordpress-web-design/') }}">جزئیات طراحی سایت وردپرس @icon('arrow-left')</a>
      </article>
      <article class="card card--hover bento__item reveal">
        <span class="card__icon" style="--c:#6d4aff">@icon('code-xml')</span>
        <h3>طراحی سایت اختصاصی</h3>
        <p>کدنویسی PHP، لاراول و جاوااسکریپت برای پنل‌ها، سامانه‌ها و پورتال‌هایی که با قالب آماده شدنی نیست.</p>
        <a class="card__link" href="{{ amol_url('/services/coding/') }}">طراحی سایت اختصاصی @icon('arrow-left')</a>
      </article>
      <article class="card card--hover bento__item reveal">
        <span class="card__icon" style="--c:#0e958c">@icon('life-buoy')</span>
        <h3>پشتیبانی سایت در آمل</h3>
        <p>بروزرسانی، بکاپ، رفع خطا و گزارش ماهانه؛ سایت شما همیشه سالم و بروز می‌ماند.</p>
        <a class="card__link" href="{{ amol_url('/services/site-support/') }}">پشتیبانی سایت @icon('arrow-left')</a>
      </article>
      <article class="card card--hover bento__item reveal">
        <span class="card__icon" style="--c:#e8662c">@icon('chart-line')</span>
        <h3>سئو و تولید محتوا</h3>
        <p>تحقیق کلمات کلیدی، سئوی فنی و محتوای سئو شده برای گوگل و پاسخ‌های هوش مصنوعی.</p>
        <a class="card__link" href="{{ amol_url('/services/seo/') }}">سئو و تولید محتوا @icon('arrow-left')</a>
      </article>
      <article class="card card--hover bento__item reveal">
        <span class="card__icon" style="--c:#e5484d">@icon('shield-check')</span>
        <h3>امنیت و سرعت سایت</h3>
        <p>فایروال، ضد بدافزار، بکاپ و بهینه‌سازی سرعت برای تجربه‌ای امن و سریع.</p>
        <a class="card__link" href="{{ amol_url('/services/security/') }}">امنیت و سرعت @icon('arrow-left')</a>
      </article>
      <article class="card card--hover bento__item reveal">
        <span class="card__icon" style="--c:#566275">@icon('network')</span>
        <h3>خدمات کامپیوتر و شبکه</h3>
        <p>پشتیبانی سیستم، شبکه‌ی اداری و رفع مشکلات فناوری اطلاعات برای شرکت‌ها و اشخاص در آمل.</p>
        <a class="card__link" href="{{ amol_url('/services/computer-network/') }}">خدمات کامپیوتر @icon('arrow-left')</a>
      </article>
      <article class="card bento__item bento__item--4 bento__cta reveal">
        <div><h3>مطمئن نیستید کدام خدمت مناسب شماست؟</h3><p>مشاوره‌ی رایگان بگیرید؛ نیاز شما را می‌شنویم و ساده‌ترین مسیر را پیشنهاد می‌دهیم.</p></div>
        <a class="btn btn--cta" href="#consult">@icon('message-circle') مشاوره‌ی رایگان</a>
      </article>
    </div>
  </div>
</section>

{{-- ============================== TYPES ============================== --}}
<section class="section section--alt" id="types" aria-labelledby="types-title">
  <div class="container">
    <x-sec-head eyebrow="طراحی انواع سایت" :title="'طراحی سایت برای هر نوع کسب‌وکار؛ ' . count($types) . ' ساختار تخصصی'" desc="هر نوع سایت نیاز، امکانات و سئوی متفاوتی دارد. صفحه‌ی اختصاصی کسب‌وکار خود را باز کنید و ساختار پیشنهادی، امکانات و هزینه را ببینید." :center="true" id="types-title" />
    <div class="types">@foreach ($types as $t)<x-type-tile :t="$t" />@endforeach</div>
    <p class="center mt-3"><a class="btn btn--primary" href="{{ amol_url('/services/web-design/') }}">راهنمای انتخاب نوع سایت @icon('arrow-left', 'i--arrow')</a></p>
  </div>
</section>

{{-- ============================== WHY US ============================== --}}
<section class="section" id="why" aria-labelledby="why-title">
  <div class="container">
    <x-sec-head eyebrow="چرا آمل نوین؟" title="شریک دیجیتال شما در آمل، نه فقط یک شرکت طراحی سایت" desc="تفاوت ما در جزئیاتی است که بعد از تحویل پروژه معلوم می‌شود: سرعت، امنیت، پشتیبانی و شفافیت." :center="false" id="why-title" />
    <div class="grid grid--3">
      @foreach ($why as [$ic, $t, $d])<div class="card reveal" style="--d:{{ $loop->index * 0.06 }}s"><span class="card__icon">@icon($ic)</span><h3>{{ $t }}</h3><p>{{ $d }}</p></div>@endforeach
    </div>
  </div>
</section>
<section class="section section--dark section--tight" aria-label="آمار آمل نوین">
  <div class="container"><div class="stats">
    <div class="stat reveal"><span class="stat__num" data-count="15" data-suffix="+">۱۵+</span><span class="stat__label">سال تجربه در طراحی وب</span></div>
    <div class="stat reveal"><span class="stat__num" data-count="150" data-suffix="+">۱۵۰+</span><span class="stat__label">پروژه‌ی تحویل‌شده</span></div>
    <div class="stat reveal"><span class="stat__num" data-count="24">۲۴</span><span class="stat__label">ماه پشتیبانی فنی رایگان</span></div>
    <div class="stat reveal"><span class="stat__num" data-count="{{ count($types) }}">{{ count($types) }}</span><span class="stat__label">نوع سایت با ساختار تخصصی</span></div>
  </div></div>
</section>

{{-- ============================== PROCESS ============================== --}}
<section class="section" id="process" aria-labelledby="process-title">
  <div class="container">
    <x-sec-head eyebrow="مراحل همکاری" title="از اولین تماس تا راه‌اندازی؛ شفاف و قدم‌به‌قدم" desc="هر پروژه با قرارداد کتبی و زمان‌بندی مشخص انجام می‌شود تا همیشه بدانید در کدام مرحله‌ایم." :center="true" id="process-title" />
    <ol class="steps steps--{{ count($process) }}">
      @foreach ($process as [$t, $d])<li class="step reveal" style="--d:{{ $loop->index * 0.06 }}s"><span class="step__num">{{ $loop->iteration }}</span><h3>{{ $t }}</h3><p>{{ $d }}</p></li>@endforeach
    </ol>
  </div>
</section>

{{-- ============================== PORTFOLIO ============================== --}}
<section class="section section--alt" id="portfolio" aria-labelledby="portfolio-title">
  <div class="container">
    <x-sec-head eyebrow="نمونه‌کارها" title="پروژه‌های واقعی؛ سایت‌هایی که امروز فعال‌اند" desc="فروشگاه، سایت شرکتی، صنعتی و بین‌المللی؛ هر نمونه را باز کنید و مطالعه‌ی موردی آن را ببینید." :center="false" id="portfolio-title" />
    <div class="projects">@foreach (amol_common('home_projects', []) as $s)@if (isset($projBy[$s]))<x-project-card :p="$projBy[$s]" />@endif @endforeach</div>
    <p class="center mt-3"><a class="btn btn--primary btn--lg" href="{{ amol_url('/portfolio/') }}">مشاهده‌ی همه‌ی نمونه‌کارها @icon('arrow-left', 'i--arrow')</a></p>
  </div>
</section>

{{-- ============================== PRICING ============================== --}}
<section class="section" id="pricing" aria-labelledby="pricing-title">
  <div class="container">
    <x-sec-head eyebrow="هزینه طراحی سایت" :title="'قیمت شفاف؛ از ' . $site['price_design'] . ' میلیون تومان'" desc="قیمت نهایی بر اساس نوع سایت، تعداد صفحات و امکانات در جلسه‌ی مشاوره‌ی رایگان اعلام می‌شود. هیچ هزینه‌ی پنهانی وجود ندارد." :center="true" id="pricing-title" />
    <div class="plans">
      @foreach ($plans as $p)
      <article class="plan{{ ! empty($p['pop']) ? ' plan--pop' : '' }} reveal" style="--d:{{ $loop->index * 0.08 }}s">
        @if (! empty($p['flag']))<span class="plan__flag">{{ $p['flag'] }}</span>@endif
        <h3 class="plan__name">{{ $p['name'] }}</h3><p class="plan__desc">{{ $p['desc'] }}</p>
        <div class="plan__price">{{ $p['price'] }}<small>{{ $p['unit'] }}</small></div>
        <ul class="plan__list">@foreach ($p['features'] as $f)<li>@icon('check')<span>{{ $f }}</span></li>@endforeach</ul>
        <a class="btn {{ ! empty($p['pop']) ? 'btn--cta' : 'btn--primary' }}" href="{{ amol_url($p['href']) }}">{{ $p['cta'] }} @icon('arrow-left', 'i--arrow')</a>
      </article>
      @endforeach
    </div>
    <p class="plan__note mt-3">مبلغ‌ها «شروع از» هستند. برای برآورد دقیق از <a href="{{ amol_url('/price-wordpress/#calculator') }}">ماشین‌حساب هزینه</a> استفاده کنید.</p>
  </div>
</section>

{{-- ============================== LOCAL ============================== --}}
<section class="section section--alt" id="amol" aria-labelledby="amol-title">
  <div class="container">
    <div class="split">
      <div class="reveal">
        <x-sec-head eyebrow="طراحی سایت در آمل" title="طراحی سایت آمل؛ شرکت طراحی سایت در آمل با دفتر حضوری" desc="اگر دنبال طراحی سایت آمل با تیمی محلی هستید، می‌توانید حضوری با ما جلسه بگذارید؛ ما بازار منطقه را می‌شناسیم و سایت را برای جستجوهای محلی هم بهینه می‌کنیم." :center="false" id="amol-title" />
        <ul class="nap">
          <li><span class="ico">@icon('map-pin')</span><span><small>نشانی دفتر (با هماهنگی قبلی)</small><strong>{{ $site['address'] }}</strong></span></li>
          <li><span class="ico">@icon('phone-call')</span><span><small>تماس و واتساپ</small><a href="tel:{{ $site['phone_intl'] }}" dir="ltr">{{ $site['phone_display'] }}</a></span></li>
          <li><span class="ico">@icon('mail')</span><span><small>ایمیل</small><a href="mailto:{{ $site['email'] }}" dir="ltr">{{ $site['email'] }}</a></span></li>
        </ul>
        <div class="city-list">@foreach (amol_cities() as $c)<span class="chip">{{ $c }}</span>@endforeach</div>
        <div class="btn-row mt-3"><a class="btn btn--primary" href="{{ amol_url('/web-design-amol/') }}">طراحی سایت در آمل @icon('arrow-left', 'i--arrow')</a><a class="btn btn--ghost" href="{{ amol_url('/contact-us/') }}">تماس با ما</a></div>
      </div>
      <div class="reveal"><x-map-card /></div>
    </div>
  </div>
</section>

{{-- ============================== LEARN + STORE ============================== --}}
<section class="section" aria-label="آموزش و فروشگاه افزونه">
  <div class="container">
    <div class="grid grid--2">
      <article class="card card--feature reveal">
        <span class="card__icon" style="--c:#d98400">@icon('graduation-cap')</span>
        <h3>دوره‌ی آموزش طراحی سایت با وردپرس و هوش مصنوعی</h3>
        <p>دوره‌ی حضوری ۳ ماهه در آمل با بیش از ۱۲ سرفصل و ۳ پروژه‌ی عملی (شرکتی، فروشگاهی و شخصی)؛ بدون نیاز به کدنویسی، با ابزارهای هوش مصنوعی و نکات امنیت.</p>
        <ul class="chip-row mt-2"><li class="chip chip--accent">حضوری در آمل</li><li class="chip chip--accent">۳ ماه</li><li class="chip chip--accent">۳ پروژه‌ی عملی</li></ul>
        <a class="card__link" href="{{ amol_url('/wordpress-learn/') }}">سرفصل‌ها و ثبت‌نام @icon('arrow-left')</a>
      </article>
      <article class="card card--feature reveal">
        <span class="card__icon" style="--c:#2f7cf6">@icon('package')</span>
        <h3>فروشگاه افزونه‌های اختصاصی آمل نوین</h3>
        <p>{{ count($products) }} افزونه‌ی کاربردی برای وردپرس و ووکامرس: امنیت، فیلتر محصولات، کیف پول، اعلان‌رسان بله و ایتا، مدیریت باشگاه و بیشتر.</p>
        @php($withImg = array_values(array_filter($products, fn ($x) => ! empty($x['img']) || ! empty($x['_thumb']))))
        <div class="flex gap-2 mt-2" style="overflow:hidden">@foreach (array_slice($withImg, 0, 3) as $pr)<img style="border-radius:12px;width:31%;height:auto" src="{{ ! empty($pr['_thumb']) ? $pr['_thumb'] : amol_asset('img/store/' . $pr['img'] . '.webp') }}" width="800" height="450" loading="lazy" alt="افزونه‌ی {{ $pr['name'] }}">@endforeach</div>
        <a class="card__link" href="{{ amol_url('/wplm-store/') }}">مشاهده‌ی افزونه‌ها @icon('arrow-left')</a>
      </article>
    </div>
  </div>
</section>

{{-- ============================== BLOG ============================== --}}
<section class="section section--alt" id="blog" aria-labelledby="blog-title">
  <div class="container">
    <x-sec-head eyebrow="وبلاگ" title="راهنماهای کاربردی طراحی سایت، سئو و پشتیبانی" :center="false" id="blog-title" />
    <div class="posts">@foreach (amol_common('home_posts', []) as $s)@if (isset($postBy[$s]))<x-post-card :p="$postBy[$s]" />@endif @endforeach</div>
    <p class="center mt-3"><a class="btn btn--ghost" href="{{ amol_url('/blog-n/') }}">همه‌ی مقالات @icon('arrow-left', 'i--arrow')</a></p>
  </div>
</section>

{{-- ============================== FAQ ============================== --}}
<section class="section" id="faq" aria-labelledby="faq-title">
  <div class="container container--narrow">
    <x-sec-head eyebrow="سوالات متداول" title="پاسخ به پرسش‌های پرتکرار درباره‌ی طراحی سایت در آمل" :center="true" id="faq-title" />
    <x-faq :items="$page['faq'] ?? []" :open-first="true" />
  </div>
</section>

<x-cta-band title="آماده‌اید سایتتان را به ابزار جذب مشتری تبدیل کنیم؟" text="همین امروز یک جلسه‌ی مشاوره‌ی رایگان بگیرید؛ نیاز شما را بررسی می‌کنیم و بدون تعهد، پیشنهاد و برآورد زمان و هزینه می‌دهیم." :primary="['دریافت مشاوره‌ی رایگان', '/contact-us/']" :secondary="['مشاهده‌ی نمونه‌کارها', '/portfolio/']" />
@endsection
