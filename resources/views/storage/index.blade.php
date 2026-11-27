@extends('layouts.app')
@section('title', __('ui.nav_storage'))
@section('content')
@php($u = auth()->user())
<h1>{{ __('ui.nav_storage') }}</h1>
<x-errors/>
<form method="get" class="row wrap" style="margin-bottom:16px">
    <div style="min-width:180px"><label for="county" class="sr">{{ __('ui.county') }}</label><select id="county" name="county"><option value="">{{ __('ui.all_counties') }}</option>@foreach(config('freshroute.counties') as $c)<option @selected(($d['county'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="check" style="margin:0"><input type="checkbox" id="free" name="free" value="1" @checked(!empty($d['free']))><label for="free">{{ __('ui.free_only') }}</label></div>
    <button class="btn" type="submit">{{ __('ui.filter') }}</button>
</form>
<div class="grid g2">
@forelse($facilities as $f)
<article class="card stack">
    <div class="row between wrap"><h2 style="margin:0">{{ $f->name }}</h2><x-origin :origin="$f->origin"/></div>
    <div class="muted"><x-icon name="pin" size="sm"/> {{ $f->place }}, {{ $f->county }}</div>
    <dl class="facts"><dt>{{ __('ui.capacity') }}</dt><dd>{{ number_format($f->capacity_bags) }} {{ __('ui.bags') }}</dd>
        <dt>{{ __('ui.free') }}</dt><dd>@if($f->free_bags > 0){{ number_format($f->free_bags) }}@else<span class="badge b-danger">{{ __('ui.full') }}</span>@endif</dd>
        <dt>{{ __('ui.temperature') }}</dt><dd>{{ $f->temp_min_c !== null ? $f->temp_min_c.' - '.$f->temp_max_c.' C' : __('ui.ambient') }}
            @if($f->temp_min_c !== null)<span class="badge {{ $f->temperature_status === 'verified' ? 'b-ok' : 'b-warn' }}">{{ $f->temperature_status === 'verified' ? __('ui.verified') : __('ui.self_declared') }}</span>@endif</dd>
        <dt>{{ __('ui.price') }}</dt><dd>{{ \App\Support\Money::kes($f->price_minor) }} / {{ __('ui.bag_week') }}</dd>
        <dt>{{ __('ui.phone') }}</dt><dd>{{ $f->contact_phone ?: '-' }}</dd></dl>
    @if($u->hasRole(\App\Enums\Role::Farmer, \App\Enums\Role::Buyer) && $f->free_bags > 0)
    <form method="post" action="{{ route('storage.request', $f) }}" class="row wrap" style="align-items:flex-end">@csrf
        <div style="flex:1;min-width:90px"><label for="b{{ $f->id }}">{{ __('ui.bags') }}</label><input id="b{{ $f->id }}" type="number" name="bags" min="1" max="{{ $f->free_bags }}" required></div>
        <div style="flex:2;min-width:140px"><label for="d{{ $f->id }}">{{ __('ui.from') }}</label><input id="d{{ $f->id }}" type="date" name="from_date" min="{{ today()->toDateString() }}" value="{{ today()->addDays(2)->toDateString() }}" required></div>
        <button class="btn sm" type="submit">{{ __('ui.ask_space') }}</button></form>
    @endif
</article>
@empty<div class="empty card" style="grid-column:1/-1"><x-icon name="store"/><p>{{ __('ui.no_storage') }}</p></div>@endforelse
</div>
<p class="hint">{{ __('ui.temp_note') }}</p>
@endsection
