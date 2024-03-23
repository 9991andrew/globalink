{{-- Editor dialog to make changes to the record (or make a new one). --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id)
            Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            <a class="ml-4 text-base link" href="{{route('maps')}}/{{$editing->id}}"><i class="fas fa-map"></i> Open Map Tile Editor</a>
            @else
            New {{ camelToSpaced($objectClass) }}
            @endisset

        </x-slot>
        <x-slot name="content">
            <x-input.group for="name" label="Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="map_type_id" label="Map Type" :error="$errors->first('editing.map_type_id')">
                <select id="map_type_id" class="w-full" wire:model="editing.map_type_id">
                    <option value="">Select a Map Type</option>
                    @foreach(MapType::orderBy('name')->get() as $mapType)
                        <option value="{{$mapType->id}}">{{ $mapType->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="description" label="Description" :error="$errors->first('editing.description')">
                <textarea class="w-full" wire:model="editing.description" id="description"></textarea>
                <div class="text-xs leading-tight">Briefly describe the purpose and contents of this map.</div>
            </x-input.group>

            <x-input.group label="NPCs">
                <ul class="gap-x-5 grid grid-cols-3 text-sm">
                    @foreach($editing->npcs as $npc)
                        <li><span class="text-xs text-gray-500">{{$npc->id}}&nbsp;</span><a class="link" href="{{route('npcs')}}/?filters[id]={{$npc->id}}">{{ $npc->name }}</a></li>
                    @endforeach
                </ul>
            </x-input.group>

            <x-input.group label="Buildings">
                <ul class="gap-x-5 grid grid-cols-3 text-sm">
                    @foreach($editing->buildings as $building)
                        <li><span class="text-xs text-gray-500">{{$building->id}}&nbsp;</span><a class="link" href="{{route('buildings')}}/?filters[id]={{$building->id}}">{{ strlen($building->name)?$building->name:"[NO NAME]" }}</a></li>
                    @endforeach
                </ul>
            </x-input.group>

            <x-input.group label="Portals to this Map">
                <ul class="gap-x-5 grid grid-cols-3 text-sm">
                    @foreach($editing->dest_maps as $building)
                        <li><span class="text-xs text-gray-500">{{$building->id}}&nbsp;</span><a class="link" href="{{route('buildings')}}?filters[id]={{$building->id}}">{{ strlen($building->name)?$building->name:"[NO NAME]" }}</a></li>
                    @endforeach
                </ul>
            </x-input.group>

            <x-input.group label="Birthplaces">
                <ul class="gap-x-5 grid grid-cols-3 text-sm">
                    @foreach($editing->birthplaces as $birthplace)
                        <li><span class="text-xs text-gray-500">{{$birthplace->id}}&nbsp;</span><a class="link" href="{{route('birthplaces')}}?filters[id]={{$birthplace->id}}">{{ $birthplace->name }}</a></li>
                    @endforeach
                </ul>
            </x-input.group>

            <x-input.group label="Players">
                <ul class="gap-x-5 grid grid-cols-3 text-sm">
                    @foreach($editing->players as $player)
                        <li><a class="link" href="/players/?filters[id]={{$player->id}}">{{ $player->name }}</a></li>
                    @endforeach
                </ul>
            </x-input.group>

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
