@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
<div class="field">
    <label for="f-{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true">*</span>@endif</label>
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @if($required) required @endif
        @if($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @elseif($hint) aria-describedby="h-{{ $name }}" @endif
        {{ $attributes }}>
    @if($hint)<div class="hint" id="h-{{ $name }}">{{ $hint }}</div>@endif
    @error($name)<div class="err" id="e-{{ $name }}" role="alert"><x-icon name="warn" size="sm"/>{{ $message }}</div>@enderror
</div>
