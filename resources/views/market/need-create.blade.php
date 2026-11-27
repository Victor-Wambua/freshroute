@extends('layouts.app')
@section('title', __('ui.post_need'))
@section('content')
<h1>{{ __('ui.post_need') }}</h1>
<p>{{ __('ui.need_explain') }}</p>
<x-errors/>
<form method="post" action="{{ route('needs.store') }}" data-offline="need" novalidate>@csrf
    <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <x-field name="quantity_bags" type="number" min="1" :label="__('ui.bags_needed')" required inputmode="numeric"/>
    <x-select name="county" :label="__('ui.county')" required :value="auth()->user()->county" :options="array_combine(config('freshroute.counties'), config('freshroute.counties'))"/>
    <x-field name="needed_by" type="date" :label="__('ui.needed_by')" min="{{ today()->toDateString() }}" required :value="today()->addDays(7)->toDateString()"/>
    <x-select name="min_grade" :label="__('ui.min_grade')" required value="B" :options="array_combine(config('freshroute.grades'), config('freshroute.grades'))"/>
    <x-field name="max_price_kes" type="number" step="1" :label="__('ui.max_price_bag')" :hint="__('ui.optional')" inputmode="numeric"/>
    <button class="btn clay block" type="submit">{{ __('ui.post_need') }}</button>
</form>
@endsection
