@extends('layouts.app')
@section('title', '404')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_404') }}</h1><p>{{ __('ui.err_404_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
