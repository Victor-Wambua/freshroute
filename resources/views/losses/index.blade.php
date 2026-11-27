@extends('layouts.app')
@section('title', __('ui.nav_losses'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.nav_losses') }}</h1><a class="btn sm secondary" href="{{ route('losses.report') }}"><x-icon name="chart" size="sm"/>{{ __('ui.nav_reports') }}</a></div>
<p class="banner syn" role="note"><x-icon name="info" size="sm"/>{{ __('ui.loss_selfreported') }}</p>
<x-errors/>
<div class="card" style="margin-bottom:16px">
    <h2>{{ __('ui.record_loss') }}</h2>
    <form method="post" action="{{ route('losses.store') }}" data-offline="loss" novalidate>@csrf
        <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <input type="hidden" name="stage" id="f-stage" value="{{ old('stage') }}">
        <div class="lbl">{{ __('ui.stage') }}</div>
        <div class="bigchoice" style="margin-bottom:12px;grid-template-columns:repeat(auto-fit,minmax(96px,1fr))">
            @foreach(config('freshroute.stages') as $s)<button type="button" data-choice="#f-stage" data-value="{{ $s }}" aria-pressed="{{ old('stage') === $s ? 'true' : 'false' }}"><span class="small">{{ __('ui.stage_'.$s) }}</span></button>@endforeach
        </div>
        @error('stage')<div class="err" role="alert">{{ $message }}</div>@enderror
        <x-field name="bags" type="number" min="1" :label="__('ui.bags_lost')" required inputmode="numeric"/>
        <x-select name="category" :label="__('ui.loss_kind')" required :options="collect(config('freshroute.loss_categories'))->mapWithKeys(fn($c)=>[$c=>__('ui.cat_'.$c)])->all()"/>
        <x-select name="reason" :label="__('ui.reason')" required :options="collect(config('freshroute.loss_reasons'))->mapWithKeys(fn($c)=>[$c=>__('ui.reason_'.$c)])->all()"/>
        @if($lots->count())<x-select name="lot_id" :label="__('ui.link_lot')" :blank="__('ui.none')" :options="$lots->mapWithKeys(fn($l)=>[$l['lot']->id => $l['lot']->harvested_on->toDateString().' - '.$l['lot']->harvested_bags.' '.__('ui.bags')])->all()"/>@endif
        <button class="btn block" type="submit">{{ __('ui.save') }}</button>
    </form>
</div>
<div data-queue-list="loss" data-reload-on-sync class="stack" style="margin-bottom:12px"></div>

<h2>{{ __('ui.history') }} <span class="muted small">({{ $summary['total_lost_bags'] }} {{ __('ui.bags') }})</span></h2>
@forelse($events as $e)
    <div class="card tight row between" style="margin-bottom:8px"><div><strong>{{ __('ui.stage_'.$e->stage) }}</strong> - {{ $e->bags }} {{ __('ui.bags') }} <span class="muted small">{{ __('ui.cat_'.$e->category) }}, {{ __('ui.reason_'.$e->reason) }}</span></div><div class="small muted">{{ $e->occurred_on->toDateString() }} <x-origin :origin="$e->origin"/></div></div>
@empty<p class="muted">{{ __('ui.no_losses') }}</p>@endforelse

<h2 style="margin-top:24px">{{ __('ui.harvest_lots') }}</h2>
<p class="hint">{{ __('ui.lot_explain') }}</p>
@foreach($lots as $l)
<div class="card tight" style="margin-bottom:8px">
    <strong>{{ $l['lot']->harvested_on->toDateString() }}</strong>: {{ __('ui.harvested') }} {{ $l['sum']['harvested'] }} = {{ __('ui.sold') }} {{ $l['sum']['sold'] }} + {{ __('ui.cat_damaged') }} {{ $l['sum']['damaged'] }} + {{ __('ui.cat_spoiled') }} {{ $l['sum']['spoiled'] }} + {{ __('ui.cat_rejected') }} {{ $l['sum']['rejected'] }} + {{ __('ui.unaccounted') }} {{ $l['sum']['unaccounted'] }}
</div>
@endforeach
<form method="post" action="{{ route('losses.lots.store') }}" class="card" novalidate>@csrf
    <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <h3>{{ __('ui.add_lot') }}</h3>
    <x-field name="harvested_on" type="date" :label="__('ui.harvested_on')" max="{{ today()->toDateString() }}" required/>
    <x-field name="harvested_bags" type="number" min="1" :label="__('ui.harvested')" required inputmode="numeric"/>
    <x-field name="sold_bags" type="number" min="0" :label="__('ui.sold')" inputmode="numeric"/>
    <button class="btn secondary block" type="submit">{{ __('ui.save') }}</button>
</form>
@endsection
