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
                <x-input wire:model="editing.name" id="name" type="text"/>
            </x-input.group>

            <x-input.group for="hp" label="HP ">
                <input id="hp" wire:model="editing.hp" type="number"/>
            </x-input.group>

            <x-input.group for="item id" label="item id">
                <input id="item_id" wire:model="editing.item_id" type="number" />
            </x-input.group>

            <x-input.group for="drop_rate" label="drop rate">
                <input id="drop_rate" wire:model="editing.drop_rate" type="number" />
            </x-input.group>
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
