@extends('layouts.app')
@section('title', __('ui.listing'))
@section('content')
<p><a href="{{ route('market.index') }}">&larr; {{ __('ui.nav_market') }}</a></p>
<h1>{{ $listing->quantity_bags }} {{ __('ui.bags') }} - {{ $listing->place }}</h1>
<x-errors/>
<div class="card stack">
    @if($listing->photos->count())<div class="photos">@foreach($listing->photos as $p)<img src="{{ route('photos.show', $p) }}" alt="{{ __('ui.photo') }}">@endforeach</div>@endif
    <div class="row wrap"><span class="price">{{ \App\Support\Money::kes($listing->price_minor) }}</span><span class="muted">/ {{ config('freshroute.unit.en') }}</span><x-origin :origin="$listing->origin"/></div>
    @if($ref)<div class="banner info"><x-icon name="chart" size="sm"/><span>{{ __('ui.official_ref') }}: {{ \App\Support\Money::kes($ref->price_minor) }} ({{ $ref->market }}, {{ $ref->collected_on->toDateString() }}) <x-origin :origin="$ref->origin"/></span></div>@endif
    <dl class="facts">
        <dt>{{ __('ui.supplier') }}</dt><dd>{{ $listing->user->displayName() }}</dd>
        <dt>{{ __('ui.place') }}</dt><dd>{{ $listing->place }}, {{ $listing->county }} <span class="muted small">({{ __('ui.approx_location') }})</span></dd>
        <dt>{{ __('ui.harvest_date') }}</dt><dd>{{ $listing->harvest_date->toDateString() }}</dd>
        <dt>{{ __('ui.available_to') }}</dt><dd>{{ $listing->available_to?->toDateString() ?? '-' }}</dd>
        <dt>{{ __('ui.grade') }}</dt><dd>{{ $listing->quality_grade }} <span class="muted small">({{ __('ui.grade_disclaimer_short') }})</span></dd>
        <dt>{{ __('ui.bags_free') }}</dt><dd>{{ $listing->availableBags() }}</dd>
        <dt>{{ __('ui.storage_req') }}</dt><dd>{{ $listing->storage_requirements ?: '-' }}</dd>
        @if($showPhone)<dt>{{ __('ui.phone') }}</dt><dd>{{ $listing->user->phone }}</dd>@endif
    </dl>
    @unless($showPhone)<p class="hint">{{ __('ui.phone_after_reply') }}</p>@endunless
    <form method="post" action="{{ route('saved.toggle', $listing->user_id) }}">@csrf<button class="btn secondary sm" type="submit"><x-icon name="heart" size="sm"/>{{ $saved ? __('ui.saved') : __('ui.save_supplier') }}</button></form>
</div>

@if($mine)
    <div class="card" style="margin-top:16px"><h2>{{ __('ui.your_enquiry') }}</h2>
        <x-enquiry-steps :e="$mine"/>
        <a class="btn" href="{{ route('enquiries.show', $mine) }}">{{ __('ui.view') }}</a>
        @if($mine->status->value === 'agreed')<a class="btn secondary" href="{{ route('transport.index') }}">{{ __('ui.arrange_transport') }}</a>@endif
    </div>
@endif
@if(! $mine || $mine->status->isTerminal())
<div class="card" style="margin-top:16px">
    <h2>{{ __('ui.send_enquiry') }}</h2>
    <form method="post" action="{{ route('enquiries.store') }}" data-offline="enquiry" novalidate>@csrf
        <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="listing_id" value="{{ $listing->id }}">
        <x-field name="quantity_bags" type="number" min="1" max="{{ $listing->availableBags() }}" :label="__('ui.bags_wanted')" :hint="__('ui.max_n', ['n' => $listing->availableBags()])" required inputmode="numeric"/>
        <div class="field"><label for="f-message">{{ __('ui.message') }}</label><textarea id="f-message" name="message" maxlength="500">{{ old('message') }}</textarea></div>
        <button class="btn clay block" type="submit">{{ __('ui.send_enquiry') }}</button>
    </form>
    <p class="hint">{{ __('ui.no_money') }}</p>
</div>
@endif
<div data-queue-list="enquiry" class="stack" style="margin-top:12px"></div>
@endsection
