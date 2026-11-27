@extends('layouts.app')
@section('title', __('ui.nav_my_storage'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.nav_my_storage') }}</h1><a class="btn clay" href="{{ route('storage.create') }}"><x-icon name="plus" size="sm"/>{{ __('ui.add_facility') }}</a></div>
@forelse($facilities as $f)
<div class="card stack" style="margin-bottom:12px"><div class="row between wrap"><h2 style="margin:0">{{ $f->name }}</h2><a class="btn sm secondary" href="{{ route('storage.edit', $f) }}">{{ __('ui.edit') }}</a></div>
    <div class="muted">{{ $f->place }}, {{ $f->county }}</div>
    <div>{{ __('ui.free') }}: <strong>{{ $f->free_bags }}</strong> / {{ $f->capacity_bags }} @if($f->temp_min_c !== null) - {{ $f->temp_min_c }}-{{ $f->temp_max_c }} C <span class="badge {{ $f->temperature_status === 'verified' ? 'b-ok' : 'b-warn' }}">{{ $f->temperature_status === 'verified' ? __('ui.verified') : __('ui.self_declared') }}</span>@endif</div></div>
@empty<div class="empty card"><x-icon name="store"/><p>{{ __('ui.no_facilities') }}</p></div>@endforelse
@endsection
