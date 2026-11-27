@extends('layouts.app')
@section('title', __('ui.my_produce'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.my_produce') }}</h1><a class="btn clay" href="{{ route('listings.create') }}"><x-icon name="plus" size="sm"/>{{ __('ui.sell_potatoes') }}</a></div>
<div data-queue-list="listing" data-reload-on-sync class="stack" style="margin-bottom:12px"></div>
<div class="stack">
@forelse($listings as $l)
    <a class="card" href="{{ route('listings.show', $l) }}" style="display:block;text-decoration:none;color:inherit"><x-listing-line :l="$l"/></a>
@empty
    <div class="empty card"><x-icon name="box"/><p>{{ __('ui.no_listings') }}</p></div>
@endforelse
</div>
@endsection
