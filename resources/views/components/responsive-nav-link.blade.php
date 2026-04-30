@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-start text-base font-semibold text-blue-700 transition'
            : 'block w-full rounded-lg border border-transparent px-3 py-2 text-start text-base font-medium text-slate-700 transition hover:border-slate-200 hover:bg-slate-50';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
