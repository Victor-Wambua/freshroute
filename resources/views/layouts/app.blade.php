<!doctype html>
@php($u = auth()->user())
@php($nav = $u ? \App\Support\Nav::for($u) : null)
@php($unread = $u ? $u->unreadNotifications()->count() : 0)
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1F5E34">
<title>@yield('title', 'FreshRoute') - FreshRoute</title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/icons/logo.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<script src="/theme-init.js"></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-authed="{{ $u ? '1' : '0' }}" data-i18n="{{ json_encode(__('pwa')) }}">
@include('partials.sprite')
<a class="skip" href="#view">{{ __('ui.skip') }}</a>
<div class="app">
<header class="top">
    <a class="brand" href="{{ $u ? route('home') : route('welcome') }}"><svg aria-hidden="true"><use href="#i-logo"/></svg><span>FreshRoute</span></a>
    <div class="bar-actions">
        <span id="net-pill" class="pill hide" role="status" aria-live="polite"></span>
        <a class="iconbtn langbtn" href="{{ route('locale', app()->getLocale() === 'en' ? 'sw' : 'en') }}" lang="{{ app()->getLocale() === 'en' ? 'sw' : 'en' }}" aria-label="{{ app()->getLocale() === 'en' ? 'SW' : 'EN' }}: {{ __('ui.switch_lang') }}"><x-icon name="globe" size="sm"/>{{ app()->getLocale() === 'en' ? 'SW' : 'EN' }}</a>
        <button type="button" class="iconbtn" id="theme-btn" aria-label="{{ __('ui.toggle_theme') }}"><x-icon name="moon" size="sm"/></button>
        @if($u)
        <a class="iconbtn" href="{{ route('alerts.index') }}" aria-label="{{ __('ui.nav_alerts') }}{{ $unread ? ' ('.$unread.')' : '' }}"><x-icon name="bell"/>@if($unread)<span class="dot"></span>@endif</a>
        @else
        <a class="btn sm secondary" href="{{ route('login') }}">{{ __('ui.sign_in') }}</a>
        @endif
    </div>
</header>
@if($u)
<aside class="side" aria-label="{{ __('ui.main_nav') }}">
    <a class="brand" href="{{ route('home') }}" style="margin:0 8px 12px"><svg aria-hidden="true"><use href="#i-logo"/></svg>FreshRoute</a>
    <nav>
    @foreach($nav['side'] as $n)
        <a class="navlink" href="{{ route($n['route']) }}" @if(request()->routeIs(...$n['pat'])) aria-current="page" @endif><x-icon :name="$n['icon']"/>{{ __('ui.'.$n['label']) }}</a>
    @endforeach
    </nav>
    <div style="margin-top:auto;padding:8px">
        <div class="small muted" style="margin-bottom:8px">{{ $u->displayName() }} <span class="badge b-neutral">{{ $u->role->label() }}</span></div>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost sm block" type="submit">{{ __('ui.sign_out') }}</button></form>
    </div>
</aside>
@endif
<main id="view" tabindex="-1">
    @if(config('freshroute.show_synthetic_banner'))
        <div class="banner syn" role="note"><x-icon name="info" size="sm"/><span>{{ __('ui.synthetic_note') }}</span></div>
    @endif
    @if(session('status'))<div class="banner ok flash" role="status"><x-icon name="check" size="sm"/>{{ session('status') }}</div>@endif
    <div id="queue-banner" class="banner off hide" role="status"></div>
    @yield('content')
    <p class="print-foot">{{ __('ui.print_foot', ['date' => now()->toDateString()]) }}</p>
</main>
@if($u)
<nav class="tabbar" aria-label="{{ __('ui.main_nav') }}">
    @foreach($nav['tabs'] as $n)
        <a class="tab {{ $n['cta'] ? 'cta' : '' }}" href="{{ route($n['route']) }}" @if(request()->routeIs(...$n['pat'])) aria-current="page" @endif><x-icon :name="$n['icon']"/><span>{{ __('ui.'.$n['label']) }}</span></a>
    @endforeach
</nav>
@endif
</div>
<div id="toast" role="status" aria-live="polite"></div>
</body>
</html>
