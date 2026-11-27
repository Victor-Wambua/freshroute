@extends('layouts.app')
@section('title', __('ui.offline_title'))
@section('content')
<div class="empty">
    <x-icon name="wifioff"/>
    <h1>{{ __('ui.offline_title') }}</h1>
    <p>{{ __('ui.offline_body') }}</p>
    <a class="btn" href="/home">{{ __('ui.try_again') }}</a>
</div>
@endsection
