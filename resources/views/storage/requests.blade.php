@extends('layouts.app')
@section('title', __('ui.nav_requests'))
@section('content')
<h1>{{ __('ui.nav_requests') }}</h1>
@forelse($reqs as $r)
<div class="card stack" style="margin-bottom:8px"><div class="row between wrap"><strong>{{ $r->user->displayName() }} - {{ $r->bags }} {{ __('ui.bags') }}</strong><span class="badge b-neutral">{{ __('ui.sr_'.$r->status) }}</span></div>
    <div class="small muted">{{ $r->facility->name }} - {{ __('ui.from') }} {{ $r->from_date->toDateString() }} - {{ $r->user->phone }}</div>
    @if($r->status === 'sent')<div class="table-actions">@foreach(['accepted','declined'] as $s)<form method="post" action="{{ route('storage.requests.status', $r) }}">@csrf<input type="hidden" name="status" value="{{ $s }}"><button class="btn sm {{ $s === 'declined' ? 'ghost' : '' }}" type="submit">{{ __('ui.sr_act_'.$s) }}</button></form>@endforeach</div>@endif</div>
@empty<div class="empty card"><x-icon name="msg"/><p>{{ __('ui.no_requests') }}</p></div>@endforelse
@endsection
