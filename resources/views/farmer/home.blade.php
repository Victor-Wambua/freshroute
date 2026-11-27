@extends('layouts.app')
@section('title', __('ui.nav_home'))
@section('content')
<h1>{{ __('ui.hello', ['name' => auth()->user()->name]) }}</h1>
<a class="btn clay big-cta block" href="{{ route('listings.create') }}" style="margin-bottom:16px"><x-icon name="plus"/>{{ __('ui.sell_potatoes') }}</a>
<div class="grid g4">
    <a class="card kpi link" href="{{ route('calendar.index') }}"><x-icon name="cal"/><div class="n">{{ $next ? $next->harvest_on->format('j M') : '-' }}</div><div class="l">{{ __('ui.next_harvest') }}</div></a>
    <a class="card kpi link" href="{{ route('enquiries.index') }}"><x-icon name="msg"/><div class="n">{{ $newEnquiries }}</div><div class="l">{{ __('ui.new_enquiries') }}</div></a>
    <a class="card kpi link" href="{{ route('prices.index') }}"><x-icon name="tag"/><div class="n">{{ $ref ? \App\Support\Money::kes($ref->price_minor) : '-' }}</div><div class="l">{{ __('ui.official_ref') }}@if($ref) <x-origin :origin="$ref->origin"/>@endif</div></a>
    <a class="card kpi link" href="{{ route('losses.index') }}"><x-icon name="loss"/><div class="n">{{ $lost }}</div><div class="l">{{ __('ui.lost_90') }}</div></a>
</div>
@if($demand)<div class="banner info" style="margin-top:16px"><x-icon name="tag" size="sm"/>{{ trans_choice('ui.demand_open', $demand, ['n' => $demand]) }}</div>@endif
<h2 style="margin-top:24px">{{ __('ui.my_produce') }}</h2>
@forelse($listings as $l)
    <a class="card" href="{{ route('listings.show', $l) }}" style="display:block;margin-bottom:12px;text-decoration:none;color:inherit"><x-listing-line :l="$l"/></a>
@empty
    <div class="empty card"><x-icon name="box"/><p>{{ __('ui.no_listings') }}</p><a class="btn" href="{{ route('listings.create') }}">{{ __('ui.sell_potatoes') }}</a></div>
@endforelse
<div data-queue-list="listing" class="stack"></div>
@endsection
