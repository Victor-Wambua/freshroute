@extends('layouts.app')
@section('title', __('ui.nav_overview'))
@section('content')
<h1>{{ $coop->name }}</h1>
<p>@if($coop->verified_at)<span class="badge b-ok">{{ __('ui.verified') }}</span>@else<span class="badge b-warn">{{ __('ui.coop_unverified') }}</span>@endif <x-origin :origin="$coop->origin"/></p>
<div class="grid g4">
    <div class="card kpi"><div class="n">{{ $members }}</div><div class="l">{{ __('ui.nav_members') }}</div></div>
    <div class="card kpi"><div class="n">{{ $listings->count() }}</div><div class="l">{{ __('ui.open_listings') }}</div></div>
    <div class="card kpi"><div class="n">{{ $listings->sum('quantity_bags') }}</div><div class="l">{{ __('ui.bags_available') }}</div></div>
    <div class="card kpi"><div class="n">{{ $loss['total_lost_bags'] }}</div><div class="l">{{ __('ui.total_lost') }}</div></div>
</div>
<div class="grid g2" style="margin-top:16px">
    <div class="card bars"><h2>{{ __('ui.supply_by_week') }}</h2>@php($max = max(1, $supplyByWeek->max() ?? 1))
        @forelse($supplyByWeek as $w => $b)<div class="bar"><span>{{ \Carbon\Carbon::parse($w)->format('j M') }}</span><div class="track"><div class="fill" style="width: {{ round($b * 100 / $max) }}%;background:var(--fr-primary)"></div></div><strong>{{ $b }}</strong></div>@empty<p class="muted">{{ __('ui.no_listings') }}</p>@endforelse</div>
    <div class="card bars"><h2>{{ __('ui.loss_by_stage') }}</h2>
        @foreach($loss['by_stage'] as $stage => $s)<div class="bar"><span>{{ __('ui.stage_'.$stage) }}</span><div class="track"><div class="fill" style="width: {{ min(100, $s['share']) }}%"></div></div><strong>{{ $s['bags'] }}</strong></div>@endforeach
        <p class="hint">{{ __('ui.loss_selfreported') }}</p></div>
</div>
@endsection
