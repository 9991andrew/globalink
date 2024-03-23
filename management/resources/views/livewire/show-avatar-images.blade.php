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

        <select wire:model="filters.avatar_image_type_id">
            <option value="">All Types</option>
            {{--<option value="__NULL__">No Category</option>--}}
            @foreach(AvatarImageType::orderBy('name')->get() as $type)
                <option value="{{$type->id}}">{{ $type->name }}</option>
            @endforeach
        </select>

        <select wire:model="filters.gender_id">
            <option value="">All</option>
            {{--<option value="__NULL__">No Gender</option>--}}
            @foreach(Gender::orderBy('name')->get() as $gender)
                <option value="{{$gender->id}}">{{ $gender->name }}</option>
            @endforeach
        </select>

        <select wire:model="filters.race_id">
            <option value="">All</option>
            {{--<option value="__NULL__">No Race</option>--}}
            @foreach(Race::orderBy('name')->get() as $race)
                <option value="{{$race->id}}">{{ $race->name }}</option>
            @endforeach
        </select>
        {{-- Could add filter by creation date - existing users dont' have one. --}}
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="filename" class="text-left">Filename</x-datatable.th>
        <x-datatable.th class="text-left">Preview</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="svg_code" class="text-left">SVG Code</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="avatar_image_type_id" class="text-right">Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="gender_id" class="text-right">Ge</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="race_id" class="text-right">Race</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="color_qty" class="text-right">Colors</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="players_count" class="text-left">Players</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td><input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" /></td>
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>
            <td class="text-black dark:text-white">{{ $object->name }}</td>
            <td>{{ $object->filename }}</td>
            <td>@if(strlen($object->svg_code) > 10)<svg class="w-16 bg-gray-400 rounded" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 330 460">{!! $object->svg_code !!}</svg>@endif</td>
            <td class="text-xs">{{ Str::words($object->svg_code, 10) }}</td>
            <td class="text-center">{{ $object->avatarImageType->name }}</td>
            <td class="text-center">@isset($object->gender){{ substr($object->gender->name, 0, 1) }}@endisset</td>
            <td class="text-center">@isset($object->race){{ $object->race->name }}@endisset</td>
            <td class="text-center">{{ $object->color_qty }}</td>
            <td class="text-center">{{ $object->players_count }}</td>

            <td class="text-center">
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

    <x-slot name="editModal"><livewire:edit-avatar-image /></x-slot>

</x-data-table>
