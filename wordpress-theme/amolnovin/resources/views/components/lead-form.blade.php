@props(['id', 'service' => '', 'variant' => 'full', 'cta' => 'دریافت مشاوره‌ی رایگان', 'rows' => 3])
@php($opts = amol_common('service_options', []))
<form class="lead-form" id="{{ $id }}" data-lead-form data-endpoint="{{ \App\Rest\Lead::url() }}" data-wa="{{ $site['whatsapp'] }}" data-phone="{{ $site['phone_intl'] }}" data-page="{{ $page['title'] ?? '' }}" novalidate>
  <div class="{{ $variant === 'quick' ? 'quick__form' : 'form-grid' }}">
    <div class="field"><label for="{{ $id }}-name">نام و نام خانوادگی</label><input id="{{ $id }}-name" name="name" autocomplete="name" required placeholder="مثلاً علی رضایی"><span class="err" aria-live="polite"></span></div>
    <div class="field"><label for="{{ $id }}-phone">شماره‌ی موبایل</label><input id="{{ $id }}-phone" name="phone" type="tel" inputmode="tel" dir="ltr" autocomplete="tel" required placeholder="09xxxxxxxxx"><span class="err" aria-live="polite"></span></div>
    <div class="field{{ $variant === 'quick' ? '' : ' field--full' }}"><label for="{{ $id }}-service">نوع درخواست</label>
      <select id="{{ $id }}-service" name="service">
        @if ($service && ! in_array($service, $opts, true))<option selected>{{ $service }}</option>@endif
        @foreach ($opts as $o)<option @if ($o === $service) selected @endif>{{ $o }}</option>@endforeach
      </select></div>
    @if ($variant === 'full')
    <div class="field field--full"><label for="{{ $id }}-msg">توضیح کوتاه (اختیاری)</label><textarea id="{{ $id }}-msg" name="message" rows="{{ $rows }}" placeholder="نوع کسب‌وکار، هدف سایت یا هر نکته‌ای که می‌خواهید بدانیم"></textarea></div>
    @endif
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    {{-- WP Math Captcha (only when the plugin is active): verified server-side in App\Rest\Lead --}}
    @if (\App\Integrations\Captcha::shouldProtect())
    <div class="field field--full wpmc-field">{!! \App\Integrations\Captcha::field() !!}</div>
    @endif
    <div class="form-actions"><button class="btn btn--cta {{ $variant === 'quick' ? '' : 'btn--lg btn--block' }}" type="submit">@icon('send') {{ $cta }}</button></div>
  </div>
  @if ($variant !== 'quick')<p class="form-note">@icon('lock') پاسخ در {{ $site['response_time'] }}؛ اطلاعات شما نزد ما محفوظ می‌ماند.</p>@endif
  <div class="form-status" role="status" aria-live="polite"></div>
</form>
