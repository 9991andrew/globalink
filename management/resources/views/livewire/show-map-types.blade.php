{{-- I wish I didn't have to pass all this data through... maybe there's an easier way?--}}
<x-data-table :objectClass="$objectClass" :objects="$objects" noBulk new>
    <x-slot name="bulkActions">
        <x-dropdown-link href="#" class="cursor-pointer text-red-500" x-on:click="$dispatch('confirm-bulk-action')">
            <i class="fas fa-trash"></i> &nbsp; Delete Selected
        </x-dropdown-link>
    </x-slot>

    <x-slot name="filterInputs">
        <x-input wire:model="filters.name"
                 type="text"
                 placeholder="search by name/id"
                 class="w-full sm:w-80 text-center"
        />
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="description" class="text-left">Description</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="maps_count" class="text-left">Maps</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}" >
            <td class="text-center align-top">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="align-top text-black dark:text-white">
                {{ $object->name }}
            </td>

            <td class="align-top">{{ $object->description }}</td>

            <td class="align-top">
                <p class="font-bold mb-3">{{ $object->maps_count }} {{Str::plural('maps', $object->maps_count)}}</p>
                <ul class="gap-x-3 grid grid-cols-3 text-xs">
                    @foreach($object->maps as $map)
                        <li><a class="link" href="{{ route('maps') }}?filters[id]={{$map->id}}">{{ $map->name }}</a></li>
                    @endforeach
                </ul>
            </td>

            <td class="align-top text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="align-top text-center">
                @if($object->maps_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-map-type /></x-slot>

</x-data-table>
