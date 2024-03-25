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
        <x-datatable.th :sorts="$sorts" field="min_hp" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_hp" class="text-center">Description</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_mp_consumtion" class="text-center">Effect</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_mp_consumtion" class="text-center">Weight</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_atk" class="text-center">Min Atk</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_atk" class="text-center">Max Atk</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_def" class="text-center">Min Def</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_def" class="text-center">Max Def</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="min_dex" class="text-center">Min Dex</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_dex" class="text-center">Max Dex</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="weapon_type" class="text-center">Weapon Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="attack_type" class="text-center">Attack Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="ImageId" class="text-center">Image ID</x-datatable.th>
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

            <td class="text-center">{{ $object->item_name }}</td>
            <td class="text-center">{{ $object->item_amount }}</td>
            <td class="text-center">{{ $object->item_description }}</td>
            <td class="text-center">{{ $object->item_effect }}</td>
            <td class="text-center">{{ $object->item_weight }}</td>
            <td class="text-center">{{ $object->item_level }}</td>
            <td class="text-center">{{ $object->item_hand }}</td>
            <td class="text-center">{{ $object->bound }}</td>
            <td class="text-center">{{ $object->price }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>
            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}
    <x-slot name="editModal"><livewire:edit-weapon /></x-slot>
</x-data-table>
