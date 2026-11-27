@extends('layouts.app')
@section('title', __('ui.nav_overview'))
@section('content')
<h1>{{ __('ui.nav_overview') }}</h1>
<div class="grid g4">
@foreach($counts as $role => $c)<div class="card kpi"><div class="n">{{ $c }}</div><div class="l">{{ __('ui.role_'.$role) }}</div></div>@endforeach
<div class="card kpi"><div class="n">{{ $openListings }}/{{ $listings }}</div><div class="l">{{ __('ui.open_listings') }}</div></div>
<a class="card kpi link" href="{{ route('admin.deletions') }}"><div class="n">{{ $pendingDeletions }}</div><div class="l">{{ __('ui.nav_deletions') }}</div></a>
<a class="card kpi link" href="{{ route('admin.verify') }}"><div class="n">{{ $unverified }}</div><div class="l">{{ __('ui.nav_verify') }}</div></a>
</div>
@endsection
