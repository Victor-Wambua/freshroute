@extends('layouts.app')
@section('title', __('ui.mfa_title'))
@section('content')
<div class="login">
<h1>{{ __('ui.mfa_title') }}</h1>
<x-errors/>
@if($codes)
    <div class="banner ok"><x-icon name="check" size="sm"/>{{ __('ui.mfa_enabled') }}</div>
    <h2>{{ __('ui.recovery_codes') }}</h2>
    <p>{{ __('ui.recovery_note') }}</p>
    <ul class="mono card">@foreach($codes as $c)<li>{{ $c }}</li>@endforeach</ul>
    <a class="btn block" href="{{ route('home') }}">{{ __('ui.continue') }}</a>
@elseif($enabled)
    <div class="banner ok"><x-icon name="check" size="sm"/>{{ __('ui.mfa_enabled') }}</div>
@else
    <p>{{ __('ui.mfa_intro') }}</p>
    <div class="card stack">
        <div><div class="lbl">{{ __('ui.mfa_secret') }}</div><div class="mono" id="mfa-secret">{{ $secret }}</div></div>
        <div><div class="lbl">{{ __('ui.mfa_uri') }}</div><div class="mono small">{{ $uri }}</div></div>
    </div>
    <p class="hint">{{ __('ui.mfa_no_qr') }}</p>
    <form method="post" action="{{ route('mfa.confirm') }}">@csrf
        <x-field name="code" :label="__('ui.mfa_code')" required inputmode="numeric" autocomplete="one-time-code" maxlength="10"/>
        <button class="btn block" type="submit">{{ __('ui.mfa_confirm') }}</button>
    </form>
@endif
<form method="post" action="{{ route('logout') }}" style="margin-top:16px">@csrf<button class="btn ghost block" type="submit">{{ __('ui.sign_out') }}</button></form>
</div>
@endsection
