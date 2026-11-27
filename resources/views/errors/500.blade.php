@extends('layouts.app')
@section('title', '500')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_500') }}</h1><p>{{ __('ui.err_500_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
