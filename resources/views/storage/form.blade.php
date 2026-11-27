@extends('layouts.app')
@section('title', __('ui.add_facility'))
@section('content')
<h1>{{ $facility->exists ? __('ui.edit') : __('ui.add_facility') }}</h1>
<x-errors/>
<form method="post" action="{{ $facility->exists ? route('storage.update', $facility) : route('storage.store') }}" novalidate>@csrf @if($facility->exists) @method('PUT') @endif
    <x-field name="name" :label="__('ui.facility_name')" :value="$facility->name" required/>
    <x-select name="county" :label="__('ui.county')" required :value="$facility->county ?? auth()->user()->county" :options="array_combine(config('freshroute.counties'), config('freshroute.counties'))"/>
    <x-field name="place" :label="__('ui.place')" :value="$facility->place ?? auth()->user()->place" required/>
    <x-field name="capacity_bags" type="number" min="1" :label="__('ui.capacity')" :value="$facility->capacity_bags" required inputmode="numeric"/>
    <x-field name="free_bags" type="number" min="0" :label="__('ui.free')" :value="$facility->free_bags" required inputmode="numeric"/>
    <div class="grid g2"><x-field name="temp_min_c" type="number" :label="__('ui.temp_min')" :value="$facility->temp_min_c"/><x-field name="temp_max_c" type="number" :label="__('ui.temp_max')" :value="$facility->temp_max_c"/></div>
    <p class="hint">{{ __('ui.temp_declare_note') }}</p>
    <x-field name="price_kes" type="number" step="1" min="0" :label="__('ui.price_bag_week')" :value="$facility->exists ? intdiv($facility->price_minor, 100) : null" required inputmode="numeric"/>
    <x-field name="contact_phone" type="tel" :label="__('ui.phone')" :value="$facility->contact_phone ?? auth()->user()->phone"/>
    <button class="btn block" type="submit">{{ __('ui.save') }}</button>
</form>
@endsection
