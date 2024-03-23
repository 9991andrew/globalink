{{-- I wish I didn't have to pass all this data through... maybe there's an easier way?--}}
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
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th class="text-center">Thumbnail</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="movement_req" class="text-center">Move Req</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="skill_id_req" class="text-center">Skill Req</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="graphic_filename" class="text-left">File</x-datatable.th>
        <x-datatable.th  class="text-right" >View 3D Model</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}" >
            <td>
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="text-black dark:text-white">
                {{ $object->name }}
            </td>

            <td>
                <img width="100" height="75" src="{{ $object->tnUrl }}" />
            </td>

            <td class="text-center">{{ $object->movement_req }}</td>

            <td class="text-center">@if($object->skill_id_req != 0){{ $object->skill->name }}@endif</td>

            <td>{{ $object->graphic_filename }}</td>

            <td class="text-center">
                <a href="{{route('map-tile-types')}}/{{$object->id}}"
                    type = 'button', class = 'min-w-[2.5rem] px-1 text-green-600 dark:text-green-400
                    hover:text-green-500 dark:hover:text-green-200
                    rounded focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50
                    bg-green-600 bg-opacity-10 border border-green-500 border-opacity-20 hover:border-opacity-40
                    transition ease-in-out duration-150'> <i class="fas fa-eye"></i> 
                </a>
            </td>

            <td class="text-left">
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

    <x-slot name="editModal"><livewire:edit-map-tile-type/></x-slot>

</x-data-table>

