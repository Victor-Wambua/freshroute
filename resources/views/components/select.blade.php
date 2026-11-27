@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'blank' => null])
<div class="field">
    <label for="f-{{ $name }}">{{ $label }}@if($required) <span aria-hidden="true">*</span>@endif</label>
    <select id="f-{{ $name }}" name="{{ $name }}" @if($required) required @endif @if($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif {{ $attributes }}>
        @if($blank !== null)<option value="">{{ $blank }}</option>@endif
        @foreach($options as $k => $v)
            <option value="{{ $k }}" @selected((string) old($name, $value) === (string) $k)>{{ $v }}</option>
        @endforeach
    </select>
    @error($name)<div class="err" id="e-{{ $name }}" role="alert"><x-icon name="warn" size="sm"/>{{ $message }}</div>@enderror
</div>
