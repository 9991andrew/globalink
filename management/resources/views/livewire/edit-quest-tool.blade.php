<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">

            <x-input.group for="quest_id" label="Quest" :error="$errors->first('editing.quest_id')">
                <select id="quest_id" wire:model="editing.quest_id">
                    @foreach(Quest::orderBy('id')->get() as $quest)
                        <option value="{{ $quest->id }}">{{ $quest->id }}: {{ $quest->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="item_id" label="Tool Item" :error="$errors->first('editing.item_id')">
                <div class="flex space-y-2 my-2 flex-wrap">
                    <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$editing->item_id" field="item_id" :key="'Item'.$editing->id" />
                    <input tabindex="-1" type="hidden" wire:model="item_id">
                    @isset($editing->item)
                    <div class="managementContent w-full p-2 shadow-inset-dark dark:bg-gray-900 rounded-md">
                        {!! $editing->item->description !!}
                    </div>
                    @endisset
                </div>
            </x-input.group>

            <x-input.group label="Item Variables">
                <div class="text-xs mt-1">X and Y coordinates may be specified using equations including variables from which a value will be randomly selected each time a player obtains this item. You may use variables directly in the X and Y values and their values will be evaluated in the equation.</div>
                <div class="text-xs mt-2">Click a variable to copy its name to the clipboard.</div>
                @isset($editing->item_id)
                    @foreach($editing->item->itemVariables as $itemVariable)
                        <li wire:key="itemVariable{{ $itemVariable->id }}" class="flex items-center">
                            {{-- Make this pulse when clicked --}}
                            <button type="button" class="ml-2 cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50"
                                    onclick="copy(this)"><strong class="text-lg">@php echo $itemVariable->var_name; @endphp</strong></button> <span class="ml-2 opacity-80">{{ round($itemVariable->min_value) }} .. {{ round($itemVariable->max_value) }}</span>
                        </li>
                    @endforeach
                    <a class="link" target="_blank" href="{{route('items')}}?filters%5Bid%5D={{$editing->item_id}}}}">Edit item and its variables in new tab</a>
                @endisset
            </x-input.group>

            <x-input.group label="Locations">
                <div class="text-xs">An item to be obtained with this tool</div>
                <div class="flex space-y-2 my-2 flex-wrap">
                    <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$location_item_id" field="location_item_id" :key="'LocationItem'.$editing->id" />
                    <input tabindex="-1" type="hidden" wire:model="location_item_id">

                    <select class="w-full" wire:model="location_map_id">
                        <option value="">Select a Map</option>
                        @foreach(Map::orderBy('name')->get() as $map)
                            <option value="{{$map->id}}">{{ $map->name }}  ({{ $map->mapType->name }})</option>
                        @endforeach
                    </select>

                    <x-input.prefix outerClasses="flex-1 mr-2" wire:model="location_x" type="text" class="w-full">X</x-input.prefix>
                    <x-input.prefix outerClasses="flex-1" wire:model="location_y" type="text" class="w-full">Y</x-input.prefix>
                    <x-input.prefix outerClasses="w-full" wire:model="location_success_message" type="text" class="w-full">Message</x-input.prefix>
                    <x-input.prefix outerClasses="w-48" wire:model="location_item_amount" type="text" class="w-full">Amount</x-input.prefix>
                    <label for="location_quest_complete" class="flex-1 flex items-center justify-end">Finding Completes Quest <input class="ml-2" type="checkbox" id="location_quest_complete" wire:model="location_quest_complete" /></label>
                    <x-button type="button" class="w-full" wire:click="addQuestToolLocation">Add Location</x-button>
                </div>
                {{-- Validation --}}
                @if($errors->first('location_item_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_item_id')}}</div>@endif
                @if($errors->first('location_map_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_map_id')}}</div>@endif
                @if($errors->first('location_x'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_x')}}</div>@endif
                @if($errors->first('location_y'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_y')}}</div>@endif
                @if($errors->first('location_success_message'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_success_message')}}</div>@endif
                @if($errors->first('location_item_amount'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('location_item_amount')}}</div>@endif
                <div class="text-xs mt-1">These are locations at which this tool may be used to find items or complete a quest.</div>

                <ul x-data="">
                    @foreach($editing->questToolLocations()->get() as $questToolLocation)
                        <li wire:key="questToolLocation{{ $questToolLocation->id }}" class="flex items-center my-3">
                            <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$questToolLocation->id}}', name:'@isset($questToolLocation->map_id){{addSlashes($questToolLocation->map->name)}}@endisset [{{addSlashes($questToolLocation->item->name)}}]', className:'QuestToolLocation' })">
                                <i class="fas fa-times text-red-500"></i>
                            </button>
                            <div class="w-full">
                                <div class="w-full">
                                    @isset($questToolLocation->item_id)<span class="ml-2"><strong>Item: </strong><span class="text-xs text-gray-500">{{ $questToolLocation->item->id }}</span> {{ $questToolLocation->item->name }}</span> <strong class="ml-2">Qty: {{ $questToolLocation->item_amount }}</strong>
                                    @else<span class="ml-2 text-red-500">No Item set.</span>
                                    @endisset
                                </div>
                                <div class="w-full">
                                    @isset($questToolLocation->map_id)<span class="ml-2">{{ $questToolLocation->map->name }}</span> <strong class="ml-3">X</strong> {{ $questToolLocation->x }} <strong class="ml-2">Y</strong> {{ $questToolLocation->y }}
                                    @else<span class="ml-2 text-red-500">No location set.</span>
                                    @endisset
                                    @if($questToolLocation->quest_complete==1)<span class="text-green-500 ml-2">Completes Quest</span>@endif
                                </div>
                                <x-input.prefix outerClasses="text-sm ml-2 my-1 w-full" class="w-full text-sm" type="text" @change="$wire.setLocationMessage({{$questToolLocation->id}}, $event.target.value)" value="{{ $questToolLocation->success_message }}">Message</x-input.prefix>
                            </div>
                        </li>
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
