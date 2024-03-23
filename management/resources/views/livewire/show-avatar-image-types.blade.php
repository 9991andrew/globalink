<x-data-table :objectClass="$objectClass" :objects="$objects" new noBulk>
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
        <x-datatable.th :sorts="$sorts" field="layer_index">Layer</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="display_seq">Order</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="avatar_images_count">Images</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="avatar_image_type_colors_count">Colors</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            {{--<td><input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" /></td>--}}
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>
            <td class="text-black dark:text-white">{{ $object->name }}</td>
            <td class="text-center">{{ $object->layer_index }}</td>
            <td class="text-center">{{ $object->display_seq }}</td>
            <td class="text-center">{{ $object->avatar_images_count }}</td>
            <td class="text-center">{{ $object->avatar_image_type_colors_count }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                @if($object->avatar_images_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-avatar-image-type /></x-slot>

</x-data-table>
