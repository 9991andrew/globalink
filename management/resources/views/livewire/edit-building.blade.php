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

            <x-input.group for="level" label="Level" :error="$errors->first('editing.level')">
                <x-input wire:model="editing.level" id="level" type="text" />
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


            <x-input.group for="dest_map_type_id" label="Dest Map Type" :error="$errors->first('destMapTypeId')">
                <select id="dest_map_type_id" class="w-full" wire:model="destMapTypeId">
                    <option value="">Select a Map Type</option>
                    @foreach(MapType::orderBy('name')->get() as $mapType)
                        <option value="{{$mapType->id}}">{{ $mapType->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="dest_map_id" label="Destination Map" :error="$errors->first('editing.dest_map_id')">
                <select id="dest_map_id" class="w-full" wire:model="editing.dest_map_id">
                    <option value="">Select a Map</option>
                    @foreach(Map::where('map_type_id', $destMapTypeId)->orderBy('name')->get() as $map)
                        <option value="{{$map->id}}">{{ $map->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group label="Destination Coords">
                <x-input.prefix wire:model="editing.dest_x" type="text" class="text-center w-12">X</x-input.prefix>
                <span class="pr-3"></span>
                <x-input.prefix wire:model="editing.dest_y" type="text" class="text-center w-12">Y</x-input.prefix>
                @if ($errors->first('editing.dest_x'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.dest_x') }}</div>
                @endif
                @if ($errors->first('editing.dest_y'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.dest_y') }}</div>
                @endif
            </x-input.group>

            <x-input.group for="external_link" label="External Link" :error="$errors->first('editing.external_link')">
                <x-input wire:model="editing.external_link" id="external_link" type="text" />
            </x-input.group>

            @isset($editing->id)
                <x-input.group for="professions" label="Professions">
                    <div class="flex space-x-2">
                        <select wire:model="profession_id" id="profession_id" class="flex-1 min-w-0">
                            <option value="">Select a profession</option>
                            @php
                                $existingProfs = $editing->professions->pluck('id')->toArray();
                            @endphp
                            @foreach(Profession::whereNotIn('id', $existingProfs)->get() as $profession)
                                <option value="{{$profession->id}}">{{$profession->name}}</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addProfession">Add</x-button>
                    </div>

                    <div class="text-xs mt-2">If professions are specified, a player requires one of these for portals to work and links to show.</div>

                    <ul class="mt-2">
                        @foreach($editing->professions as $profession)
                            <li wire:key="building{{$editing->id}}profession{{$profession->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$editing->id}}', name:'{{addSlashes($profession->name)}}', className:'Building', pivot:'professions', pivotId:{{$profession->id}} })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                <a class="link" href="{{route('professions')}}?filters%5Bid%5D={{ $profession->id }}"><span class="text-sm text-gray-500/80 mr-1">{{ $profession->id }}</span>{{ $profession->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
            @endisset
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

</form>
