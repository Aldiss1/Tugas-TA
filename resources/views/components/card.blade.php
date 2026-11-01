@props(['bodyClass' => 'p-5'])
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm ring-1 ring-slate-100']) }}>
    @if (isset($header))
        <div class="px-5 py-4 border-b border-slate-100">
            {{ $header }}
        </div>
    @endif
    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
