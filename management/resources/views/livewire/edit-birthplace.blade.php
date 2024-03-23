{{-- Editor dialog to make changes to the record (or make a new one).
     I don't feel that there's much value in extracting this to a template since almost everything could be different,
     except for the save and cancel buttons, maybe.
 --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="name" label="Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="description" label="Description" :error="$errors->first('editing.description')">
                <x-input wire:model="editing.description" id="description" type="text" />
            </x-input.group>

            {{-- This dropdown is just to filter the list of maps to make it easier to find the desired one --}}
            <x-input.group for="map_type_id" label="Map Type" :error="$errors->first('mapTypeId')">
                <select id="map_type_id" class="w-full" wire:model="mapTypeId">
                    <option value="">Select a Map Type</option>
                    @foreach(MapType::orderBy('name')->get() as $mapType)
                        <option value="{{$mapType->id}}">{{ $mapType->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="map_id" label="Map" :error="$errors->first('editing.map_id')">
                <select id="map_id" class="w-full" wire:model="editing.map_id">
                    <option value="">Select a Map</option>
                    @foreach(Map::where('map_type_id', $mapTypeId)->orderBy('name')->get() as $map)
                        <option value="{{$map->id}}">{{ $map->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group label="Coordinates">
                <x-input.prefix wire:model="editing.x" type="text" class="text-center w-12">X</x-input.prefix>
                <span class="pr-3"></span>
                <x-input.prefix wire:model="editing.y" type="text" class="text-center w-12">Y</x-input.prefix>
                {{-- I wonder if there's a nicer way to do this --}}
                @if ($errors->first('editing.x'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.x') }}</div>
                @endif
                @if ($errors->first('editing.y'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.y') }}</div>
                @endif
            </x-input.group>

            <x-input.group label="Players Born Here">
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
