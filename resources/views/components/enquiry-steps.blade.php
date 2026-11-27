@props(['e'])
@php($s = $e->status->step())
@if($s === 0)
    <p><span class="badge b-neutral">{{ __('ui.es_'.$e->status->value) }}</span></p>
@else
<ol class="steps" aria-label="{{ __('ui.es_'.$e->status->value) }}">
    <li class="{{ $s >= 1 ? 'done' : '' }}">{{ __('ui.es_sent') }}</li><li class="{{ $s >= 2 ? 'done' : '' }}">{{ __('ui.es_replied') }}</li><li class="{{ $s >= 3 ? 'done' : '' }}">{{ __('ui.es_agreed') }}</li>
</ol>
@endif
