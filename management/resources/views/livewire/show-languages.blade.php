<x-data-table :objectClass="$objectClass" :objects="$objects" noBulk new>

    <x-slot name="filterInputs">
        <x-input wire:model="filters.username"
                 type="text"
                 placeholder="search by name/country/id"
                 class="w-full sm:w-80 text-center"
        />
        {{-- Could add filter by creation date - existing users dont' have one. --}}
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="native_name" class="text-left">Native Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="country" class="text-left">Country</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="locale_id" class="text whitespace-nowrap">Locale ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="supported" class="text whitespace-nowrap">Support</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="users_count" class="text whitespace-nowrap">Users</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td>
                <span class="text-black dark:text-white">{{ $object->name }}</span>
            </td>

            <td>{{ $object->native_name }}</td>
            <td>{{ $object->country }}</td>
            <td>{{ $object->locale_id }}</td>

            <td class="text-center">@if ($object->supported == 1) <i class="fas fa-check-circle text-green-500"></i> @endif</td>
            <td class="text-center">@if ($object->supported ==1){{ $object->users_count }}@endif</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                {{-- This will break when we have objects without name or id...
                I'll add conditional handling when that happens --}}
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{$object->username}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-language /></x-slot>

</x-data-table>
