{{-- Input with a prefix --}}
@props([
    'outerClasses' => '',
])
<span class="inline-flex rounded-md shadow-sm {{$outerClasses}}">
    <span class="inline-flex items-center px-1.5 rounded-l-md border border-r-0 border-black dark:border-gray-300 bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-300">
        {{ $slot }}
    </span>
    <input {!! $attributes->merge(['class' => 'rounded-none rounded-r-md border-l-0']) !!} />
</span>
