@extends('layouts.app')
@section('title', __('ui.nav_deliveries'))
@section('content')
<h1>{{ __('ui.nav_deliveries') }}</h1>
@forelse($deliveries as $d)
<a class="card" href="{{ route('deliveries.show', $d) }}" style="display:block;margin-bottom:12px;text-decoration:none;color:inherit">
    <div class="row between wrap"><strong>{{ $d->bags }} {{ __('ui.bags') }}: {{ $d->pickup_place }} &rarr; {{ $d->dropoff_place }}</strong><span class="small muted">{{ $d->pickup_date->toDateString() }}</span></div>
    <x-delivery-steps :d="$d"/>
</a>
@empty<div class="empty card"><x-icon name="truck"/><p>{{ __('ui.no_deliveries') }}</p></div>@endforelse
@endsection
