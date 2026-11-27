@extends('layouts.app')
@section('title', __('ui.nav_deliveries'))
@section('content')
@php($u = auth()->user())
<p><a href="{{ route('deliveries.index') }}">&larr; {{ __('ui.nav_deliveries') }}</a></p>
<h1>{{ __('ui.delivery') }} #{{ $d->id }}</h1>
<x-errors/>
<div class="card stack">
    <x-delivery-steps :d="$d"/>
    <dl class="facts"><dt>{{ __('ui.bags') }}</dt><dd>{{ $d->bags }}</dd><dt>{{ __('ui.pickup_place') }}</dt><dd>{{ $d->pickup_place }}</dd><dt>{{ __('ui.dropoff_place') }}</dt><dd>{{ $d->dropoff_place }}</dd><dt>{{ __('ui.pickup_date') }}</dt><dd>{{ $d->pickup_date->toDateString() }}</dd>
        <dt>{{ __('ui.transporter') }}</dt><dd>{{ $d->transporter->company_name }}</dd><dt>{{ __('ui.requester') }}</dt><dd>{{ $d->requester->displayName() }}</dd>
        <dt>{{ __('ui.phone') }}</dt><dd>{{ $u->id === $d->requester_id ? $d->transporter->user->phone : $d->requester->phone }}</dd><dt>{{ __('ui.price') }}</dt><dd>{{ \App\Support\Money::kes($d->transporter->base_price_minor) }} ({{ __('ui.paid_directly') }})</dd></dl>
    <p class="hint">{{ __('ui.manual_status_note') }}</p>
    <div class="table-actions">
    @foreach($d->status->next() as $to)
        @php($carrier = $u->id === $d->transporter->user_id)
        @if(($to->value === 'cancelled' && $u->id === $d->requester_id) || ($to->value !== 'cancelled' && $carrier))
            <form method="post" action="{{ route('deliveries.status', $d) }}">@csrf<input type="hidden" name="status" value="{{ $to->value }}"><button class="btn sm {{ in_array($to->value, ['declined','cancelled']) ? 'ghost' : '' }}" type="submit">{{ __('ui.dact_'.$to->value) }}</button></form>
        @endif
    @endforeach
    </div>
</div>
@endsection
