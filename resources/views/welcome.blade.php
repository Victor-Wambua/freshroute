@extends('layouts.app')
@section('title', __('ui.tagline'))
@section('content')
<section class="hero">
    <h1>{{ __('ui.hero_title') }}</h1>
    <p>{{ __('ui.hero_body') }}</p>
    <div class="stack">
        <a class="btn clay big-cta block" href="{{ route('register') }}"><x-icon name="plus"/>{{ __('ui.create_account') }}</a>
        <a class="btn secondary block" href="{{ route('login') }}">{{ __('ui.sign_in') }}</a>
    </div>
</section>
<div class="grid g3">
    <div class="card"><x-icon name="box" size="lg"/><h3>{{ __('ui.pitch_sell') }}</h3><p class="muted">{{ __('ui.pitch_sell_b') }}</p></div>
    <div class="card"><x-icon name="truck" size="lg"/><h3>{{ __('ui.pitch_route') }}</h3><p class="muted">{{ __('ui.pitch_route_b') }}</p></div>
    <div class="card"><x-icon name="loss" size="lg"/><h3>{{ __('ui.pitch_loss') }}</h3><p class="muted">{{ __('ui.pitch_loss_b') }}</p></div>
</div>
<div class="card" style="margin-top:16px">
    <h2>{{ __('ui.nav_prices') }}</h2>
    @if($ref)
        <p><span class="price">{{ \App\Support\Money::kes($ref->price_minor) }}</span> <span class="muted">/ {{ $ref->unit }} - {{ $ref->market }}</span></p>
        <p><x-origin :origin="$ref->origin"/> <span class="small muted">{{ __('ui.collected') }} {{ $ref->collected_on->toDateString() }}</span></p>
    @else
        <p class="muted">{{ __('ui.no_official_prices') }}</p>
    @endif
    <a class="btn secondary" href="{{ route('prices.index') }}">{{ __('ui.see_prices') }}</a>
</div>
<p class="small muted" style="margin-top:16px">{{ __('ui.no_money') }} {{ __('ui.scope_note') }}</p>
@endsection
