@props(['origin'])
@php($o = $origin ?? 'user_submitted')
@if($o === 'synthetic')
<span class="badge tag-syn"><x-icon name="info" size="sm"/>{{ __('ui.origin_synthetic') }}</span>
@elseif($o === 'officially_published')
<span class="badge b-official">{{ __('ui.origin_official') }}</span>
@else
<span class="badge b-user">{{ __('ui.origin_user') }}</span>
@endif
