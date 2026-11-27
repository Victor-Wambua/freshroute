@extends('layouts.app')
@section('title', __('ui.nav_needs'))
@section('content')
<p><a href="{{ route('needs.index') }}">&larr; {{ __('ui.nav_needs') }}</a></p>
<h1>{{ $need->quantity_bags }} {{ __('ui.bags') }} - {{ $need->county }}</h1>
<div class="card stack"><dl class="facts"><dt>{{ __('ui.needed_by') }}</dt><dd>{{ $need->needed_by->toDateString() }}</dd><dt>{{ __('ui.min_grade') }}</dt><dd>{{ $need->min_grade }}</dd><dt>{{ __('ui.max_price') }}</dt><dd>{{ $need->max_price_minor ? \App\Support\Money::kes($need->max_price_minor) : '-' }}</dd><dt>{{ __('ui.status') }}</dt><dd>{{ __('ui.need_'.$need->status) }}</dd></dl>
@if($need->status === 'open')<form method="post" action="{{ route('needs.close', $need) }}">@csrf<button class="btn ghost sm" type="submit">{{ __('ui.close_need') }}</button></form>@endif</div>
<h2 style="margin-top:24px">{{ __('ui.matching_supply') }}</h2>
<p class="hint">{{ __('ui.match_explain') }}</p>
@forelse($matches as $m)
<div class="card match" style="margin-bottom:8px">
    <div class="row between wrap"><a href="{{ route('market.show', $m['listing']) }}"><strong>{{ $m['listing']->user->name }}: {{ $m['listing']->quantity_bags }} {{ __('ui.bags') }}, {{ $m['listing']->place }}</strong></a><span class="score">{{ $m['score'] }}/100</span></div>
    <ul class="parts">@foreach($m['parts'] as $p)<li><span>{{ $p['text'] }}</span><span>{{ $p['points'] }}/{{ $p['max'] }}</span></li>@endforeach</ul>
</div>
@empty<p class="muted">{{ __('ui.no_matches') }}</p>@endforelse
@endsection
