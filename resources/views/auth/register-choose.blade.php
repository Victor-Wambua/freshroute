@extends('layouts.app')
@section('title', __('ui.create_account'))
@section('content')
<div class="login">
    <h1>{{ __('ui.who_are_you') }}</h1>
    @foreach([['farmer','user'],['buyer','search'],['coop_admin','users'],['storage_provider','store'],['transporter','truck']] as [$r,$i])
        <a class="rolecard" href="{{ route('register.role', $r) }}" style="text-decoration:none;color:inherit"><x-icon :name="$i" size="lg"/><span>{{ __('ui.role_'.$r) }}<br><span class="muted small">{{ __('ui.role_'.$r.'_d') }}</span></span></a>
    @endforeach
    <p class="hint">{{ __('ui.scope_note') }}</p>
    <p>{{ __('ui.have_account') }} <a href="{{ route('login') }}">{{ __('ui.sign_in') }}</a></p>
</div>
@endsection
