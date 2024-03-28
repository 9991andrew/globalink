{{-- Editor dialog to make changes to the record (or make a new one). --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="req_lv" label="Required Level" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.req_lv" id="req_lv" type="number"/>
            </x-input.group>

            <x-input.group for="min_hp" label="Minimum HP ">
                <input id="min_hp" wire:model="editing.min_hp" type="number"/>
            </x-input.group>

            <x-input.group for="max_hp" label="Max HP">
                <input id="max_hp" wire:model="editing.max_hp" type="number" />
            </x-input.group>

            <x-input.group for="min_atk" label="Minimum Attack">
                <input id="min_atk" wire:model="editing.min_atk" type="number"/>
            </x-input.group>
            <x-input.group for="max_atk" label="Maximum Attack">
                <input id="max_atk" wire:model="editing.max_atk" type="number" />
            </x-input.group>
            <x-input.group for="min_def" label="Minimum DEF ">
                <input id="min_def" wire:model="editing.min_def" type="number" />
            </x-input.group>
            <x-input.group for="max_def" label="Maximum DEF">
                <input id="max_def" wire:model="editing.max_def" type="number" />
            </x-input.group>
            <x-input.group for="min_dex" label="Minimum DEX">
                <input id="min_dex" wire:model="editing.min_dex" type="number"/>
            </x-input.group>
            <x-input.group for="max_dex" label="Maximum DEX">
                <input id="max_dex" wire:model="editing.max_dex" type="number" />
            </x-input.group>
            <x-input.group for="armor_type" label="Armor Type">
                <input id="armor_type" wire:model="editing.armor_type" type="number"/>
            </x-input.group>
            <x-input.group label="Image">
                @if($newImage)
                    <img class="w-full m-auto" src="{{ $newImage->temporaryUrl() }}" style="max-width:200px;">
                @else
                    @isset($editing->id)
                        <img class="w-full m-auto" src="{{$editing->imageUrl}}" style="max-width:200px;">
                    @endisset
                @endif
            </x-input.group>
            <x-input.group label="Upload New Image" for="newImage{{ $iteration }}" :error="$errors->first('newImage')">
                <x-input type="file" id="newImage{{ $iteration }}" wire:model="newImage" />
            </x-input.group>
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
