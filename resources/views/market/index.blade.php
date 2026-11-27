@extends('layouts.app')
@section('title', __('ui.nav_market'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.nav_market') }}</h1><a class="btn secondary sm" href="{{ route('needs.create') }}"><x-icon name="tag" size="sm"/>{{ __('ui.post_need') }}</a></div>
<x-errors/>
<form method="get" class="toolbar" role="search">
    <div><label for="q" class="sr">{{ __('ui.search') }}</label><input id="q" type="search" name="q" value="{{ $d['q'] ?? '' }}" placeholder="{{ __('ui.search_ph') }}"></div>
    <button type="button" class="btn ghost sm filter-toggle" data-toggle="#more-filters" aria-expanded="false" aria-controls="more-filters">{{ __('ui.filter') }}</button>
    <div class="more-filters @if(!empty($d['county']) || !empty($d['min_bags']) || !empty($d['grade']) || (($d['sort'] ?? 'harvest') !== 'harvest')) on @endif" id="more-filters">
    <div><label for="county" class="sr">{{ __('ui.county') }}</label><select id="county" name="county"><option value="">{{ __('ui.all_counties') }}</option>@foreach(config('freshroute.counties') as $c)<option @selected(($d['county'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div><label for="min_bags" class="sr">{{ __('ui.min_bags') }}</label><input id="min_bags" type="number" min="1" name="min_bags" value="{{ $d['min_bags'] ?? '' }}" placeholder="{{ __('ui.min_bags') }}" inputmode="numeric"></div>
    <div><label for="sort" class="sr">{{ __('ui.sort') }}</label><select id="sort" name="sort">@foreach(['harvest','price_asc','price_desc','qty'] as $s)<option value="{{ $s }}" @selected(($d['sort'] ?? 'harvest') === $s)>{{ __('ui.sort_'.$s) }}</option>@endforeach</select></div>
    <fieldset style="border:0;padding:0;margin:0;grid-column:1/-1"><legend class="sr">{{ __('ui.grade') }}</legend><div class="chips">@foreach(['A','B','C'] as $g)<label class="chip" style="display:inline-flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="grade[]" value="{{ $g }}" @checked(in_array($g, $d['grade'] ?? []))> {{ __('ui.grade') }} {{ $g }}</label>@endforeach</div></fieldset>
    </div>
    <button class="btn" type="submit">{{ __('ui.search') }}</button>
</form>
@if($ref)<p class="small muted"><x-origin :origin="$ref->origin"/> {{ __('ui.official_ref') }}: {{ \App\Support\Money::kes($ref->price_minor) }} ({{ $ref->market }}, {{ $ref->collected_on->toDateString() }})</p>@endif
<p role="status" aria-live="polite" id="result-count"><strong>{{ $listings->total() }}</strong> {{ __('ui.results') }}</p>
<div class="row" style="margin-bottom:8px"><a id="cmp-link" class="btn sm secondary hide" href="#">{{ __('ui.compare') }}</a></div>
<div class="grid g2">
@forelse($listings as $l)
    @php($diff = $ref ? round(($l->price_minor - $ref->price_minor) * 100 / $ref->price_minor) : null)
    <article class="card stack">
        <div class="listing"><div class="thumb">@if($l->photos->first())<img src="{{ route('photos.show', $l->photos->first()) }}" alt="" loading="lazy">@else<x-icon name="box" size="lg"/>@endif</div>
        <div><div class="row between wrap"><strong>{{ $l->quantity_bags }} {{ __('ui.bags') }}</strong><span class="price">{{ \App\Support\Money::kes($l->price_minor) }}</span></div>
            <div class="small muted">{{ $l->place }}, {{ $l->county }} - {{ __('ui.harvest') }} {{ $l->harvest_date->toDateString() }}</div>
            <div class="small">{{ $l->user->name }} <span class="badge b-neutral">{{ __('ui.grade') }} {{ $l->quality_grade }}</span> <x-origin :origin="$l->origin"/></div>
            @if($diff !== null)<div class="small muted">{{ $diff >= 0 ? '+' : '' }}{{ $diff }}% {{ __('ui.vs_official') }}</div>@endif</div></div>
        <div class="row wrap">
            <a class="btn sm grow" href="{{ route('market.show', $l) }}">{{ __('ui.view') }}</a>
            <form method="post" action="{{ route('saved.toggle', $l->user_id) }}">@csrf<button class="btn sm secondary" type="submit" aria-pressed="{{ in_array($l->user_id, $saved) ? 'true' : 'false' }}"><x-icon name="heart" size="sm"/>{{ in_array($l->user_id, $saved) ? __('ui.saved') : __('ui.save_supplier') }}</button></form>
            <label class="check" style="margin:0"><input class="cmp" type="checkbox" value="{{ $l->id }}"> {{ __('ui.compare') }}</label>
        </div>
    </article>
@empty
    <div class="empty card" style="grid-column:1/-1"><x-icon name="search"/><p>{{ __('ui.no_results') }}</p><a class="btn" href="{{ route('market.index') }}">{{ __('ui.clear_filters') }}</a></div>
@endforelse
</div>
<div class="pager">{{ $listings->links('partials.pager') }}</div>
@endsection
