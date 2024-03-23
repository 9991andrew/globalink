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

            <x-input.group for="layer_index" label="Layer Index" :error="$errors->first('editing.layer_index')">
                <x-input wire:model="editing.layer_index" id="layer_index" type="text" />
            </x-input.group>

            <x-input.group for="display_seq" label="Sort Order" :error="$errors->first('editing.display_seq')">
                <x-input wire:model="editing.display_seq" id="display_seq" type="text" />
            </x-input.group>

            <x-input.group for="disabled" label="Disabled" :error="$errors->first('editing.disabled')">
                <input wire:model="editing.disabled" id="disabled" type="checkbox" />
            </x-input.group>
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

</form>
