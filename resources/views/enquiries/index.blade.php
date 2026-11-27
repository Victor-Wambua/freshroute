@extends('layouts.app')
@section('title', __('ui.nav_enquiries'))
@section('content')
<h1>{{ __('ui.nav_enquiries') }}</h1>
<p class="hint">{{ __('ui.no_money') }}</p>
<div data-queue-list="enquiry" data-reload-on-sync class="stack" style="margin-bottom:12px"></div>
@forelse($enquiries as $e)
<a class="card" href="{{ route('enquiries.show', $e) }}" style="display:block;margin-bottom:12px;text-decoration:none;color:inherit">
    <div class="row between wrap"><strong>{{ auth()->user()->hasRole(\App\Enums\Role::Buyer) ? $e->listing->user->name : $e->buyer->displayName() }} - {{ $e->quantity_bags }} {{ __('ui.bags') }}</strong><span class="small muted">{{ $e->created_at->diffForHumans() }}</span></div>
    <x-enquiry-steps :e="$e"/>
    @if($e->message)<div class="small muted">{{ \Illuminate\Support\Str::limit($e->message, 90) }}</div>@endif
</a>
@empty<div class="empty card"><x-icon name="msg"/><p>{{ __('ui.no_enquiries') }}</p></div>@endforelse
@endsection
