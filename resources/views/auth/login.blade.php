@extends('layouts.app')
@section('title', __('ui.sign_in'))
@section('content')
<div class="login">
    <h1>{{ __('ui.sign_in') }}</h1>
    <x-errors/>
    <form method="post" action="{{ route('login') }}" novalidate>
        @csrf
        <x-field name="email" type="email" :label="__('ui.email')" autocomplete="username" required inputmode="email"/>
        <x-field name="password" type="password" :label="__('ui.password')" autocomplete="current-password" required/>
        <div class="check"><input type="checkbox" id="remember" name="remember" value="1"><label for="remember">{{ __('ui.remember') }}</label></div>
        <button class="btn block" type="submit">{{ __('ui.sign_in') }}</button>
    </form>
    <p style="margin-top:16px"><a href="{{ route('password.request') }}">{{ __('ui.forgot') }}</a></p>
    <p>{{ __('ui.no_account') }} <a href="{{ route('register') }}">{{ __('ui.create_account') }}</a></p>
    <p class="hint">{{ __('ui.login_note') }}</p>
</div>
@endsection
