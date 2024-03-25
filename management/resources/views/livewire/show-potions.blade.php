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
        <x-datatable.th :sorts="$sorts" field="req_lv" class="text-left">REQ LV</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="hp" class="text-center">HP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="mp" class="text-center">MP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="atk" class="text-center">Atk</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="def" class="text-center">Def</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="dex" class="text-center">Dex</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="ImageID" class="text-center">Image</x-datatable.th>
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

            <td class="text-black dark:text-white text-base">
                @if($object->ismap)
                    {{ $object->name }} (Map)
                @else
                    {{ $object->name }}
                @endif
            </td>

            <td class="text-center">{{ $object->maxbpm }}</td>
            <td class="text-center">{{ $object->autobpm }}</td>
            <td class="text-center">{{ $object->ngrampos }}</td>
            <td class="text-center">{{ $object->canonical }}</td>
            <td class="text-center">{{ $object->timeout }}</td>
            <td class="text-center">{{ $object->lang }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>
            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}
    <x-slot name="editModal"><livewire:edit-potions /></x-slot>
</x-data-table>
