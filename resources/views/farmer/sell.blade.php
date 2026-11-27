@extends('layouts.app')
@section('title', __('ui.nav_sell'))
@section('content')
@php($u = auth()->user())
<h1>{{ __('ui.sell_potatoes') }}</h1>
<x-errors/>
<form method="post" action="{{ route('listings.store') }}" enctype="multipart/form-data" data-wizard data-offline="listing" novalidate>
    @csrf
    <input type="hidden" name="client_uuid" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <div class="progress" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>

    <section class="wiz-step on" aria-labelledby="s1">
        <h2 id="s1">{{ __('ui.step_bags') }}</h2>
        <div class="chips" style="margin-bottom:12px">@foreach([20,50,100,200] as $n)<button type="button" class="chip" data-set="#f-quantity_bags" data-value="{{ $n }}" aria-pressed="false">{{ $n }}</button>@endforeach</div>
        <div class="stepper">
            <button type="button" data-bump="#f-quantity_bags" data-by="-10" aria-label="{{ __('ui.less') }}">-</button>
            <input id="f-quantity_bags" name="quantity_bags" type="number" inputmode="numeric" min="{{ config('freshroute.bags.min') }}" max="{{ config('freshroute.bags.max') }}" value="{{ old('quantity_bags', 50) }}" required aria-label="{{ __('ui.bags') }}">
            <button type="button" data-bump="#f-quantity_bags" data-by="10" aria-label="{{ __('ui.more') }}">+</button>
        </div>
        <div class="hint">{{ __('ui.unit_note', ['unit' => config('freshroute.unit.en')]) }}</div>
        @error('quantity_bags')<div class="err" role="alert">{{ $message }}</div>@enderror
    </section>

    <section class="wiz-step" aria-labelledby="s2">
        <h2 id="s2">{{ __('ui.step_date') }}</h2>
        <div class="chips" style="margin-bottom:12px">
            @foreach([0 => 'today', 3 => '3d', 7 => '7d', 14 => '14d'] as $d => $k)<button type="button" class="chip" data-set="#f-harvest_date" data-value="{{ today()->addDays($d)->toDateString() }}" aria-pressed="false">{{ trans_choice('ui.in_days', $d, ['n' => $d]) }}</button>@endforeach
        </div>
        <x-field name="harvest_date" type="date" :label="__('ui.harvest_date')" :value="today()->addDays(3)->toDateString()" min="{{ today()->toDateString() }}" required/>
        <x-field name="available_to" type="date" :label="__('ui.available_to')" :hint="__('ui.available_to_hint')" min="{{ today()->toDateString() }}"/>
    </section>

    <section class="wiz-step" aria-labelledby="s3">
        <h2 id="s3">{{ __('ui.step_quality') }}</h2>
        <input type="hidden" id="f-quality_grade" name="quality_grade" value="{{ old('quality_grade') }}" required>
        <div class="bigchoice" style="margin-bottom:8px">
            @foreach(config('freshroute.grades') as $g)<button type="button" data-choice="#f-quality_grade" data-value="{{ $g }}" aria-pressed="{{ old('quality_grade') === $g ? 'true' : 'false' }}"><span class="gl">{{ $g }}</span><span class="small">{{ __('ui.grade_'.$g) }}</span></button>@endforeach
        </div>
        <p class="hint">{{ __('ui.grade_disclaimer') }}</p>
        @error('quality_grade')<div class="err" role="alert">{{ $message }}</div>@enderror
        <x-select name="county" :label="__('ui.county')" required :value="$u->county" :options="array_combine(config('freshroute.counties'), config('freshroute.counties'))"/>
        <x-field name="place" :label="__('ui.place')" :value="$u->place" required list="places"/>
        <datalist id="places">@foreach(collect(config('freshroute.places'))->flatten() as $p)<option value="{{ $p }}">@endforeach</datalist>
        @if($farms->count())<x-select name="farm_id" :label="__('ui.farm_name')" :blank="__('ui.none')" :options="$farms->pluck('name','id')->all()"/>@endif
    </section>

    <section class="wiz-step" aria-labelledby="s4">
        <h2 id="s4">{{ __('ui.step_price') }}</h2>
        @if($ref)
            <div class="banner info"><x-icon name="chart" size="sm"/><span>{{ __('ui.official_ref') }}: <strong>{{ \App\Support\Money::kes($ref->price_minor) }}</strong> ({{ $ref->market }}, {{ $ref->collected_on->toDateString() }}) <x-origin :origin="$ref->origin"/></span></div>
        @else
            <div class="banner syn">{{ __('ui.no_official_prices') }}</div>
        @endif
        <div class="stepper">
            <button type="button" data-bump="#f-price_kes" data-by="-50" aria-label="{{ __('ui.less') }}">-</button>
            <input id="f-price_kes" name="price_kes" type="number" inputmode="numeric" min="500" max="20000" step="1" value="{{ old('price_kes', $ref ? intdiv($ref->price_minor, 100) : 2500) }}" required aria-label="{{ __('ui.price_per_bag') }}">
            <button type="button" data-bump="#f-price_kes" data-by="50" aria-label="{{ __('ui.more') }}">+</button>
        </div>
        <div class="hint">{{ __('ui.price_per_bag') }} (KES, 500 - 20,000)</div>
        @error('price_kes')<div class="err" role="alert">{{ $message }}</div>@enderror
        <x-field name="storage_requirements" :label="__('ui.storage_req')" :hint="__('ui.storage_req_hint')" maxlength="200"/>
        <x-field name="variety" :label="__('ui.variety')" maxlength="60"/>
        <div class="field"><label for="f-photo"><x-icon name="camera" size="sm"/> {{ __('ui.photo') }}</label><input id="f-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment"><div class="hint">{{ __('ui.photo_hint') }}</div>@error('photo')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
    </section>

    <section class="wiz-step" aria-labelledby="s5">
        <h2 id="s5">{{ __('ui.step_review') }}</h2>
        <div class="card"><dl class="facts">
            <dt>{{ __('ui.bags') }}</dt><dd id="rv-bags"></dd><dt>{{ __('ui.harvest_date') }}</dt><dd id="rv-date"></dd><dt>{{ __('ui.grade') }}</dt><dd id="rv-grade"></dd>
            <dt>{{ __('ui.price_per_bag') }}</dt><dd>KES <span id="rv-price"></span></dd><dt>{{ __('ui.place') }}</dt><dd id="rv-place"></dd>
        </dl></div>
        <p class="hint">{{ __('ui.no_money') }}</p>
    </section>

    <div class="row" style="margin-top:16px">
        <button type="button" class="btn ghost hide" data-back>{{ __('ui.back') }}</button>
        <button type="button" class="btn grow" data-next>{{ __('ui.continue') }}</button>
        <button type="submit" class="btn clay grow hide" data-submit>{{ __('ui.publish') }}</button>
    </div>
</form>
<div data-queue-list="listing" class="stack" style="margin-top:16px"></div>
@endsection
