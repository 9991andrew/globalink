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
        <x-datatable.th :sorts="$sorts" field="maxbpm" class="text-center">Max BPM</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="autobpm" class="text-center">Auto BPM</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="ngrampos" class="text-center">N-gram POS</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="canonical" class="text-center">Canonical</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="timeout" class="text-center">Timeout</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="lang" class="text-center">Language</x-datatable.th>
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
    <x-slot name="editModal"><livewire:edit-monster /></x-slot>
</x-data-table>
