@extends('layouts.app')
@section('title', __('ui.compare'))
@section('content')
<p><a href="{{ route('market.index') }}">&larr; {{ __('ui.nav_market') }}</a></p>
<h1>{{ __('ui.compare') }}</h1>
@if($listings->count() < 2)<div class="empty card"><p>{{ __('ui.compare_pick') }}</p></div>@else
<div class="tablewrap" data-hint="{{ __('ui.scroll_hint') }}" tabindex="0" role="region" aria-label="{{ __('ui.table_region') }}"><table><thead><tr><th scope="col"><span class="sr">{{ __('ui.compare') }}</span></th>@foreach($listings as $l)<th scope="col">{{ $l->user->name }}</th>@endforeach</tr></thead><tbody>
@foreach([['price_per_bag', fn($l) => \App\Support\Money::kes($l->price_minor)], ['bags', fn($l) => $l->quantity_bags], ['place', fn($l) => $l->place.', '.$l->county], ['harvest_date', fn($l) => $l->harvest_date->toDateString()], ['grade', fn($l) => $l->quality_grade], ['vs_official', fn($l) => $ref ? (round(($l->price_minor - $ref->price_minor) * 100 / $ref->price_minor).'%') : '-']] as [$k, $f])
<tr><th scope="row">{{ __('ui.'.$k) }}</th>@foreach($listings as $l)<td>{{ $f($l) }}</td>@endforeach</tr>
@endforeach
<tr><th scope="row"><span class="sr">{{ __('ui.view') }}</span></th>@foreach($listings as $l)<td><a class="btn sm" href="{{ route('market.show', $l) }}">{{ __('ui.view') }}</a></td>@endforeach</tr>
</tbody></table></div>
@if($ref)<p class="small muted"><x-origin :origin="$ref->origin"/> {{ __('ui.official_ref') }}: {{ \App\Support\Money::kes($ref->price_minor) }}</p>@endif
@endif
@endsection
