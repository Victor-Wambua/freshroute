@extends('layouts.app')
@section('title', __('ui.nav_alerts'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.nav_alerts') }}</h1>
@if($items->whereNull('read_at')->count())<form method="post" action="{{ route('alerts.read-all') }}">@csrf<button class="btn sm secondary" type="submit">{{ __('ui.mark_all_read') }}</button></form>@endif</div>
<div class="card" style="margin-bottom:16px">
@forelse($items as $n)
    <a class="notice-item {{ $n->read_at ? '' : 'unread' }}" href="{{ route('alerts.open', $n->id) }}" style="text-decoration:none;color:inherit">
        @unless($n->read_at)<span class="dot-unread" aria-label="{{ __('ui.unread') }}"></span>@endunless
        <div><div>{{ \App\Support\NoticeText::render($n->data['type'], $n->data['params'] ?? []) }}</div><div class="small muted">{{ $n->created_at->diffForHumans() }}</div></div>
    </a>
@empty<div class="empty"><x-icon name="bell"/><p>{{ __('ui.no_alerts') }}</p></div>@endforelse
</div>
<h2>{{ __('ui.channels') }}</h2>
<form method="post" action="{{ route('alerts.prefs') }}" class="card">@csrf
    <div class="check"><input type="checkbox" id="p-db" name="database" value="1" @checked($prefs['database'] ?? true)><label for="p-db">{{ __('ui.ch_inapp') }}</label></div>
    <div class="check"><input type="checkbox" id="p-mail" name="mail" value="1" @checked($prefs['mail'] ?? true)><label for="p-mail">{{ __('ui.ch_email') }}</label></div>
    <div class="check"><input type="checkbox" id="p-sms" disabled aria-describedby="sms-note"><label for="p-sms">{{ __('ui.ch_sms') }}</label></div>
    <p class="hint" id="sms-note">{{ __('ui.sms_outstanding') }}</p>
    <button class="btn" type="submit">{{ __('ui.save') }}</button></form>
@endsection
