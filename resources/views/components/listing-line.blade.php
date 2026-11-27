@props(['l'])
<div class="listing">
    <div class="thumb">@if($l->photos->first())<img src="{{ route('photos.show', $l->photos->first()) }}" alt="" loading="lazy">@else<x-icon name="box" size="lg"/>@endif</div>
    <div>
        <div class="row between wrap"><strong>{{ $l->quantity_bags }} {{ __('ui.bags') }} - {{ $l->place }}</strong><span class="price">{{ \App\Support\Money::kes($l->price_minor) }}</span></div>
        <div class="small muted">{{ __('ui.harvest') }} {{ $l->harvest_date->toDateString() }} - {{ __('ui.grade') }} {{ $l->quality_grade }}</div>
        <div class="row wrap" style="margin-top:6px"><span class="badge {{ $l->status->value === 'active' ? 'b-ok' : 'b-neutral' }}">{{ __('ui.ls_'.$l->status->value) }}</span><x-origin :origin="$l->origin"/></div>
    </div>
</div>
