@extends('layouts.app')
@section('title', __('ui.nav_farm'))
@section('content')
<h1>{{ __('ui.nav_farm') }}</h1>
<x-errors/>
@foreach($farms as $f)
<div class="card stack" style="margin-bottom:12px">
    <div class="row between"><h2 style="margin:0">{{ $f->name }}</h2><form method="post" action="{{ route('farms.destroy', $f) }}">@csrf @method('DELETE')<button class="btn link" type="submit">{{ __('ui.delete') }}</button></form></div>
    <div class="muted">{{ $f->place }}, {{ $f->county }} @if($f->area_acres) - {{ $f->area_acres }} {{ __('ui.acres') }}@endif</div>
    @foreach($f->crops as $c)<div class="row"><x-icon name="box" size="sm"/>{{ $c->commodity->name() }} {{ $c->variety }} - {{ __('ui.capacity') }}: {{ $c->capacity_bags }} {{ __('ui.bags') }}</div>@endforeach
    <form method="post" action="{{ route('farms.crops.store', $f) }}" class="grid g3">@csrf
        <x-field name="variety" :label="__('ui.variety')"/><x-field name="capacity_bags" type="number" min="1" :label="__('ui.capacity_bags')" required/>
        <div class="field" style="align-self:end"><button class="btn secondary block" type="submit">{{ __('ui.add_crop') }}</button></div></form>
</div>
@endforeach
<h2>{{ __('ui.add_farm') }}</h2>
<form method="post" action="{{ route('farms.store') }}" class="card">@csrf
    <x-field name="name" :label="__('ui.farm_name')" required/>
    <x-select name="county" :label="__('ui.county')" required :options="array_combine(config('freshroute.counties'), config('freshroute.counties'))"/>
    <x-field name="place" :label="__('ui.place')" required/>
    <x-field name="area_acres" type="number" step="0.1" :label="__('ui.area_acres')"/>
    <button class="btn block" type="submit">{{ __('ui.save') }}</button></form>
@endsection
