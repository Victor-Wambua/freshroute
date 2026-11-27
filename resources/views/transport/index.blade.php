@extends('layouts.app')
@section('title', __('ui.nav_transport'))
@section('content')
@php($u = auth()->user())
<h1>{{ __('ui.nav_transport') }}</h1>
<x-errors/>
<div class="grid g2">
@forelse($profiles as $p)
<article class="card stack">
    <div class="row between wrap"><h2 style="margin:0">{{ $p->company_name }}</h2>@if($p->verified_at)<span class="badge b-ok">{{ __('ui.verified') }}</span>@endif <x-origin :origin="$p->origin"/></div>
    <dl class="facts"><dt>{{ __('ui.vehicle') }}</dt><dd>{{ __('ui.veh_'.$p->vehicle_type) }}</dd><dt>{{ __('ui.capacity') }}</dt><dd>{{ $p->capacity_bags }} {{ __('ui.bags') }}</dd><dt>{{ __('ui.routes') }}</dt><dd>{{ $p->routes }}</dd>
        <dt>{{ __('ui.price') }}</dt><dd>{{ \App\Support\Money::kes($p->base_price_minor) }}</dd><dt>{{ __('ui.availability') }}</dt><dd>{{ $p->availability_note ?: '-' }}</dd><dt>{{ __('ui.phone') }}</dt><dd>{{ $p->user->phone }}</dd></dl>
    @if($u->hasRole(\App\Enums\Role::Farmer, \App\Enums\Role::Buyer))
    <form method="post" action="{{ route('deliveries.store') }}" class="stack" novalidate>@csrf
        <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="transporter_profile_id" value="{{ $p->id }}">
        <div class="grid g2"><div><label for="bg{{ $p->id }}">{{ __('ui.bags') }}</label><input id="bg{{ $p->id }}" type="number" name="bags" min="1" max="{{ $p->capacity_bags }}" required></div>
            <div><label for="pd{{ $p->id }}">{{ __('ui.pickup_date') }}</label><input id="pd{{ $p->id }}" type="date" name="pickup_date" min="{{ today()->toDateString() }}" value="{{ today()->addDays(2)->toDateString() }}" required></div>
            <div><label for="pu{{ $p->id }}">{{ __('ui.pickup_place') }}</label><input id="pu{{ $p->id }}" name="pickup_place" maxlength="80" required value="{{ $u->place }}"></div>
            <div><label for="dr{{ $p->id }}">{{ __('ui.dropoff_place') }}</label><input id="dr{{ $p->id }}" name="dropoff_place" maxlength="80" required value="Nakuru town"></div></div>
        @if($listings->count())<div><label for="ls{{ $p->id }}">{{ __('ui.attach_listing') }}</label><select id="ls{{ $p->id }}" name="listing_id"><option value="">{{ __('ui.none') }}</option>@foreach($listings as $l)<option value="{{ $l->id }}">{{ $l->quantity_bags }} {{ __('ui.bags') }} - {{ $l->place }}</option>@endforeach</select></div>@endif
        <button class="btn" type="submit">{{ __('ui.request_delivery') }}</button>
    </form>
    @endif
</article>
@empty<div class="empty card" style="grid-column:1/-1"><x-icon name="truck"/><p>{{ __('ui.no_transporters') }}</p></div>@endforelse
</div>
<p class="hint">{{ __('ui.no_money') }}</p>
@endsection
