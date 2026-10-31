@php($calc = amol_common('calc', []))
<section class="section" id="calculator"><div class="container">
  <x-sec-head eyebrow="ماشین‌حساب هزینه" title="برآورد هزینه‌ی طراحی سایت در کمتر از یک دقیقه" desc="نوع سایت، تعداد صفحات و امکانات را انتخاب کنید تا برآورد اولیه را فوری ببینید. قیمت قطعی پس از جلسه‌ی مشاوره‌ی رایگان و بررسی نیاز شما اعلام می‌شود." :center="false" />
  <div class="calc" data-calc="{{ wp_json_encode($calc, JSON_UNESCAPED_UNICODE) }}">
    <div class="calc__panel reveal">
      <div class="calc__group"><h3><span class="n">۱</span> نوع سایت</h3>
        <div class="calc__types">@foreach (($calc['types'] ?? []) as $ty)<label><input type="radio" name="ctype" value="{{ $loop->index }}"@if ($loop->first) checked @endif><span>@icon($ty['icon']) {{ $ty['label'] }}</span></label>@endforeach</div></div>
      <div class="calc__group"><h3><span class="n">۲</span> تعداد صفحات</h3>
        <div class="range-row"><input type="range" name="pages" min="3" max="40" value="8" aria-label="تعداد صفحات"><div class="flex between"><span class="muted small">۳ صفحه</span><output><strong data-calc-pages>۸</strong> صفحه</output><span class="muted small">۴۰ صفحه</span></div></div></div>
      <div class="calc__group"><h3><span class="n">۳</span> امکانات اضافه</h3>
        <div class="calc__opts">@foreach (($calc['options'] ?? []) as $o)<label><input type="checkbox" name="opt" value="{{ $loop->index }}"><span>{{ $o['label'] }} <em>+{{ $o['price'] }} م</em></span></label>@endforeach</div></div>
      <div class="calc__group"><h3><span class="n">۴</span> تعداد زبان‌ها</h3>
        <div class="field"><label class="sr-only" for="calc-lang">زبان‌ها</label><select id="calc-lang" name="lang"><option value="1">فقط فارسی</option><option value="2">فارسی و انگلیسی</option><option value="3">سه زبان (فارسی، انگلیسی، عربی)</option></select></div></div>
    </div>
    <aside class="calc__result reveal" aria-live="polite">
      <h3>برآورد اولیه‌ی هزینه</h3>
      <div class="calc__total" data-calc-total>۰ <small>میلیون تومان (برآورد اولیه)</small></div>
      <p class="mb-0 small">زمان تقریبی تحویل: <strong data-calc-time>—</strong></p>
      <dl class="calc__sum" data-calc-sum></dl>
      <button class="btn btn--cta btn--block" type="button" data-calc-send>@icon('send') ثبت درخواست با این برآورد</button>
      <p class="calc__disclaimer">این عدد صرفاً یک برآورد راهنماست و تعهد قیمت نیست. هزینه‌ی دامنه، هاست و افزونه‌های پولی ثالث جدا محاسبه می‌شود.</p>
    </aside>
  </div>
</div></section>
