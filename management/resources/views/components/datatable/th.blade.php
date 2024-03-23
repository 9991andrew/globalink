@props([
    'sorts' => null,
    'direction' => null,
    'multi' => null,
    'field' => null,
    'field1st' => '',
    'field2nd' => '',
])

@php
if (isset($field)) {
    // Unsure if this component has access to the sorts array
    $direction = $sorts[$field] ?? null;
}

if (isset($sorts)) {
    $keys = array_keys($sorts);
    if (array_key_exists(0, $keys)) $field1st = $keys[0];
    if (array_key_exists(1, $keys)) $field2nd = $keys[1];
}

@endphp

<th scope="col"
    {{ $attributes->merge(['class' => 'px-2 pt-5 pb-0'])->only('class') }}
>
    @isset($field)
        <button {{ $attributes->except('class') }}
                @isset($field) wire:click="sortBy('{{ $field }}')" @endisset
                class="link group text-sm font-medium p-0.5 focus:outline-none focus:ring focus:ring-opacity-50 focus:ring-cyan-200 rounded">
            <div>{{ $slot }}</div>
            {{-- Show sort direction icons that reflect the sort order of this column--}}
            <div class="w-full flex justify-center group-hover:opacity-100
                @if($field==$field2nd) opacity-30 @elseif($field!=$field1st) opacity-0 @endif">
                @if ($direction === 'asc')
                    <i class="fas fa-chevron-up"></i>
                @elseif ($direction === 'desc')
                    <i class="fas fa-chevron-down"></i>
                @else
                    <i class="fas fa-chevron-up opacity-0 group-hover:opacity-100"></i>
                @endif
            </div>
        </button>
    @else {{-- Show a plain heading if this isn't sortable.
        The disabled button is used to ensure the sizing is identical to sortable headings. --}}
        <button disabled class="cursor-text text-sm p-0.5 focus:outline-none">
        <div class="text-black dark:text-white text-sm font-medium ">{{ $slot }}</div>
        <div class="w-full flex justify-center invisible"><i class="fas fa-chevron-down"></i></div>
        </button>
    @endif
</th>

