@extends('layouts.app')
@section('title', __('ui.nav_more'))
@section('content')
<h1>{{ __('ui.nav_more') }}</h1>
<div class="grid g2">
    @foreach($links as $l)
        <a class="card row" href="{{ route($l['route']) }}" style="text-decoration:none;color:inherit;min-height:64px"><x-icon :name="$l['icon']"/><strong>{{ __('ui.'.$l['label']) }}</strong></a>
    @endforeach
    <a class="card row" href="{{ route('my-data.index') }}" style="text-decoration:none;color:inherit;min-height:64px"><x-icon name="download"/><strong>{{ __('ui.nav_mydata') }}</strong></a>
</div>
<form method="post" action="{{ route('logout') }}" style="margin-top:16px">@csrf<button class="btn ghost block" type="submit">{{ __('ui.sign_out') }}</button></form>
@endsection
