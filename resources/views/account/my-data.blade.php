@extends('layouts.app')
@section('title', __('ui.nav_mydata'))
@section('content')
<h1>{{ __('ui.nav_mydata') }}</h1>
<x-errors/>
<div class="card stack" style="margin-bottom:16px"><h2>{{ __('ui.export') }}</h2><p>{{ __('ui.export_note') }}</p>
    <a class="btn" href="{{ route('my-data.json') }}"><x-icon name="download" size="sm"/>{{ __('ui.export_json') }}</a>
    <div class="chips">@foreach($datasets as $d)<a class="chip linkchip" href="{{ route('my-data.csv', ['dataset' => $d]) }}">CSV: {{ __('ui.ds_'.$d) }}</a>@endforeach</div></div>
<div class="card stack"><h2>{{ __('ui.delete_account') }}</h2>
@if($pending)
    <div class="banner off"><x-icon name="warn" size="sm"/>{{ __('ui.deletion_pending', ['date' => $pending->due_at->toDateString()]) }}</div>
    <form method="post" action="{{ route('my-data.cancel') }}">@csrf @method('DELETE')<button class="btn" type="submit">{{ __('ui.cancel_deletion') }}</button></form>
@else
    <p>{{ __('ui.delete_note', ['days' => config('freshroute.deletion_grace_days')]) }}</p>
    <form method="post" action="{{ route('my-data.delete') }}" novalidate>@csrf
        <x-field name="confirm" :label="__('ui.type_delete')" required autocomplete="off"/>
        <x-field name="password" type="password" :label="__('ui.password')" required autocomplete="current-password"/>
        <button class="btn danger" type="submit">{{ __('ui.request_deletion') }}</button></form>
@endif
</div>
<p class="hint">{{ __('ui.privacy_note') }}</p>
@endsection
