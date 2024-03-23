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
        <x-datatable.th>Image</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="author" class="text-left">Author</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="created_at" class="whitespace-nowrap">Created</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="updated_at" class="whitespace-nowrap">Updated</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npcs_count" class="text">NPCs</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td>
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="w-36"><span class="text-black dark:text-white">{{ $object->name }}</span></td>

            <td class="text-center"><img width="50" src="{{ $object->tnUrl }}"></td>

            <td>{{ $object->author }}</td>

            <td>
                @isset($object->created_at)
                    {{ Carbon::parse($object->created_at)->format("Y-M-d") }}
                    <span class="text-xs">{{ Carbon::parse($object->created_at)->timezone(Auth::user()->time_zone)->format("H:i T") }}</span>
                @endisset
            </td>

            <td>
                @isset($object->updated_at)
                    {{ Carbon::parse($object->updated_at)->format("Y-M-d") }}
                    <span class="text-xs">{{ Carbon::parse($object->updated_at)->timezone(Auth::user()->time_zone)->format("H:i T") }}</span>
                @endisset
            </td>

            <td class="text-center">
                {{ $object->npcs_count }}
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                @if($object->npcs_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-npc-icon /></x-slot>

</x-data-table>
