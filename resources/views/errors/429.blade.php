@extends('layouts.app')
@section('title', '429')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_429') }}</h1><p>{{ __('ui.err_429_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
