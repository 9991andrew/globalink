<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="name" label="Name (in English)" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="native_name" label="Native Name" :error="$errors->first('editing.native_name')">
                <x-input wire:model="editing.native_name" id="native_name" type="text" />
            </x-input.group>

            <x-input.group for="country" label="Country" :error="$errors->first('editing.country')">
                <x-input wire:model="editing.country" id="country" type="text" />
            </x-input.group>

            <x-input.group for="locale_id" label="Language" :error="$errors->first('editing.locale_id')">
                <x-input wire:model="editing.locale_id" id="native_name" type="text" />
            </x-input.group>

            <x-input.group for="supported" label="Enable Support" :error="$errors->first('editing.supported')">
                <input wire:model="editing.supported" id="supported" type="checkbox" />
            </x-input.group>



        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

</form>
