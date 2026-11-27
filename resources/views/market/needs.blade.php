@extends('layouts.app')
@section('title', __('ui.nav_needs'))
@section('content')
<div class="row between wrap"><h1>{{ __('ui.nav_needs') }}</h1><a class="btn clay" href="{{ route('needs.create') }}"><x-icon name="plus" size="sm"/>{{ __('ui.post_need') }}</a></div>
<p class="hint">{{ __('ui.need_explain') }}</p>
@forelse($needs as $n)
<a class="card" href="{{ route('needs.show', $n) }}" style="display:block;margin-bottom:8px;text-decoration:none;color:inherit"><div class="row between wrap"><strong>{{ $n->quantity_bags }} {{ __('ui.bags') }} - {{ $n->county }}</strong><span class="badge {{ $n->status === 'open' ? 'b-ok' : 'b-neutral' }}">{{ __('ui.need_'.$n->status) }}</span></div><div class="small muted">{{ __('ui.needed_by') }} {{ $n->needed_by->toDateString() }} - {{ __('ui.min_grade') }} {{ $n->min_grade }} @if($n->max_price_minor)- {{ __('ui.max_price') }} {{ \App\Support\Money::kes($n->max_price_minor) }}@endif</div></a>
@empty<div class="empty card"><x-icon name="tag"/><p>{{ __('ui.no_needs') }}</p></div>@endforelse
<div data-queue-list="need" class="stack"></div>
@endsection
