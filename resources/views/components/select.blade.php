@props(['label' => null, 'name' => null])
<div>
    @if ($label)
        <label for="ui-{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">
            {{ $label }}@if (in_array($attributes->get('required')), [true, 'required', ''])<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <select @if ($name) name="{{ $name }}" id="ui-{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-brand-500 focus:ring-brand-500']) }}>
        {{ $slot }}
    </select>
</div>
