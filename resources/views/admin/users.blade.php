@extends('layouts.app')
@section('title', __('ui.nav_users'))
@section('content')
<h1>{{ __('ui.nav_users') }}</h1>
<form method="get" role="search" style="margin-bottom:16px"><label for="q" class="sr">{{ __('ui.search') }}</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('ui.email') }}"></form>
<div class="tablewrap"><table><thead><tr><th scope="col">{{ __('ui.full_name') }}</th><th scope="col">{{ __('ui.email') }}</th><th scope="col">{{ __('ui.role') }}</th><th scope="col">{{ __('ui.status') }}</th><th scope="col"></th></tr></thead><tbody>
@foreach($users as $u)<tr><th scope="row">{{ $u->name }}</th><td>{{ $u->email }}</td><td>{{ $u->role->label() }} <x-origin :origin="$u->origin"/></td><td>{{ $u->anonymised_at ? __('ui.anonymised') : ($u->suspended_at ? __('ui.suspended') : __('ui.active')) }}</td>
    <td>@unless($u->hasRole(\App\Enums\Role::PlatformAdmin) || $u->anonymised_at)<form method="post" action="{{ route('admin.suspend', $u) }}">@csrf<button class="btn sm ghost" type="submit">{{ $u->suspended_at ? __('ui.unsuspend') : __('ui.suspend') }}</button></form>@endunless</td></tr>@endforeach
</tbody></table></div>
<div class="pager">{{ $users->links('partials.pager') }}</div>
@endsection
