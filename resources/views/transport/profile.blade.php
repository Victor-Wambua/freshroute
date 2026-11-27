@extends('layouts.app')
@section('title', __('ui.nav_my_vehicle'))
@section('content')
<h1>{{ __('ui.nav_my_vehicle') }}</h1>
<x-errors/>
<form method="post" action="{{ route('transport.profile.save') }}" novalidate>@csrf
    <x-field name="company_name" :label="__('ui.business_name')" :value="$profile->company_name ?? auth()->user()->business_name" required/>
    <x-select name="vehicle_type" :label="__('ui.vehicle')" required :value="$profile->vehicle_type" :options="collect(config('freshroute.vehicle_types'))->mapWithKeys(fn($v)=>[$v=>__('ui.veh_'.$v)])->all()"/>
    <x-field name="capacity_bags" type="number" min="1" :label="__('ui.capacity')" :value="$profile->capacity_bags" required inputmode="numeric"/>
    <x-field name="routes" :label="__('ui.routes')" :hint="__('ui.routes_hint')" :value="$profile->routes" required maxlength="300"/>
    <x-field name="price_kes" type="number" step="1" min="0" :label="__('ui.base_price')" :value="$profile->exists ? intdiv($profile->base_price_minor, 100) : null" required inputmode="numeric"/>
    <x-field name="availability_note" :label="__('ui.availability')" :value="$profile->availability_note" maxlength="120"/>
    <div class="check"><input type="checkbox" id="active" name="active" value="1" @checked(old('active', $profile->exists ? $profile->active : true))><label for="active">{{ __('ui.listed_active') }}</label></div>
    <button class="btn block" type="submit">{{ __('ui.save') }}</button>
</form>
@endsection
