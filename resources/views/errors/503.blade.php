@extends('layouts.app')
@section('title', '503')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_503') }}</h1><p>{{ __('ui.err_503_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
