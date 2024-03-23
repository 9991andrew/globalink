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

        <select wire:model="filters.map_id">
            <option value="">All Maps</option>
            <option value="__NULL__">No Map</option>
            @foreach(Map::orderBy('name')->get() as $map)
                <option value="{{$map->id}}">{{ $map->name }}</option>
            @endforeach
        </select>

    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npc_icon_id" class="text-left">Icon</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="map_id" class="text-left">Map</x-datatable.th>
        <x-datatable.th class="text-left">Locations</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="level" class="text-left">Level</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npc_professions_count" class="text-left">Professions</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npc_portals_count" class="text-left">Portals</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npc_items_count" class="text-left">Items</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quests_count" class="text-left">Gives Quests</x-datatable.th>

    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}" >
            <td class="align-top">
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center align-top">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="align-top text-black dark:text-white">{{ $object->name }}</td>

            <td class="align-top text-center ">@isset($object->npcIcon)<img width="50" src="{{ $object->npcIcon->tnUrl }}">@endisset</td>
            {{--Add map to main query to avoid the extra DB queries--}}
            <td class="align-top leading-tight">
                @isset($object->map_id)
                    <a class="link" href="{{route('maps')}}?filters%5Bid%5D={{ $object->map->id }}">{{ $object->map->name }}</a>
                    <div class="text-xs text-gray-500">{{ $object->map->mapType->name }}</div>
                @endisset
            </td>

            <td class="align-top">
                @if($object->x_left != $object->x_right || $object->y_top != $object->y_bottom)
                    {{$object->x_left}},{{$object->y_top}} - {{$object->x_right}},{{$object->y_bottom}}
                @else
                    {{$object->x_left}},{{$object->y_top}}
                @endif
            </td>

            <td class="align-top">{{ $object->level }}</td>
            <td class="align-top">
                <ul class="text-xs leading-tight">
                @foreach($object->npcProfessions as $npcProfession)
                        <li><span class="text-xs text-gray-500">{{$npcProfession->profession->id}} </span><a class="link" href="/professions/?filters[id]={{$npcProfession->profession->id}}">{{ mb_substr($npcProfession->profession->name, 0, 18) }}</a></li>
                @endforeach
                </ul>
            </td>

            <td class="align-top">
                <ul class="text-xs leading-tight">
                    @foreach($object->npcPortals as $npcPortal)
                        <li>{{ mb_substr($npcPortal->name, 0, 18) }}</li>
                    @endforeach
                </ul>
            </td>

            <td class="align-top">
                <ul class="text-xs leading-tight">
                    @foreach($object->npcItems as $npcItem)
                        <li><span class="text-xs text-gray-500">{{$npcItem->item->id}} </span><a class="link" href="/items/?filters[id]={{$npcItem->item->id}}">{{ mb_substr($npcItem->item->name, 0, 18) }}</a></li>
                    @endforeach
                </ul>
            </td>

            <td class="align-top">
                <ul class="text-xs leading-tight">
                    @foreach($object->quests as $quest)
                        <li><span class="text-xs text-gray-500">{{$quest->id}} </span><a class="link" href="/quests/?filters[id]={{$quest->id}}">{{ mb_substr($quest->name, 0, 18) }}</a></li>
                    @endforeach
                </ul>
            </td>

            <td class="align-top text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="align-top text-center">
                @if($object->maps_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-npc /></x-slot>

</x-data-table>
