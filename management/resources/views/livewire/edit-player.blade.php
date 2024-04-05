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
            <x-input.group for="user_id" label="User" :error="$errors->first('editing.user_id')">
                {{-- Could only allow changing the user_id if this is the special TEST PLAYER --}}
                {{--@if($editing->player_id == 0)--}}
                <x-input wire:model="editing.user_id" id="user_id" type="text" />
                {{--@endif--}}
                {{-- Could make special FAYT livewire component for this...--}}
                @isset($editing->user)
                    <strong>{{ $editing->user->username }}</strong>
                    <span class="text-sm pl-3"><span class="text-gray-500 text-opacity-80">ID: </span>{{ $editing->user_id }}
                @endisset
            </x-input.group>

            <x-input.group for="name" label="Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="birthplace_id"
                           label="Birthplace"
                           :error="$errors->first('editing.birthplace_id')"
            >
                <select id="birthplace_id" class="w-full" wire:model="editing.birthplace_id">
                    @foreach(Birthplace::orderBy('name')->get() as $bp)
                        <option value="{{$bp->id}}">{{ $bp->name }}</option>
                    @endforeach
                </select>

                <div><x-button class="mt-2" wire:click="returnToBirthplace">Return to Birthplace</x-button></div>
            </x-input.group>

            <x-input.group label="Current Location">
                <select id="map_id" class="w-full mb-2" wire:model="editing.map_id">
                    <option value="">Select a Map</option>
                    @foreach(Map::orderBy('name')->get() as $map)
                        <option value="{{$map->id}}">{{ $map->name }}</option>
                    @endforeach
                </select>

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

            <x-input.group for="health" label="Health" :error="$errors->first('editing.health')">
                <x-input wire:model="editing.health" id="health" type="text" />
            </x-input.group>

            <x-input.group for="money" label="Money" :error="$errors->first('editing.money')">
                {{-- Prefix with a dollar sign, I'll worry about this later --}}
                <x-input wire:model="editing.money" id="money" type="text" />
            </x-input.group>

            <x-input.group for="movement" label="Movement Energy" :error="$errors->first('editing.movement')">
                {{-- Prefix with a Footsteps icon? --}}
                <x-input wire:model="editing.movement" id="movement" type="text" />
            </x-input.group>

            @if(is_int($editing->id))



                <x-input.group for="skills" label="Skills">
                    <div class="flex space-x-2">
                        <select wire:model="skill_id" id="skill_id" class="flex-1 min-w-0">
                            <option value="">Select a skill</option>
                            @php
                                $existingSkills = $editing->skills->pluck('id')->toArray();
                            @endphp
                            @foreach(Skill::whereNotIn('id', $existingSkills)->get() as $skill)
                                <option value="{{$skill->id}}">{{$skill->name}}</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addSkill">Add</x-button>
                    </div>

                    <ul class="mt-2">
                        @foreach($editing->skills as $skill)
                            <li wire:key="player{{$editing->id}}skill{{$skill->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$editing->id}}', name:'{{addSlashes($skill->name)}}', className:'Player', pivot:'skills', pivotId:{{$skill->id}} })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                <a class="link" href="{{route('skills')}}?filters%5Bid%5D={{ $skill->id }}"><span class="text-sm text-gray-500 text-opacity-80 mr-1">{{ $skill->id }}</span>{{ $skill->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>

                <x-input.group for="quests" label="Quests">

                    <div class="flex space-x-2">
                        <select wire:model="quest_id" id="quest_id" class="flex-1 min-w-0">
                            <option value="">Select a quest</option>
                            @php
                              $existingQuests = $editing->quests->pluck('id')->toArray();
                            @endphp
                            @foreach(Quest::whereNotIn('id', $existingQuests)->get() as $quest)
                                <option value="{{$quest->id}}">{{$quest->id}}: {{$quest->name}}</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addQuest">Add</x-button>
                    </div>

                    <ul class="mt-2">
                        @php $lastComplete=false; @endphp
                        @foreach($editing->quests()->orderBy('success_time')->get() as $quest)
                            @if($quest->pivot->success_time != null && $lastComplete != true)
                                @php $lastComplete=true; @endphp
                            </ul>
                                <h3 class="mt-2 mb-1/2 font-bold text-lg">Completed Quests</h3>
                            <ul>
                            @endif
                            <li wire:key="player{{$editing->id}}skill{{$quest->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$editing->id}}', name:'{{addSlashes($quest->name)}}', className:'Player', pivot:'quests', pivotId:{{$quest->id}} })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                <a class="link" href="{{route('quests')}}?filters%5Bid%5D={{ $quest->id }}"><span class="text-sm text-gray-500 text-opacity-80 mr-1">{{ $quest->id }}</span>{{ $quest->name }}</a> @if(!$lastComplete)<div class="text-xs">Picked up: <span class="text-gray-500">{{ Carbon::parse($quest->pivot->pickup_time)->timezone(Auth::user()->time_zone)->format("Y-M-d H:i") }}</span></div>@endif
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
                <x-input.group for="items" label="Player's Items">
                    <ul class="mt-2">
                        @foreach($editing->playerItems as $playerItem)
                            <li wire:key="playerItem{{$playerItem->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$playerItem->id}}', name:'{{addSlashes($playerItem->item->name)}}', className:'PlayerItem'})">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                <a class="link" href="{{route('items')}}?filters%5Bid%5D={{ $playerItem->item->id }}"><span class="text-sm text-gray-500 text-opacity-80 mr-1">{{ $playerItem->item->id }}</span>{{ $playerItem->item->name }}</a> <span class="text-sm"><span class="text-gray-500 text-xs">Qty</span> {{$playerItem->bag_slot_amount}}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-input.group>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
