@extends('layouts.app')
@section('title', '419')
@section('content')
<div class="empty"><x-icon name="warn"/><h1>{{ __('ui.err_419') }}</h1><p>{{ __('ui.err_419_b') }}</p><a class="btn" href="{{ url('/') }}">{{ __('ui.go_home') }}</a></div>
@endsection
