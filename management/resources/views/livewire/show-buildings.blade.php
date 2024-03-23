<x-data-table :objectClass="$objectClass" :objects="$objects" new>
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

        <select wire:model="filters.map_id">
            <option value="">All Maps</option>
            {{--<option value="__NULL__">No Category</option>--}}
            @foreach(Map::orderBy('name')->get() as $map)
                <option value="{{$map->id}}">{{ $map->name }}</option>
            @endforeach
        </select>

    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="map_id" class="text-left">Map</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="x" class="text-center">X</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="y" class="text-center">Y</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="dest_map_id" class="text-left">Destination Map</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="dest_x" class="text-center">Dest</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="external_link" class="text-left">Link</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="professions_count" class="text-left">Profs</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td><input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" /></td>
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>
            <td class="text-black dark:text-white">{{ $object->name }}</td>
            <td>@isset($object->map_id)<span class="text-gray-500 text-xs mr-1">{{ $object->map_id }}</span><a class="link" href="{{route('maps')}}?filters%5Bid%5D={{$object->map_id}}">{{ $object->map->name }}</a>@endisset</td>
            <td class="text-center">{{ $object->x }}</td>
            <td class="text-center">{{ $object->y }}</td>
            <td>@isset($object->dest_map_id)<span class="text-gray-500 text-xs mr-1">{{ $object->dest_map_id }}</span><a class="link" href="{{route('maps')}}?filters%5Bid%5D={{$object->dest_map_id}}">{{ $object->destMap->name }}</a>@endisset</td>
            <td class="text-center">@isset($object->dest_x){{ $object->dest_x }}, {{ $object->dest_y }}@endisset</td>
            <td class="max-w-md break-all"><a class="link" href="{{ $object->external_link }}">{{ $object->external_link }}</td>
            <td class="text-center">{{ $object->professions_count }}</td>
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

    <x-slot name="editModal"><livewire:edit-building /></x-slot>

</x-data-table>
