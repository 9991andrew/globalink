
<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'btn']) }}
    {{ $attributes->whereStartsWith('x-') }}
>
    {{ $slot }}
</button>
