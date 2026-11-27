@extends('layouts.app')
@section('title', __('ui.edit'))
@section('content')
<p><a href="{{ route('listings.show', $listing) }}">&larr; {{ __('ui.back') }}</a></p>
<h1>{{ __('ui.edit') }}</h1>
<x-errors/>
<form method="post" action="{{ route('listings.update', $listing) }}" novalidate>@csrf @method('PUT')
    <x-field name="quantity_bags" type="number" :label="__('ui.bags')" :value="$listing->quantity_bags" min="1" max="{{ config('freshroute.bags.max') }}" required inputmode="numeric"/>
    <x-field name="harvest_date" type="date" :label="__('ui.harvest_date')" :value="$listing->harvest_date->toDateString()" required/>
    <x-field name="available_to" type="date" :label="__('ui.available_to')" :value="$listing->available_to?->toDateString()"/>
    <x-select name="quality_grade" :label="__('ui.grade')" required :value="$listing->quality_grade" :options="array_combine(config('freshroute.grades'), config('freshroute.grades'))"/>
    <x-field name="price_kes" type="number" step="1" :label="__('ui.price_per_bag')" :value="intdiv($listing->price_minor, 100)" required inputmode="numeric"/>
    <x-select name="county" :label="__('ui.county')" required :value="$listing->county" :options="array_combine(config('freshroute.counties'), config('freshroute.counties'))"/>
    <x-field name="place" :label="__('ui.place')" :value="$listing->place" required/>
    <x-field name="variety" :label="__('ui.variety')" :value="$listing->variety"/>
    <x-field name="storage_requirements" :label="__('ui.storage_req')" :value="$listing->storage_requirements"/>
    <button class="btn block" type="submit">{{ __('ui.save') }}</button>
</form>
@endsection
