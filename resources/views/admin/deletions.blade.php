@extends('layouts.app')
@section('title', __('ui.nav_deletions'))
@section('content')
<h1>{{ __('ui.nav_deletions') }}</h1>
@forelse($reqs as $r)<div class="card row between wrap" style="margin-bottom:8px"><span>#{{ $r->user_id }} {{ $r->user->anonymised_at ? '' : $r->user->email }} - {{ $r->status }} - {{ __('ui.due') }} {{ $r->due_at->toDateString() }}</span>
@if($r->status === 'requested')<form method="post" action="{{ route('admin.deletions.process', $r) }}">@csrf<button class="btn sm danger" type="submit">{{ __('ui.process_now') }}</button></form>@endif</div>@empty<p class="muted">{{ __('ui.nothing_pending') }}</p>@endforelse
@endsection
