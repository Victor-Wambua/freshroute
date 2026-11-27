@extends('layouts.app')
@section('title', __('ui.role_'.$role->value))
@section('content')
@php($c = config('freshroute'))
<div class="login">
    <h1>{{ __('ui.register_as', ['role' => __('ui.role_'.$role->value)]) }}</h1>
    <x-errors/>
    <form method="post" action="{{ route('register.store', $role->value) }}" novalidate>
        @csrf
        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
        <x-field name="name" :label="__('ui.full_name')" required autocomplete="name"/>
        @if(in_array($role->value, ['buyer','storage_provider','transporter']))
            <x-field name="business_name" :label="__('ui.business_name')" required/>
        @endif
        @if($role->value === 'buyer')
            <x-select name="buyer_type" :label="__('ui.buyer_type')" required :options="collect($c['buyer_types'])->mapWithKeys(fn($t)=>[$t=>__('ui.buyer_type_'.$t)])->all()"/>
        @endif
        <x-field name="email" type="email" :label="__('ui.email')" required autocomplete="email" inputmode="email"/>
        <x-field name="phone" type="tel" :label="__('ui.phone')" :hint="__('ui.phone_hint')" required autocomplete="tel" inputmode="tel"/>
        <x-select name="county" :label="__('ui.county')" required :options="array_combine($c['counties'],$c['counties'])"/>
        <x-field name="place" :label="__('ui.place')" :hint="__('ui.place_hint')" required list="places"/>
        <datalist id="places">@foreach(collect($c['places'])->flatten() as $p)<option value="{{ $p }}">@endforeach</datalist>
        @if($role->value === 'farmer')
            <h2>{{ __('ui.your_farm') }}</h2>
            <x-field name="farm_name" :label="__('ui.farm_name')" required/>
            <x-field name="area_acres" type="number" step="0.1" min="0.1" :label="__('ui.area_acres')" inputmode="decimal"/>
            <x-field name="variety" :label="__('ui.variety')" :hint="__('ui.variety_hint')"/>
            <x-field name="capacity_bags" type="number" min="1" :label="__('ui.capacity_bags')" :hint="__('ui.capacity_hint')" required inputmode="numeric"/>
            <x-select name="coop_id" :label="__('ui.join_coop')" :blank="__('ui.no_coop')" :options="$coops->pluck('name','id')->all()"/>
            <p class="hint">{{ __('ui.coop_share_note') }}</p>
        @elseif($role->value === 'coop_admin')
            <h2>{{ __('ui.your_coop') }}</h2>
            <x-field name="coop_name" :label="__('ui.coop_name')" required/>
            <x-field name="registration_no" :label="__('ui.reg_no')" :hint="__('ui.reg_no_hint')"/>
            <p class="banner info"><x-icon name="lock" size="sm"/>{{ __('ui.mfa_required_note') }}</p>
        @elseif($role->value === 'storage_provider')
            <p class="hint">{{ __('ui.provider_next_storage') }}</p>
        @elseif($role->value === 'transporter')
            <p class="hint">{{ __('ui.provider_next_transport') }}</p>
        @endif
        <x-field name="password" type="password" :label="__('ui.password')" :hint="__('ui.password_hint')" required autocomplete="new-password"/>
        <x-field name="password_confirmation" type="password" :label="__('ui.password_confirm')" required autocomplete="new-password"/>
        <div class="check"><input type="checkbox" id="consent" name="consent" value="1" required @checked(old('consent')) @if($errors->has('consent')) aria-invalid="true" @endif><label for="consent">{{ __('ui.consent') }}</label></div>
        @error('consent')<div class="err" role="alert">{{ $message }}</div>@enderror
        <button class="btn block big-cta" type="submit">{{ __('ui.create_account') }}</button>
    </form>
    <p class="hint">{{ __('ui.privacy_note') }}</p>
</div>
@endsection
