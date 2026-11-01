@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button'])
@php
    $variants = [
        'primary' => 'bg-brand-600 hover:bg-brand-700 text-white focus:ring-brand-500',
        'secondary' => 'bg-white ring-1 ring-inset ring-slate-300 hover:bg-slate-50 text-slate-700 focus:ring-slate-400',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500',
    ];
    $sizes = ['md' => 'px-4 py-2 text-sm', 'sm' => 'px-3 py-1.5 text-xs'];
@endphp
<button {{ $attributes->merge(['type' => $type, 'class' => "inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed {$variants[$variant]} {$sizes[$size]}"]) }}>
    {{ $slot }}
</button>
