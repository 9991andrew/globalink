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
        <x-datatable.th class="text-left">Prerequisites</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="require_all_prerequisites" class="text-left">Req. All</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="players_count" class="text-left">Players</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npcs_count" class="text-left">NPCs</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}" >
            <td class="align-top">
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="text-black dark:text-white">{{ $object->name }}</td>

            <td class="text-left ">
                <ul>
                @foreach($object->prerequisites as $prerequisite)
                        <li>{{ $prerequisite->professionRequired->name }} <span class="text-gray-500">({{$prerequisite->profession_xp_req}}<span class="text-xs"> xp</span>)</span></li>
                @endforeach
                </ul>
            </td>

            <td class="text-center ">@if($object->require_all_prerequisites == 1) Yes @endif</td>

            <td class="text-center">{{ $object->players_count }}</td>

            <td class="text-center">{{ $object->npcs_count }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-profession /></x-slot>

</x-data-table>
