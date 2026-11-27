@extends('layouts.app')
@section('title', __('ui.my_produce'))
@section('content')
<p><a href="{{ route('listings.index') }}">&larr; {{ __('ui.my_produce') }}</a></p>
<h1>{{ $listing->quantity_bags }} {{ __('ui.bags') }} - {{ $listing->place }}</h1>
<x-errors/>
<div class="card stack">
    <div class="row wrap"><span class="badge {{ $listing->status->value === 'active' ? 'b-ok' : 'b-neutral' }}">{{ __('ui.ls_'.$listing->status->value) }}</span><x-origin :origin="$listing->origin"/></div>
    <dl class="facts">
        <dt>{{ __('ui.price_per_bag') }}</dt><dd>{{ \App\Support\Money::kes($listing->price_minor) }}</dd>
        <dt>{{ __('ui.harvest_date') }}</dt><dd>{{ $listing->harvest_date->toDateString() }}</dd>
        <dt>{{ __('ui.available_to') }}</dt><dd>{{ $listing->available_to?->toDateString() ?? '-' }}</dd>
        <dt>{{ __('ui.grade') }}</dt><dd>{{ $listing->quality_grade }}</dd>
        <dt>{{ __('ui.place') }}</dt><dd>{{ $listing->place }}, {{ $listing->county }}</dd>
        <dt>{{ __('ui.storage_req') }}</dt><dd>{{ $listing->storage_requirements ?: '-' }}</dd>
        <dt>{{ __('ui.bags_free') }}</dt><dd>{{ $listing->availableBags() }}</dd>
    </dl>
    @if($listing->photos->count())<div class="photos">@foreach($listing->photos as $p)<div><img src="{{ route('photos.show', $p) }}" alt="{{ __('ui.photo') }}">
        <form method="post" action="{{ route('photos.destroy', $p) }}">@csrf @method('DELETE')<button class="btn link" type="submit">{{ __('ui.remove') }}</button></form></div>@endforeach</div>@endif
    <div class="table-actions">
        @foreach($listing->status->next() as $to)
            <form method="post" action="{{ route('listings.status', $listing) }}">@csrf<input type="hidden" name="status" value="{{ $to->value }}"><button class="btn sm {{ in_array($to->value, ['withdrawn','expired']) ? 'ghost' : '' }}" type="submit">{{ __('ui.act_'.$to->value) }}</button></form>
        @endforeach
        @if($listing->status->next() !== [])<a class="btn sm secondary" href="{{ route('listings.edit', $listing) }}">{{ __('ui.edit') }}</a>@endif
        @if(in_array($listing->status->value, ['draft','withdrawn']) && $enquiries->isEmpty())
            <form method="post" action="{{ route('listings.destroy', $listing) }}">@csrf @method('DELETE')<button class="btn sm danger" type="submit">{{ __('ui.delete') }}</button></form>
        @endif
    </div>
    @if($listing->status->next() !== [] && $listing->photos->count() < 4)
        <form method="post" action="{{ route('listings.photos.store', $listing) }}" enctype="multipart/form-data" class="field">@csrf
            <label for="f-photo">{{ __('ui.add_photo') }}</label><input id="f-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-shrink required>
            <button class="btn sm secondary" type="submit" style="margin-top:8px">{{ __('ui.upload') }}</button></form>
    @endif
</div>

<h2 style="margin-top:24px">{{ __('ui.nav_enquiries') }}</h2>
@forelse($enquiries as $e)
    <a class="card" style="display:block;margin-bottom:8px;text-decoration:none;color:inherit" href="{{ route('enquiries.show', $e) }}"><div class="row between"><strong>{{ $e->buyer->displayName() }} - {{ $e->quantity_bags }} {{ __('ui.bags') }}</strong><span class="badge b-neutral">{{ __('ui.es_'.$e->status->value) }}</span></div></a>
@empty<p class="muted">{{ __('ui.no_enquiries') }}</p>@endforelse

<h2 style="margin-top:24px">{{ __('ui.matching_demand') }}</h2>
<p class="hint">{{ __('ui.match_explain') }}</p>
@forelse($matches as $m)
    <div class="card match" style="margin-bottom:8px">
        <div class="row between"><strong>{{ $m['need']->buyer->displayName() }}: {{ $m['need']->quantity_bags }} {{ __('ui.bags') }}, {{ $m['need']->county }}</strong><span class="score">{{ $m['score'] }}/100</span></div>
        <ul class="parts">@foreach($m['parts'] as $p)<li><span>{{ $p['text'] }}</span><span>{{ $p['points'] }}/{{ $p['max'] }}</span></li>@endforeach</ul>
    </div>
@empty<p class="muted">{{ __('ui.no_matches') }}</p>@endforelse
@endsection
