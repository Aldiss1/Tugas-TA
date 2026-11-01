@props(['label' => null, 'name' => null, 'type' => 'text'])
<div>
    @if ($label)
        <label for="ui-{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">
            {{ $label }}@if (in_array($attributes->get('required'), [true, 'required', '']))<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <input @if ($name) name="{{ $name }}" id="ui-{{ $name }}" @endif type="{{ $type }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-brand-500 focus:ring-brand-500']) }}>
</div>
