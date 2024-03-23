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
        {{-- Could add filter by creation date - existing users dont' have one. --}}
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="email" class="text-left">Email</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="time_zone" class="text-left">Time Zone</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="last_login_at" class="text whitespace-nowrap">Last Login</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="created_at" class="text whitespace-nowrap">Created</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="updated_at" class="text whitespace-nowrap">Updated</x-datatable.th>
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

            <td><span class="text-black dark:text-white">{{ $object->name }}</span></td>

            <td>{{ $object->email }}</td>

            <td>{{ $object->time_zone }}</td>

            <td>
                @isset($object->last_login_at)
                    {{ Carbon::parse($object->last_login_at)->format("Y-M-d") }}
                    <span class="text-xs">{{ Carbon::parse($object->last_login_at)->timezone(Auth::user()->time_zone)->format("H:i T") }}</span>
                @endisset
            </td>

            <td>@isset($object->created_at){{ Carbon::parse($object->created_at)->timezone(Auth::user()->time_zone)->format("Y-M-d") }} <span class="text-xs">by</span> <strong>{{ $object->created_by }}</strong>@endisset</td>

            <td>@isset($object->updated_at){{ Carbon::parse($object->updated_at)->timezone(Auth::user()->time_zone)->format("Y-M-d") }}@endisset</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                {{-- This will break when we have objects without name or id...
                I'll add conditional handling when that happens --}}
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-management-user /></x-slot>

</x-data-table>
