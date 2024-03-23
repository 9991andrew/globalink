{{-- I wish I didn't have to pass all this data through... maybe there's an easier way?--}}
<x-data-table :objectClass="$objectClass" :objects="$objects" new>
    {{-- Add game styles for map rendering --}}
    @push('styles')
        <style>
            /* Override the game CSS so we can scroll the map */
            .map {
                opacity:1;
                width:100%;
                height:100%;
                overflow:scroll;
            }

            @foreach(MapTileType::all() as $tileType)
                .mtt{{$tileType->id}} { background-image:url({{$tileType->tnUrl}}); }
            @endforeach
        </style>
    @endpush
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

        <select wire:model="filters.map_type_id">
            <option value="">All Types</option>
            {{--<option value="__NULL__">No Category</option>--}}
            @foreach(MapType::orderBy('name')->get() as $type)
                <option value="{{$type->id}}">{{ $type->name }}</option>
            @endforeach
        </select>

    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="map_type_id" class="text-left">Map Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="description" class="text-left">Description</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="npcs_count" class="text-center">NPCs</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="buildings_count" class="text-center">Bldgs</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="dest_maps_count" class="text-center">Ingrss</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="birthplaces_count" class="text-center">BPlcs</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="players_count" class="text-center">Plyrs</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}" >
            <td>
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td class="text-black dark:text-white">
                {{ $object->name }}
            </td>

            <td>{{ $object->mapType->name }}</td>

            <td style="max-width:200px;">
                @if(empty($object->description) && $object->npcs_count+$object->buildings_count+$object->dest_maps_count+$object->birthplaces_count == 0)
                    <span class="text-red-600 dark:text-red-500">No ingress or egress. Unused map?</span>
                @else
                    {{ $object->description }}
                @endif
            </td>

            <td class="text-center" title="{{ $object->npcs_count }} NPCs on map">{{ $object->npcs_count }}<span class="text-xs text-gray-500"> <i class="fas fa-user-cog"></i></span></td>

            <td class="text-center" title="{{ $object->buildings_count }} buildings on map">{{ $object->buildings_count }}<span class="text-xs text-gray-500"> <i class="fas fa-building"></i></span></td>

            <td class="text-center" title="{{ $object->dest_maps_count }} ingress portals to this map">{{ $object->dest_maps_count }}<span class="text-xs text-gray-500"> <i class="fas fa-map"></i><i class="fas fa-arrow-left"></i></span></td>

            <td class="text-center" title="{{ $object->birthplaces_count }} birthplaces on map">{{ $object->birthplaces_count }}<span class="text-xs text-gray-500"> <i class="fas fa-baby"></i></i></td>

            <td class="text-center" title="{{ $object->players_count }} players on map">{{ $object->players_count }}<span class="text-xs text-gray-500"> <i class="fas fa-user"></i></span></td>

            <td class="text-center">
                <a href="{{route('maps')}}/wsnlp/{{ $object->id }}" title="WSNLP General Configuration"
                   class="inline-block px-2 text-center text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 dark:hover:text-cyan-200
                         rounded focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50
                         bg-cyan-600 bg-opacity-10 border border-cyan-500 border-opacity-20 hover:border-opacity-40
                         transition ease-in-out duration-150"><i class="fas fa-cog"></i></a>
                <a href="{{route('maps')}}/ask4/{{ $object->id }}" title="Courses for Guardian"
                   class="inline-block px-2 text-center text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 dark:hover:text-cyan-200
                         rounded focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50
                         bg-cyan-600 bg-opacity-10 border border-cyan-500 border-opacity-20 hover:border-opacity-40
                         transition ease-in-out duration-150"><i class="fas"><img src="messages.png" style="width: 12px; height: 12px" /></i></a>
                <a href="{{route('maps')}}/{{$object->id}}" title="Map Tile Editor"
                   class="inline-block px-2 text-center text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 dark:hover:text-cyan-200
                         rounded focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50
                         bg-cyan-600 bg-opacity-10 border border-cyan-500 border-opacity-20 hover:border-opacity-40
                         transition ease-in-out duration-150"><i class="fas fa-map"></i></a>
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
                <a href="{{route('maps')}}/model/{{$object->id}}" title="3D Map Viewer"
                   class="inline-block px-2 text-green-600 dark:text-green-400 hover:text-green-500 dark:hover:text-green-200
                    rounded focus:outline-none focus:border-gray-900 focus:ring ring-green-300 focus:ring-opacity-50
                    bg-green-600 bg-opacity-10 border border-green-500 border-opacity-20 hover:border-opacity-40
                    transition ease-in-out duration-150"><i class="fas fa-eye"></i></a>
            </td>

            <td class="text-center">
                @if($object->players_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>
        {{-- Extra row just to have the correct row color --}}
        <tr class="hidden"><td colspan="12"></td></tr>
        <tr wire:key="row-{{$object->id}}map">
            <td class="p-0 h-72" colspan="12">
                <div class="map relative" style="--tileW: 52px;--tileH: 26px;">
                    <!-- absolutely positioned divs for map tiles -->
                    <div class="tileContainer">
                        {!! $object->mapHTML !!}
                    </div><!--tileContainer-->
                </div><!--map-->
            </td>
        </tr>


    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-map /></x-slot>

    <script>
        // Scrolls the midpoint of all maps to the top to ensure most of the map is visible.
        function scrollMaps() {
            document.querySelectorAll('.map').forEach(function(el) { if (el.querySelector('.tile.x0.y0')){el.scrollTop = el.querySelector('.tile.x0.y0').offsetTop - 40} });
        }

        document.addEventListener("DOMContentLoaded", () => {
            Livewire.hook('component.initialized', (component) => {scrollMaps()});
            Livewire.hook('message.processed', (message, component) => {scrollMaps()});
        });

    </script>

</x-data-table>
