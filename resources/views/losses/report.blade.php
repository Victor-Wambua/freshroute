@extends('layouts.app')
@section('title', __('ui.nav_reports'))
@section('content')
<h1>{{ __('ui.loss_report') }}</h1>
<p class="muted">{{ $scopeLabel }}</p>
<p class="banner syn" role="note"><x-icon name="info" size="sm"/>{{ __('ui.loss_selfreported') }}</p>
<form method="get" class="grid g3" style="align-items:end">
    <x-field name="from" type="date" :label="__('ui.from')" :value="$d['from'] ?? null"/><x-field name="to" type="date" :label="__('ui.to')" :value="$d['to'] ?? null"/>
    <div class="field"><button class="btn secondary block" type="submit">{{ __('ui.filter') }}</button></div>
</form>
<div class="grid g3" style="margin-bottom:16px">
    <div class="card kpi"><div class="n">{{ $report['total_lost_bags'] }}</div><div class="l">{{ __('ui.total_lost') }}</div></div>
    <div class="card kpi"><div class="n">{{ $report['harvested_bags_in_lots'] }}</div><div class="l">{{ __('ui.harvested_in_lots') }}</div></div>
    <div class="card kpi"><div class="n">{{ $report['loss_rate_pct'] === null ? '-' : $report['loss_rate_pct'].'%' }}</div><div class="l">{{ __('ui.loss_rate') }}</div></div>
</div>
<div class="card bars" style="margin-bottom:16px">
    <h2>{{ __('ui.loss_by_stage') }}</h2>
    @foreach($report['by_stage'] as $stage => $s)
        <div class="bar"><span>{{ __('ui.stage_'.$stage) }}</span><div class="track"><div class="fill" style="width: {{ min(100, $s['share']) }}%"></div></div><strong>{{ $s['bags'] }}</strong></div>
    @endforeach
    <p class="hint">{{ __('ui.share_note') }}</p>
</div>
<h2>{{ __('ui.by_commodity') }}</h2>
<div class="tablewrap" data-hint="{{ __('ui.scroll_hint') }}" tabindex="0" role="region" aria-label="{{ __('ui.table_region') }}"><table><thead><tr><th scope="col">{{ __('ui.commodity') }}</th><th scope="col">{{ __('ui.stage') }}</th><th scope="col">{{ __('ui.loss_kind') }}</th><th scope="col">{{ __('ui.reason') }}</th><th scope="col" class="numcol">{{ __('ui.bags') }}</th></tr></thead><tbody>
@forelse($report['detail'] as $r)
<tr><td>{{ $r['commodity'] }}</td><td>{{ __('ui.stage_'.$r['stage']) }}</td><td>{{ __('ui.cat_'.$r['category']) }}</td><td>{{ __('ui.reason_'.$r['reason']) }}</td><td class="numcol">{{ $r['bags'] }}</td></tr>
@empty<tr><td colspan="5" class="muted">{{ __('ui.no_losses') }}</td></tr>@endforelse
</tbody></table></div>
<p style="margin-top:16px"><a class="btn" href="{{ route($exportRoute, array_filter(['from' => $d['from'] ?? null, 'to' => $d['to'] ?? null])) }}"><x-icon name="download" size="sm"/>{{ __('ui.export_csv') }}</a></p>
@endsection
