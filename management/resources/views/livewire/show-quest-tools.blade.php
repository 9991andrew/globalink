<x-data-table :objectClass="$objectClass" :objects="$objects" noBulk new>

    <x-slot name="filterInputs">
        <x-input wire:model="filters.quest_name"
                 type="text"
                 placeholder="search by quest name/quest id"
                 class="w-full sm:w-80 text-center"
        />

    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quest_id" class="text-left">Quest</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="item_id" class="text-left">Item</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quest_tool_locations_count" class="text-left">Locations/Items</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="text-black dark:text-white"><span class="text-xs text-gray-500">{{ $object->quest_id }}</span> <a class="link" href="/quests?filters%5Bid%5D={{ $object->quest_id }}">{{ $object->quest->name }}</a></td>
            <td class="text-black dark:text-white"><span class="text-xs text-gray-500">{{ $object->item_id }}</span> <a class="link" href="/items?filters%5Bid%5D={{ $object->item_id }}">{{ $object->item->name }}</a></td>
            {{-- Actually show a ul of the locations and items --}}
            <td class="text-left">
                <ul>
                    @foreach($object->questToolLocations as $questToolLocation)

                        <li>
                            @isset($questToolLocation->map_id)
                                <div class="mt-1">
                                    <span class="text-gray-500 text-xs">{{$questToolLocation->map_id}}</span> {{$questToolLocation->map->name}}
                                    {{$questToolLocation->x}},{{$questToolLocation->y}}
                                </div>
                            @endisset
                            @isset($questToolLocation->item_id)
                                    <div class="mb-1 text-xs">{{$questToolLocation->item->name}}</div>
                            @endisset
                        </li>

                    @endforeach
                </ul>
            </td>
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-quest-tool /></x-slot>

</x-data-table>
