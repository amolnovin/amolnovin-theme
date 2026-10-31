<section class="section" id="contact"><div class="container">
  <x-sec-head eyebrow="راه‌های ارتباطی" title="تماس با آمل نوین؛ هر راهی که راحت‌ترید" desc="تلفن، واتساپ، ایمیل یا مراجعه‌ی حضوری در آمل؛ همه‌ی راه‌ها به یک تیم می‌رسد." :center="false" />
  <div class="contact-cards">
    <a class="contact-card reveal" href="tel:{{ $site['phone_intl'] }}"><span class="ico">@icon('phone-call')</span><span><small>تماس تلفنی</small><strong dir="ltr">{{ $site['phone_display'] }}</strong><span>پاسخ‌گویی ۲۴/۷</span></span></a>
    <a class="contact-card reveal" href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener"><span class="ico ico--wa">@icon('b-whatsapp')</span><span><small>واتساپ</small><strong>گفتگوی آنلاین</strong><span>پاسخ در {{ $site['response_time'] }}</span></span></a>
    <a class="contact-card reveal" href="mailto:{{ $site['email'] }}"><span class="ico">@icon('mail')</span><span><small>ایمیل</small><strong dir="ltr">{{ $site['email'] }}</strong><span>برای ارسال فایل و RFP</span></span></a>
    <div class="contact-card reveal"><span class="ico">@icon('map-pin')</span><span><small>دفتر آمل</small><strong>{{ $site['street'] }}</strong><span>{{ $site['region'] }}، {{ $site['city'] }} — با هماهنگی قبلی</span></span></div>
  </div>
  <div class="contact-grid mt-4">
    <div class="lead-card reveal"><h2 style="font-size:1.35rem">فرم درخواست مشاوره‌ی رایگان</h2><p class="muted">اطلاعات را بنویسید تا کارشناس ما در {{ $site['response_time'] }} تماس بگیرد.</p><x-lead-form id="f-contact" service="" variant="full" cta="ارسال درخواست" :rows="5" /></div>
    <div class="reveal"><x-map-card />
      <div class="card mt-3"><h3>@icon('clock') ساعت پاسخ‌گویی</h3>
        <div class="hours"><div><span>پاسخ در واتساپ و تماس</span><strong>۲۴ ساعته</strong></div><div><span>جلسه‌ی حضوری در دفتر</span><strong>با هماهنگی قبلی</strong></div><div><span>سقف زمان پاسخ به درخواست‌ها</span><strong>کمتر از ۲ ساعت</strong></div></div></div>
    </div>
  </div>
</div></section>
