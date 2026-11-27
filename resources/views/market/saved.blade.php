@extends('layouts.app')
@section('title', __('ui.nav_saved'))
@section('content')
<h1>{{ __('ui.nav_saved') }}</h1>
@forelse($saved as $s)
<div class="card stack" style="margin-bottom:12px">
    <div class="row between"><div><strong>{{ $s->supplier->name }}</strong><div class="muted small">{{ $s->supplier->place }}, {{ $s->supplier->county }} <x-origin :origin="$s->supplier->origin"/></div></div>
    <form method="post" action="{{ route('saved.toggle', $s->supplier_id) }}">@csrf<button class="btn sm ghost" type="submit">{{ __('ui.remove') }}</button></form></div>
    @foreach($open[$s->supplier_id] ?? [] as $l)<a href="{{ route('market.show', $l) }}">{{ $l->quantity_bags }} {{ __('ui.bags') }} - {{ \App\Support\Money::kes($l->price_minor) }} - {{ $l->harvest_date->toDateString() }}</a>@endforeach
</div>
@empty
<div class="empty card"><x-icon name="heart"/><p>{{ __('ui.no_saved') }}</p><a class="btn" href="{{ route('market.index') }}">{{ __('ui.nav_market') }}</a></div>
@endforelse
@endsection
