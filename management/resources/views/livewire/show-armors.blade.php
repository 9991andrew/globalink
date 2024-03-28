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
        <x-datatable.th :sorts="$sorts" field="min_hp" class="text-left">Min HP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_hp" class="text-center">Max HP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_atk" class="text-center">Min Atk</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_atk" class="text-center">Max Atk</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_def" class="text-center">Min Def</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_def" class="text-center">Max Def</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_dex" class="text-center">Min Dex</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_dex" class="text-center">Max Dex</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="armor_type" class="text-center">Armor Type</x-datatable.th>
        <x-datatable.th>Image</x-datatable.th>
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


            <td class="text-center">{{ $object->min_hp }}</td>
            <td class="text-center">{{ $object->max_hp }}</td>
            <td class="text-center">{{ $object->min_atk }}</td>
            <td class="text-center">{{ $object->max_atk }}</td>
            <td class="text-center">{{ $object->min_def }}</td>
            <td class="text-center">{{ $object->max_def }}</td>
            <td class="text-center">{{ $object->max_dex }}</td>
            <td class="text-center">{{ $object->min_dex }}</td>
            <td class="text-center">{{ $object->armor_type }}</td>
            <td class="text-center"><img width="50" src="{{ $object->tnUrl }}"></td>
            
            
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>
            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>


        </tr>
    @endforeach
    {{-- End main slot --}}
    <x-slot name="editModal"><livewire:edit-armors /></x-slot>
</x-data-table>
