@extends('layouts.app')
@section('title', __('ui.nav_audit'))
@section('content')
<h1>{{ __('ui.nav_audit') }}</h1>
<div class="tablewrap" data-hint="{{ __('ui.scroll_hint') }}" tabindex="0" role="region" aria-label="{{ __('ui.table_region') }}"><table><thead><tr><th scope="col">#</th><th scope="col">{{ __('ui.when') }}</th><th scope="col">{{ __('ui.action') }}</th><th scope="col">{{ __('ui.actor') }}</th><th scope="col">{{ __('ui.subject') }}</th><th scope="col">IP</th></tr></thead><tbody>
@foreach($logs as $l)<tr><td>{{ $l->id }}</td><td>{{ $l->created_at->toDateTimeString() }}</td><td>{{ $l->action }}</td><td>{{ $l->user_id ?? '-' }}</td><td>{{ $l->subject_type ? class_basename($l->subject_type).'#'.$l->subject_id : '-' }}</td><td>{{ $l->ip }}</td></tr>@endforeach
</tbody></table></div>
<div class="pager">{{ $logs->links('partials.pager') }}</div>
@endsection
