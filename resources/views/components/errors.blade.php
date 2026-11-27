@if($errors->any())
<div class="errsum" role="alert" tabindex="-1" id="error-summary">
    <strong>{{ __('ui.fix_following') }}</strong>
    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
