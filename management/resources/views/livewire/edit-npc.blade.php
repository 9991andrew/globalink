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

            <x-input.group for="item_icon_id" label="Icon" :error="$errors->first('editing.npc_icon_id')">
                <livewire:item-picker objectClass="NpcIcon" :selectedId="$editing->npc_icon_id" field="npc_icon_id" :key="'NpcIcon'.$editing->id" />
                <input tabindex="-1" type="hidden" wire:model="editing.npc_icon_id">
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

            <x-input.group label="Top Left Location">
                <x-input.prefix wire:model="editing.x_left" type="text" class="text-center w-12">X</x-input.prefix>
                <span class="pr-3"></span>
                <x-input.prefix wire:model="editing.y_top" type="text" class="text-center w-12">Y</x-input.prefix>
                {{-- I wonder if there's a nicer way to do this --}}
                @if ($errors->first('editing.x_left'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.x_left') }}</div>
                @endif
                @if ($errors->first('editing.y_top'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.y_top') }}</div>
                @endif
            </x-input.group>

            <x-input.group label="Bottom Right Location">
                <x-input.prefix prefix="X" wire:model="editing.x_right" type="text" class="text-center w-12">X</x-input.prefix>
                <span class="pr-3"></span>
                <x-input.prefix prefix="Y" wire:model="editing.y_bottom" type="text" class="text-center w-12">Y</x-input.prefix>
                {{-- I wonder if there's a nicer way to do this --}}
                @if ($errors->first('editing.x_right'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.x_right') }}</div>
                @endif
                @if ($errors->first('editing.y_bottom'))
                    <div class="mt-1 text-red-500 text-sm">{{ $errors->first('editing.y_bottom') }}</div>
                @endif
            </x-input.group>

            <x-input.group for="level" label="Level" :error="$errors->first('editing.level')">
                <x-input wire:model="editing.level" id="level" type="text" />
            </x-input.group>

            {{-- Fill in any NPC professions. --}}
            @isset($editing->id)
                <x-input.group for="profession_id" label="Trains Professions">
                    <div class="flex space-x-2">
                        <select wire:model="profession_id" id="profession_id" class="flex-1 min-w-0">
                            <option></option>
                            @php
                                $existingProfs = NpcProfession::where('npc_id', $editing->id)
                                    ->pluck('profession_id')->toArray();
                            @endphp
                            @foreach(Profession::whereNotIn('id', $existingProfs)->get() as $profession)
                                <option value="{{$profession->id}}">{{$profession->name}}</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addProfession">Add</x-button>
                    </div>
                    @if($errors->first('profession_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('profession_id')}}</div>@endif
                    <ul>
                        @foreach($editing->npcProfessions as $npcProfession)
                            <li>
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$npcProfession->id}}', name:'{{addSlashes($npcProfession->profession->name)}}', className:'NpcProfession' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{ $npcProfession->profession->name }}
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
            @endisset

            {{-- Add and delete NPC portals. Editing them will probably be done in a different show-npc-portals view --}}
            @isset($editing->id)
                <x-input.group label="Portals">
                    <div class="flex space-x-2">
                        <div class="flex-1 space-y-2">
                            <div class="flex space-x-2">
                                <input type="text" class="flex-1 min-w-0" wire:model="name" placeholder="Portal Name">
                                <x-input.prefix wire:model="level" type="text" class="text-center w-10 px-0">Lv</x-input.prefix>
                                <x-input.prefix wire:model="price" type="text" class="text-center w-11 px-0">$</x-input.prefix>
                            </div>
                            @if($errors->first('level'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('level')}}</div>@endif
                            @if($errors->first('price'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('price')}}</div>@endif
                            @if($errors->first('name'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('name')}}</div>@endif
                            <div class="flex space-x-2">
                                <select wire:model="dest_map_id" id="dest_map_id" class="flex-1 min-w-0">
                                    <option value="">Select a map</option>
                                    @foreach(Map::orderBy('name')->get() as $map)
                                        <option value="{{$map->id}}">{{$map->name}}</option>
                                    @endforeach
                                </select>
                                <x-input.prefix wire:model="dest_x" type="text" class="text-center w-11">X</x-input.prefix>
                                <x-input.prefix wire:model="dest_y" type="text" class="text-center w-11">Y</x-input.prefix>
                            </div>
                            @if($errors->first('dest_map_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('dest_map_id')}}</div>@endif
                            @if($errors->first('dest_x'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('dest_x')}}</div>@endif
                            @if($errors->first('dest_y'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('dest_y')}}</div>@endif
                        </div>
                        <x-button type="button" class="" wire:click="addPortal">Add</x-button>
                    </div>
                    <ul>
                        @foreach($editing->npcPortals as $npcPortal)
                            <li>
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$npcPortal->id}}', name:'{{addSlashes($npcPortal->name)}}', className:'NpcPortal' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                <span class="text-sm text-gray-500 mb-1">
                                    <span class="font-bold">Level {{$npcPortal->level }}</span>
                                    <span class="ml-2"><span class="font-bold">$</span>{{$npcPortal->price }}</span>
                                </span>
                                <span class="ml-2">{{ $npcPortal->name }}</span>
                                <div class="text-sm text-gray-500 mb-1">
                                    <span class="font-bold">Map:</span> {{$npcPortal->destMap->name}}
                                    <span class="font-bold ">X</span> {{$npcPortal->dest_x}},
                                    <span class="font-bold">Y</span> {{$npcPortal->dest_y}}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
            @endisset

            @isset($editing->id)
                <x-input.group label="Items">
                    <div class="flex space-x-2 my-2">
                        <livewire:item-picker objectClass="Item" :selectedId="$item_id" field="item_id" :key="'Item'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="item_id">
                        <x-button type="button" class="ml-2" wire:click="addItem">Add</x-button>
                    </div>
                    @if($errors->first('item_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('item_id')}}</div>@endif
                    <div class="text-xs leading-tight mb-3">Add an item above to have this NPC sell an item unrelated to a quest.</div>
                    <ul class="space-y-2">
                        @foreach($editing->npcItems as $npcItem)
                            <li wire:key="npcItem{{$npcItem->id}}" class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$npcItem->id}}', name:'{{addSlashes($npcItem->item->name)}}', className:'NpcItem' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                @isset($npcItem->item->itemIcon)<img class="mx-2" width="50" src="{{ $npcItem->item->itemIcon->tnUrl }}">@endisset
                                <a class="link" href="{{route('items')}}?filters%5Bid%5D={{$npcItem->item->id}}">{{ $npcItem->item->name }}</a>
                                @isset($npcItem->quest_id)
                                    <span class="text-sm text-gray-500 ml-2"><a class="link" href="{{route('quests')}}?filters%5Bid%5D={{$npcItem->quest_id}}"><span class="font-bold">Quest:</span> {{$npcItem->quest_id}}</a></span>
                                @endisset
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
            @endisset

            <x-input.group label="Gives Quests">
                <ul class="gap-x-5 grid grid-cols-2 text-sm">
                    @foreach($editing->quests as $quest)
                        <li><span class="text-xs text-gray-500">{{$quest->id}}&nbsp;</span><a class="link" href="/quests/?filters[id]={{$quest->id}}">{{ strlen($quest->name)?$quest->name:"[NO NAME]" }}</a></li>
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
