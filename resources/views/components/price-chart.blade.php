@props(['series', 'title', 'user' => false])
@php($c = \App\Support\Chart::line($series))
@if($c)
<figure style="margin:0">
<svg class="chart" viewBox="0 0 {{ $c['w'] }} {{ $c['h'] }}" role="img" aria-labelledby="ct-title ct-desc">
    <title id="ct-title">{{ $title }}</title>
    <desc id="ct-desc">{{ $c['points'][0]['label'] }} - {{ $c['points'][count($c['points'])-1]['label'] }}: KES {{ number_format($c['points'][0]['value']/100) }} to KES {{ number_format($c['points'][count($c['points'])-1]['value']/100) }}</desc>
    @foreach($c['ticks'] as $t)
        <line class="grid" x1="{{ $c['padL'] }}" x2="{{ $c['w'] - $c['padR'] }}" y1="{{ $t['y'] }}" y2="{{ $t['y'] }}"/>
        <text x="{{ $c['padL'] - 8 }}" y="{{ $t['y'] + 4 }}" text-anchor="end">{{ $t['label'] }}</text>
    @endforeach
    <line class="axis" x1="{{ $c['padL'] }}" x2="{{ $c['w'] - $c['padR'] }}" y1="{{ $c['h'] - $c['padB'] }}" y2="{{ $c['h'] - $c['padB'] }}"/>
    <path class="line {{ $user ? 'user' : '' }}" d="{{ $c['path'] }}"/>
    @foreach($c['points'] as $i => $p)
        <circle class="dot" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4"><title>{{ $p['label'] }}: KES {{ number_format($p['value']/100) }}</title></circle>
        @if($i === 0 || $i === count($c['points']) - 1 || $i % 4 === 0)
            <text x="{{ $p['x'] }}" y="{{ $c['h'] - 14 }}" text-anchor="middle">{{ $p['label'] }}</text>
        @endif
    @endforeach
</svg>
<figcaption class="small muted">{{ __('ui.chart_caption') }}</figcaption>
</figure>
@endif
