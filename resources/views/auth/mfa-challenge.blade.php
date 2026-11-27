@extends('layouts.app')
@section('title', __('ui.mfa_title'))
@section('content')
<div class="login"><h1>{{ __('ui.mfa_challenge') }}</h1><x-errors/>
<form method="post" action="{{ route('mfa.verify') }}">@csrf
    <x-field name="code" :label="__('ui.mfa_code_or_recovery')" required autocomplete="one-time-code" maxlength="20"/>
    <button class="btn block" type="submit">{{ __('ui.continue') }}</button></form>
<form method="post" action="{{ route('logout') }}" style="margin-top:16px">@csrf<button class="btn ghost block" type="submit">{{ __('ui.sign_out') }}</button></form></div>
@endsection
