@extends('layouts.app')
@section('title', __('ui.nav_members'))
@section('content')
<h1>{{ __('ui.nav_members') }}</h1>
<form method="get" role="search" style="margin-bottom:16px"><label for="q" class="sr">{{ __('ui.search') }}</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('ui.search_ph_members') }}"></form>
<div class="tablewrap"><table><thead><tr><th scope="col">{{ __('ui.full_name') }}</th><th scope="col">{{ __('ui.place') }}</th><th scope="col" class="numcol">{{ __('ui.open_listings') }}</th><th scope="col" class="numcol">{{ __('ui.bags') }}</th><th scope="col">{{ __('ui.last_active') }}</th></tr></thead><tbody>
@forelse($members as $m)<tr><th scope="row">{{ $m->name }} <x-origin :origin="$m->origin"/></th><td>{{ $m->place }}, {{ $m->county }}</td><td class="numcol">{{ $m->open_listings }}</td><td class="numcol">{{ (int) $m->open_bags }}</td><td>{{ $m->last_active_at?->diffForHumans() ?? '-' }}</td></tr>
@empty<tr><td colspan="5" class="muted">{{ __('ui.no_members') }}</td></tr>@endforelse
</tbody></table></div>
<p class="hint">{{ __('ui.members_scope_note') }}</p>
@endsection
