@props(['d'])
@php($s = $d->status->step())
@if($s === 0)<p><span class="badge b-neutral">{{ __('ui.ds_'.$d->status->value) }}</span></p>@else
<ol class="steps" aria-label="{{ __('ui.ds_'.$d->status->value) }}">@foreach(['requested','accepted','picked_up','delivered'] as $i => $k)<li class="{{ $s >= $i + 1 ? 'done' : '' }}">{{ __('ui.ds_'.$k) }}</li>@endforeach</ol>
@endif
