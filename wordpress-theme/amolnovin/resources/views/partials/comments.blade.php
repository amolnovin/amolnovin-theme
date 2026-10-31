@if (! post_password_required())
  <section id="comments" class="comments">
    @if (have_comments())
      <h2>{{ number_format_i18n(get_comments_number()) }} دیدگاه</h2>
      <ol class="comment-list">{!! wp_list_comments(['style' => 'ol', 'short_ping' => true, 'echo' => false]) !!}</ol>
      {!! get_the_comments_pagination() !!}
    @endif
    @if (! comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments'))
      <div class="callout">@icon('info')<p>دیدگاه‌ها بسته شده‌اند.</p></div>
    @endif
    @php(comment_form())
  </section>
@endif
