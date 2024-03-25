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
                <x-input wire:model="editing.req_lv" id="req_lv" type="numeric"/>
            </x-input.group>

            <x-input.group for="min_hp" label="Minimum HP ">
                <input id="min_hp" wire:model="editing.min_hp" type="numeric"/>
            </x-input.group>

            <x-input.group for="max_hp" label="Max HP">
                <input id="max_hp" wire:model="editing.max_hp" type="numeric" />
            </x-input.group>

            <x-input.group for="min_atk" label="Minimum Attack">
                <input id="min_atk" wire:model="editing.min_atk" type="numeric"/>
            </x-input.group>
            <x-input.group for="max_atk" label="Maximum Attack">
                <input id="max_atk" wire:model="editing.max_atk" type="numeric" />
            </x-input.group>
            <x-input.group for="min_def" label="Minimum DEF ">
                <input id="min_def" wire:model="editing.min_def" type="numeric" />
            </x-input.group>
            <x-input.group for="max_def" label="Maximum DEF">
                <input id="max_def" wire:model="editing.max_def" type="numeric" />
            </x-input.group>
            <x-input.group for="min_dex" label="Minimum DEX">
                <input id="min_dex" wire:model="editing.min_dex" type="numeric"/>
            </x-input.group>
            <x-input.group for="max_dex" label="Maximum DEX">
                <input id="max_dex" wire:model="editing.max_dex" type="numeric" />
            </x-input.group>
            <x-input.group for="armor_type" label="Armor Type">
                <input id="armor_type" wire:model="editing.armor_type" type="numeric"/>
            </x-input.group>
            <x-input.group for="ImageID" label="Image">
                <input id="ImageID" wire:model="editing.ImageID" type="numeric"/>
            </x-input.group>
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
