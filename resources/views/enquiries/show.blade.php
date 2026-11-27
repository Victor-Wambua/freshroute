@extends('layouts.app')
@section('title', __('ui.nav_enquiries'))
@section('content')
@php($u = auth()->user())
@php($mineAsFarmer = $u->id === $enquiry->listing->user_id)
<p><a href="{{ route('enquiries.index') }}">&larr; {{ __('ui.nav_enquiries') }}</a></p>
<h1>{{ __('ui.enquiry') }} #{{ $enquiry->id }}</h1>
<x-errors/>
<div class="card stack">
    <x-enquiry-steps :e="$enquiry"/>
    <dl class="facts">
        <dt>{{ __('ui.buyer') }}</dt><dd>{{ $enquiry->buyer->displayName() }}</dd>
        <dt>{{ __('ui.supplier') }}</dt><dd>{{ $enquiry->listing->user->name }}</dd>
        <dt>{{ __('ui.bags') }}</dt><dd>{{ $enquiry->quantity_bags }}</dd>
        <dt>{{ __('ui.price_per_bag') }}</dt><dd>{{ \App\Support\Money::kes($enquiry->listing->price_minor) }}</dd>
        <dt>{{ __('ui.status') }}</dt><dd>{{ __('ui.es_'.$enquiry->status->value) }}</dd>
        @if($mineAsFarmer || in_array($enquiry->status->value, ['replied','agreed']))<dt>{{ __('ui.phone') }}</dt><dd>{{ $mineAsFarmer ? $enquiry->buyer->phone : $enquiry->listing->user->phone }}</dd>@endif
    </dl>
    @if($enquiry->message)<div><div class="lbl">{{ __('ui.message') }}</div><p>{{ $enquiry->message }}</p></div>@endif
    @if($enquiry->reply)<div><div class="lbl">{{ __('ui.reply') }}</div><p>{{ $enquiry->reply }}</p></div>@endif
    <p class="hint">{{ __('ui.no_money') }}</p>
    <div class="table-actions">
        @if($mineAsFarmer && in_array($enquiry->status->value, ['sent','viewed','replied']))
            @if($enquiry->status->value === 'replied')<form method="post" action="{{ route('enquiries.status', $enquiry) }}">@csrf<input type="hidden" name="status" value="agreed"><button class="btn" type="submit">{{ __('ui.mark_agreed') }}</button></form>@endif
            <form method="post" action="{{ route('enquiries.status', $enquiry) }}">@csrf<input type="hidden" name="status" value="declined"><button class="btn ghost" type="submit">{{ __('ui.decline') }}</button></form>
        @endif
        @if(! $mineAsFarmer && ! $enquiry->status->isTerminal())
            <form method="post" action="{{ route('enquiries.status', $enquiry) }}">@csrf<input type="hidden" name="status" value="withdrawn"><button class="btn ghost" type="submit">{{ __('ui.withdraw') }}</button></form>
        @endif
        @if($enquiry->status->value === 'agreed')<a class="btn secondary" href="{{ route('transport.index') }}"><x-icon name="truck" size="sm"/>{{ __('ui.arrange_transport') }}</a>@endif
    </div>
</div>
@if($mineAsFarmer && in_array($enquiry->status->value, ['sent','viewed']))
<form method="post" action="{{ route('enquiries.reply', $enquiry) }}" class="card" style="margin-top:16px">@csrf
    <div class="field"><label for="f-reply">{{ __('ui.reply') }}</label><textarea id="f-reply" name="reply" maxlength="500" required>{{ old('reply') }}</textarea></div>
    <button class="btn block" type="submit">{{ __('ui.send_reply') }}</button></form>
@endif
@endsection
