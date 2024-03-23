{{-- Editor dialog to make changes to the record (or make a new one). --}}
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

            {{-- This might need to be a textarea --}}
            <x-input.group for="description" label="Description" :error="$errors->first('editing.description')">
                <textarea wire:model.lazy="editing.description" id="description" class="w-full h-40 text-sm"></textarea>

                <ul class="text-xs mt-2">
                    <li><i class="fas fa-check-circle text-green-500"></i> Basic HTML is supported here. <strong>Ensure HTML is valid in the preview below or you may break the game!</strong></li>
                    <li><i class="fas fa-check-circle text-green-500"></i> MathML & LaTeX is supported for formatted mathematical notation.</li>
                    <li wire:ignore><i class="fas fa-info-circle text-blue-500"></i> <strong>For LaTeX:</strong> use \<span>[</span>\sqrt5\<span>]</span> for blocks or \<span>(</span>\sqrt5\<span>)</span> for inline. eg: \(\sqrt5\)</li>
                    <li><i class="fas fa-check-circle text-green-500"></i> You may click to copy the variable placeholders below to insert the player's values in the above content.
                        <ul class="mt-2">
                            @foreach($editing->itemVariables as $itemVariable)
                                <li wire:key="itemVariableCont{{ $itemVariable->id }}" class="flex items-center">
                                    {{-- Make this pulse when clicked and copy to the clipboard --}}
                                    <button type="button" class="ml-2 text-base cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50" onclick="copy(this)"><span class="opacity-50">@{{</span><strong>@php echo $itemVariable->var_name; @endphp</strong><span class="opacity-50">}}</span></button> <span class="ml-2 opacity-80 text-sm">{{ round($itemVariable->min_value) }} .. {{ round($itemVariable->max_value) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                </ul>

                @isset($editing->description)
                    {{-- I make the user manually refresh the preview
                        - To avoid breaking the page with bad HTML if they are in the middle of an edit
                        - To avoid MathJax constantly flashing and re-typesetting MathML/LaTeX and making the page bounce around
                    --}}
                    <button class="btn block m-auto mt-3 mb-2 flex items-center" type="button" wire:click="refreshPreview">
                        Refresh Preview
                    </button>
                    {{-- Badly formatted HTML could break this page, so perhaps require clicking a button before showing this --}}
                    <div wire:key="item{{ $editing->id }}preview{{$previewCount}}" id="preview" class="managementContent p-2 shadow-inset-dark dark:bg-gray-900 rounded-md">
                        @if (htmlCheck($editing->description))
                            <div wire:ignore>
                                {!!$editing->description!!}
                            </div>
                        @else
                            <div class="text-red-500">ERROR: Content has invalid HTML.</div>
                        @endif
                    </div>
                @endisset
            </x-input.group>

            {{-- This dropdown is just to filter the list of maps to make it easier to find the desired one --}}
            <x-input.group for="item_category_id" label="Item Type" :error="$errors->first('editing.item_category_id')">
                <select id="item_category_id" class="w-full" wire:model="editing.item_category_id">
                    <option value=""></option>
                    @foreach(ItemCategory::orderBy('name')->get() as $itemCategory)
                        <option value="{{$itemCategory->id}}">{{ $itemCategory->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            {{--
                This interface will have to be fancier than a dropdown, there are a thousand icons to choose from.
                I'll probably build some kind of FAYT dropdown that shows images
            --}}
{{--            <x-input.group for="item_icon_id" label="Image" :error="$errors->first('editing.item_icon_id')">--}}
{{--                <select id="item_icon_id" class="w-full" wire:model="editing.item_icon_id">--}}
{{--                    <option value=""></option>--}}
{{--                    @foreach(ItemIcon::orderBy('name')->get() as $icon)--}}
{{--                        <option value="{{$icon->id}}">{{ $icon->name }}</option>--}}
{{--                    @endforeach--}}
{{--                </select>--}}
{{--            </x-input.group>--}}

            <x-input.group for="item_icon_id" label="Icon" :error="$errors->first('editing.item_icon_id')">
                <livewire:item-picker objectClass="ItemIcon" :selectedId="$editing->item_icon_id" field="item_icon_id" :key="'ItemIcon'.$editing->id" />
                <input type="hidden" wire:model="editing.item_icon_id">
            </x-input.group>

            <x-input.group for="item_effect_id" label="Item Effect" :error="$errors->first('editing.file_extension_id')">
                <select id="item_effect_id" class="w-full" wire:model="editing.item_effect_id">
                    <option value=""></option>
                    @foreach(ItemEffect::orderBy('name')->get() as $effect)
                        <option value="{{$effect->id}}">{{ $effect->name }}</option>
                        <option disabled value="">&nbsp;&nbsp;{{ $effect->description }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="effect_parameters" label="Effect Parameters" :error="$errors->first('editing.effect_parameters')">
                <x-input wire:model="editing.effect_parameters" id="item_effect_parameters" type="text" />
                @isset($editing->itemEffect)
                    <div class="text-xs mt-1">{{ $editing->itemEffect->effect_parameters_description }}</div>
                @endisset
            </x-input.group>

            <x-input.group for="price" label="Price" :error="$errors->first('editing.price')">
                <x-input.prefix wire:model="editing.price" id="price" type="text" outerClasses="w-full" class="w-full">$</x-input.prefix>
            </x-input.group>

            <x-input.group for="weight" label="Weight (mg)" :error="$errors->first('editing.weight')">
                <x-input wire:model="editing.weight" id="weight" type="text" />
                <div class="text-xs">I have no idea why such a small unit is used for weight.</div>
            </x-input.group>

            <x-input.group for="amount" label="Initial Amount" :error="$errors->first('editing.amount')">
                <x-input wire:model="editing.amount" id="amount" type="text" />
            </x-input.group>

            <x-input.group for="max_amount" label="Max Stack Amount" :error="$errors->first('editing.max_amount')">
                <x-input wire:model="editing.max_amount" id="max_amount" type="text" />
            </x-input.group>

            <x-input.group for="required_level" label="Required Level" :error="$errors->first('editing.required_level')">
                <x-input wire:model="editing.required_level" id="required_level" type="text" />
            </x-input.group>

            <x-input.group for="level" label="Level" :error="$errors->first('editing.level')">
                <x-input wire:model="editing.level" id="level" type="text" />
            </x-input.group>

            <x-input.group for="disabled" label="Disabled" :error="$errors->first('editing.disabled')">
                <input wire:model="editing.disabled" id="disabled" type="checkbox" value="false"/>
            </x-input.group>
            {{-- A lot of these will need explanatory text... --}}

            {{-- List NPCs and Quests associated with this item
             I'm still confused about the quest_npc_items, although they should probably also go here.
             Why are there both tables??

             These should be links to those quests or whatever
             --}}
            <x-input.group label="Used by Quests">
                @foreach($editing->quests as $quest)
                    <div><span class="text-gray-500 text-xs">{{ $quest->id }}</span> <a class="link" href="{{route('quests')}}?filters%5Bid%5D={{ $quest->id }}">{{ $quest->name }}</a></div>
                @endforeach
            </x-input.group>

            <x-input.group label="Given by NPCs">
                @foreach($editing->npcs as $npc)
                    <div>
                        <span class="text-gray-500 text-xs">{{ $npc->id }}</span> <a class="link" href="{{route('npcs')}}?filters%5Bid%5D={{ $npc->id }}">{{ $npc->name }}</a>
                        @isset($npc->pivot->quest_id)
                            <span class="text-gray-500 text-xs ml-2">Quest {{ $npc->pivot->quest_id }}</span> <a class="link" href="{{route('quests')}}?filters%5Bid%5D={{ $npc->pivot->quest_id }}">{{ Quest::find($npc->pivot->quest_id)->name }}</a>
                        @endisset
                    </div>
                @endforeach
            </x-input.group>


            @isset($editing->id)
                <x-input.group label="Item Variables">
                    <div class="flex space-x-2">
                        <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="var_name" type="text" placeholder="a">Name</x-input.prefix>
                        <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="min_value" type="text">Min</x-input.prefix>
                        <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="max_value" type="text">Max</x-input.prefix>
                        <x-button type="button" wire:click="addItemVariable">Add</x-button>
                    </div>
                    @if($errors->first('var_name'))<div class="mt-1 text-red-500 text-sm">A unique variable name is required.</div>@endif
                    @if($errors->first('min_value'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('min_value')}}</div>@endif
                    @if($errors->first('max_value'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('max_value')}}</div>@endif
                    <div class="text-xs mt-1">Items may specify ranges of numbers from which a value will be randomly selected each time an item is obtained. These values may be added into the item description with the <strong>@{{a}}</strong> placeholders. These variables may also be used in quest tool locations.</div>
                    <div class="text-xs mt-2">Click a variable to copy the variable to the clipboard.</div>
                    <ul>
                        @foreach($editing->itemVariables as $itemVariable)
                            <li wire:key="itemVariable{{ $itemVariable->id }}" class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$itemVariable->id}}', name:'{{addSlashes($itemVariable->var_name)}}', className:'ItemVariable' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{-- Make this pulse when clicked --}}
                                <button type="button" class="ml-2 cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50" onclick="copy(this)"><span class="opacity-50"></span><strong>@php echo $itemVariable->var_name; @endphp</strong><span class="opacity-50"></span></button> <span class="ml-2 text-sm opacity-80">{{ round($itemVariable->min_value) }} .. {{ round($itemVariable->max_value) }}</span>
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
