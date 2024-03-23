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
        {{-- Could add filter by creation date - existing users dont' have one. --}}
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="map_id" class="text-left">Map</x-datatable.th>
        <x-datatable.th class="text-left">Map Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="x" class="text-right">X</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="y" class="text-right">Y</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="players_count" class="text-right">Players</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="description" class="text-left">Description</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="text-black dark:text-white">
                {{ $object->name }}
            </td>

            <td>
                <span class="text-sm text-gray-500">{{ $object->map->id }} </span><a class="link" href="/maps/?filters[id]={{$object->map->id}}">{{ $object->map->name }}</a>
            </td>

            <td>
                <span class="text-sm text-gray-500">{{ $object->map->mapType->id }} </span><a class="link" href="/map-types/?filters[id]={{$object->map->mapType->id}}">{{ $object->map->mapType->name }}</a>
            </td>

            <td class="text-center">{{ $object->x }}</td>
            <td class="text-center">{{ $object->y }}</td>
            <td class="text-center">{{ $object->players_count }}</td>
            <td>{{ $object->description }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                @if($object->players_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-birthplace /></x-slot>

</x-data-table>
