{{ \App\Support\NoticeText::render($type, $params, app()->getLocale()) }}

@if($url){{ url($url) }}
@endif

--
FreshRoute. {{ __('ui.no_money') }}
{{ __('ui.synthetic_note') }}
