{{-- use a (correctly capitalized) attribute value of objectClass
     to automatically show a link to the relevant route for a certain class's editor with a count.

     If the plural item name isn't right, you can override it by specifying an attribute of
     classSpaced

     The slot contents is the description for humans.
 --}}
@props([
    'objectClass',
    'classSpaced' => camelToSpaced($objectClass),
    ])

@if (!class_exists($objectClass))
    <div class="text-red-500 py-2 px-4 w-full sm:w-1/2">Class "{{ $objectClass }}/{{ $classSpaced }}" does not exist.</div>
@else
    <div class="py-2 px-4 w-full sm:w-1/2">
        <dt {{ $attributes->merge(['class' => 'text-lg']) }}>
            {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
            <a href="{{ strToLower(camelToKebab(Str::plural($objectClass))) }}" class="link pr-1">{{ preg_replace('/Npc/', 'NPC', Str::plural($classSpaced)) }}</a>
            <span class="text-xs opacity-80">{{ number_format($objectClass::count()) }} {{ Str::plural(strtolower($classSpaced), $objectClass::count()) }}</span>
        </dt>
        <dd>{{ $slot }}</dd>
    </div>
@endif
