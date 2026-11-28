@extends('layouts.app')
@section('title', __('ui.nav_prices'))
@section('content')
<h1>{{ __('ui.nav_prices') }}</h1>
<div class="banner info"><x-icon name="info" size="sm"/>{{ __('ui.import_note') }}</div>
<h2 id="h-sources">{{ __('ui.sources') }}</h2>
<div class="tablewrap" data-hint="{{ __('ui.scroll_hint') }}" tabindex="0" role="region" aria-labelledby="h-sources"><table><thead><tr><th scope="col">{{ __('ui.source') }}</th><th scope="col">{{ __('ui.licence') }}</th><th scope="col">{{ __('ui.origin') }}</th><th scope="col" class="numcol">{{ __('ui.rows') }}</th></tr></thead><tbody>
@foreach($sources as $s)<tr><th scope="row">{{ $s->name }}<div class="small muted mono">{{ $s->url }}</div></th><td>{{ __('ui.licence_'.$s->licence_status) }}</td><td><x-origin :origin="$s->origin"/></td><td class="numcol">{{ $s->prices_count }}</td></tr>@endforeach
</tbody></table></div>
<h2 id="h-batches" style="margin-top:16px">{{ __('ui.batches') }}</h2>
<div class="tablewrap" data-hint="{{ __('ui.scroll_hint') }}" tabindex="0" role="region" aria-labelledby="h-batches"><table><thead><tr><th scope="col">{{ __('ui.when') }}</th><th scope="col">{{ __('ui.source') }}</th><th scope="col" class="numcol">{{ __('ui.read') }}</th><th scope="col" class="numcol">{{ __('ui.imported') }}</th><th scope="col" class="numcol">{{ __('ui.rejected') }}</th></tr></thead><tbody>
@foreach($batches as $b)<tr><td>{{ $b->created_at->toDateTimeString() }}</td><td>{{ $b->source->name }}</td><td class="numcol">{{ $b->rows_read }}</td><td class="numcol">{{ $b->rows_imported }}</td><td class="numcol">{{ $b->rows_rejected }}</td></tr>@endforeach
</tbody></table></div>
@endsection
