@extends('layouts.app')
@section('title', '403')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_403') }}</h1><p>{{ __('ui.err_403_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
