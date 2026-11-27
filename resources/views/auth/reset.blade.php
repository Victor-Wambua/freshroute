@extends('layouts.app')
@section('title', __('ui.reset_password'))
@section('content')
<div class="login"><h1>{{ __('ui.reset_password') }}</h1><x-errors/>
<form method="post" action="{{ route('password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-field name="email" type="email" :label="__('ui.email')" :value="$email" required/>
    <x-field name="password" type="password" :label="__('ui.password')" :hint="__('ui.password_hint')" required autocomplete="new-password"/>
    <x-field name="password_confirmation" type="password" :label="__('ui.password_confirm')" required autocomplete="new-password"/>
    <button class="btn block" type="submit">{{ __('ui.reset_password') }}</button></form></div>
@endsection
