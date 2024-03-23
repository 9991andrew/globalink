@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block pl-3 pr-4 py-2 border-l-4 border-cyan-400 font-medium text-black dark:text-white bg-cyan-500 bg-opacity-30 focus:outline-none focus:text-cyan-800 focus:bg-cyan-100 focus:border-cyan-700 transition duration-150 ease-in-out'
            : 'block pl-3 pr-4 py-2 border-l-4 border-transparent font-medium text-gray-600 dark:text-gray-300 hover:text-gray-500 hover:bg-gray-500 hover:bg-opacity-20 hover:border-gray-300 focus:outline-none focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
