@extends('layouts.app')
@section('title', __('ui.nav_harvest'))
@section('content')
@php($first = $month->copy()->startOfMonth())
@php($days = $first->daysInMonth)
@php($lead = ($first->dayOfWeekIso + 6) % 7)
@php($byDay = $entries->groupBy(fn($e) => $e->harvest_on->day))
<h1>{{ __('ui.nav_harvest') }}</h1>
<x-errors/>
<div class="row between" style="margin-bottom:12px">
    <a class="btn sm secondary" href="{{ route('calendar.index', ['m' => $month->copy()->subMonth()->format('Y-m')]) }}">&larr;</a>
    <strong>{{ $month->translatedFormat('F Y') }}</strong>
    <a class="btn sm secondary" href="{{ route('calendar.index', ['m' => $month->copy()->addMonth()->format('Y-m')]) }}">&rarr;</a>
</div>
<div class="cal" role="grid" aria-label="{{ $month->translatedFormat('F Y') }}">
    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div class="hd" role="columnheader">{{ $d }}</div>@endforeach
    @for($i = 0; $i < $lead; $i++)<div class="mt" aria-hidden="true"></div>@endfor
    @for($d = 1; $d <= $days; $d++)
        <div role="gridcell" class="{{ $byDay->has($d) ? 'ev' : '' }} {{ $first->copy()->day($d)->isToday() ? 'today' : '' }}">{{ $d }}@if($byDay->has($d))<span class="sr"> {{ __('ui.harvest_day') }}</span>@endif</div>
    @endfor
</div>
<h2 style="margin-top:24px">{{ __('ui.upcoming') }}</h2>
@forelse($upcoming as $e)
    <div class="card row between" style="margin-bottom:8px"><div><strong>{{ $e->harvest_on->translatedFormat('j M Y') }}</strong> - {{ $e->expected_bags ?? '-' }} {{ __('ui.bags') }} <span class="muted">{{ $e->note }}</span></div>
        <form method="post" action="{{ route('calendar.destroy', $e) }}">@csrf @method('DELETE')<button class="btn link" type="submit">{{ __('ui.delete') }}</button></form></div>
@empty<p class="muted">{{ __('ui.no_harvests') }}</p>@endforelse
<h2>{{ __('ui.add_harvest') }}</h2>
<form method="post" action="{{ route('calendar.store') }}" class="card">@csrf
    <x-field name="harvest_on" type="date" :label="__('ui.harvest_date')" min="{{ today()->toDateString() }}" required/>
    <x-field name="expected_bags" type="number" min="1" :label="__('ui.bags')" inputmode="numeric"/>
    <x-field name="note" :label="__('ui.note')" maxlength="120"/>
    <button class="btn block" type="submit">{{ __('ui.save') }}</button></form>
<p class="hint">{{ __('ui.reminder_note') }}</p>
@endsection
