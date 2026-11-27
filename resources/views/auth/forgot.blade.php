@extends('layouts.app')
@section('title', __('ui.forgot'))
@section('content')
<div class="login"><h1>{{ __('ui.forgot') }}</h1><x-errors/>
<form method="post" action="{{ route('password.email') }}">@csrf
    <x-field name="email" type="email" :label="__('ui.email')" required/>
    <button class="btn block" type="submit">{{ __('ui.send_reset') }}</button></form>
<p class="hint">{{ __('ui.reset_note') }}</p></div>
@endsection
