@extends('layouts.app')
@section('title', __('ui.nav_verify'))
@section('content')
<h1>{{ __('ui.nav_verify') }}</h1>
<p class="hint">{{ __('ui.verify_note') }}</p>
@foreach([['coop', $coops, fn($m) => $m->name.' ('.$m->county.', '.($m->registration_no ?: '-').')'], ['storage', $facilities, fn($m) => $m->name.' - '.$m->temp_min_c.'-'.$m->temp_max_c.' C'], ['transporter', $transporters, fn($m) => $m->company_name.' - '.$m->routes]] as [$type, $rows, $label])
<h2>{{ __('ui.verify_'.$type) }}</h2>
@forelse($rows as $m)<div class="card row between wrap" style="margin-bottom:8px"><span>{{ $label($m) }} <x-origin :origin="$m->origin"/></span><form method="post" action="{{ route('admin.verify.action') }}">@csrf<input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="id" value="{{ $m->id }}"><button class="btn sm" type="submit">{{ __('ui.mark_verified') }}</button></form></div>@empty<p class="muted">{{ __('ui.nothing_pending') }}</p>@endforelse
@endforeach
@endsection
