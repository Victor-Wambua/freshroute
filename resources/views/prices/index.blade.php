@extends('layouts.app')
@section('title', __('ui.nav_prices'))
@section('content')
@php($u = auth()->user())
<h1>{{ __('ui.nav_prices') }} - {{ $commodity->name() }}</h1>
<nav class="tabs" aria-label="{{ __('ui.price_origin') }}">
    <a class="btn secondary" style="flex:1" href="{{ route('prices.index', ['market' => $market]) }}" @if($tab === 'official') aria-current="page" @endif><span class="badge b-official">{{ __('ui.origin_official') }}</span></a>
    <a class="btn secondary" style="flex:1" href="{{ route('prices.index', ['tab' => 'user', 'market' => $market]) }}" @if($tab === 'user') aria-current="page" @endif><span class="badge b-user">{{ __('ui.origin_user') }}</span></a>
</nav>
<nav class="subnav" aria-label="{{ __('ui.market') }}">@foreach(config('freshroute.markets') as $m)<a class="chip linkchip" href="{{ route('prices.index', ['tab' => $tab, 'market' => $m]) }}" @if($m === $market) aria-current="true" @endif>{{ $m }}</a>@endforeach</nav>
<x-errors/>

@if($tab === 'official')
    <div class="banner info"><x-icon name="info" size="sm"/><span>{{ __('ui.official_explain') }}</span></div>
    @php($latestHere = $latest[$market] ?? null)
    @if($latestHere)
        <div class="card stack" style="margin-bottom:16px">
            <div class="row between wrap"><div><span class="price" style="font-size:2rem">{{ \App\Support\Money::kes($latestHere->price_minor) }}</span> <span class="muted">/ {{ $latestHere->unit }}</span></div><x-origin :origin="$latestHere->origin"/></div>
            <dl class="facts"><dt>{{ __('ui.market') }}</dt><dd>{{ $latestHere->market }}</dd><dt>{{ __('ui.collected') }}</dt><dd>{{ $latestHere->collected_on->toDateString() }}</dd>
                <dt>{{ __('ui.source') }}</dt><dd>{{ $latestHere->source->name }}</dd><dt>{{ __('ui.source_url') }}</dt><dd class="mono small">{{ $latestHere->source_url }}</dd>
                <dt>{{ __('ui.licence') }}</dt><dd><span class="badge {{ $latestHere->licence_status === 'synthetic' ? 'tag-syn' : 'b-official' }}">{{ __('ui.licence_'.$latestHere->licence_status) }}</span></dd></dl>
            @if($stats->isStale($latestHere))<div class="banner off"><x-icon name="warn" size="sm"/>{{ __('ui.stale_warning', ['n' => config('freshroute.price_stale_days')]) }}</div>@endif
        </div>
        <div class="card" style="margin-bottom:16px"><h2>{{ __('ui.history_12w') }} - {{ $market }}</h2>
            <x-price-chart :series="$series->map(fn($p) => ['label' => $p->collected_on->format('j M'), 'value' => $p->price_minor])->all()" :title="__('ui.history_12w').' - '.$market"/></div>
        <h2>{{ __('ui.all_markets') }}</h2>
        <div class="tablewrap"><table><thead><tr><th scope="col">{{ __('ui.market') }}</th><th scope="col" class="numcol">{{ __('ui.price') }}</th><th scope="col">{{ __('ui.collected') }}</th><th scope="col" class="hide-sm">{{ __('ui.source') }}</th><th scope="col">{{ __('ui.licence') }}</th></tr></thead><tbody>
        @foreach($latest as $m => $p)<tr><th scope="row">{{ $m }}</th><td class="numcol">{{ \App\Support\Money::kes($p->price_minor) }}</td><td>{{ $p->collected_on->toDateString() }}</td><td class="hide-sm">{{ $p->source->name }}</td><td>{{ __('ui.licence_'.$p->licence_status) }}</td></tr>@endforeach
        </tbody></table></div>
    @else
        <div class="empty card"><x-icon name="chart"/><p>{{ __('ui.no_official_prices') }}</p></div>
    @endif
@else
    <div class="banner user"><x-icon name="warn" size="sm"/><span>{{ __('ui.user_explain') }}</span></div>
    @if($userMedian)<p><strong>{{ __('ui.user_median') }}:</strong> {{ \App\Support\Money::kes($userMedian) }} <span class="badge b-user">{{ __('ui.origin_user') }}</span> <span class="small muted">{{ __('ui.not_official_avg') }}</span></p>@endif
    <div class="tablewrap"><table><thead><tr><th scope="col">{{ __('ui.market') }}</th><th scope="col" class="numcol">{{ __('ui.price') }}</th><th scope="col">{{ __('ui.observed') }}</th><th scope="col">{{ __('ui.note') }}</th><th scope="col">{{ __('ui.origin') }}</th></tr></thead><tbody>
    @forelse($userRows as $r)<tr><th scope="row">{{ $r->market }}</th><td class="numcol">{{ \App\Support\Money::kes($r->price_minor) }}</td><td>{{ $r->observed_on->toDateString() }}</td><td>{{ $r->note }}</td><td><x-origin :origin="$r->origin"/></td></tr>
    @empty<tr><td colspan="5" class="muted">{{ __('ui.no_user_prices') }}</td></tr>@endforelse
    </tbody></table></div>
    @auth
    <h2 style="margin-top:24px">{{ __('ui.submit_price') }}</h2>
    <form method="post" action="{{ route('prices.user.store') }}" data-offline="price" class="card" novalidate>@csrf
        <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <x-select name="market" :label="__('ui.market')" required :value="$market" :options="array_combine(config('freshroute.markets'), config('freshroute.markets'))"/>
        <x-field name="price_kes" type="number" step="1" :label="__('ui.price_per_bag')" required inputmode="numeric"/>
        <x-field name="observed_on" type="date" :label="__('ui.observed')" :value="today()->toDateString()" max="{{ today()->toDateString() }}" required/>
        <x-field name="note" :label="__('ui.note')" maxlength="140"/>
        <button class="btn block" type="submit">{{ __('ui.submit_price') }}</button>
    </form>
    <div data-queue-list="price" class="stack"></div>
    @else
    <p><a href="{{ route('login') }}">{{ __('ui.sign_in_to_submit') }}</a></p>
    @endauth
@endif
@endsection
