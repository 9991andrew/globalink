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
                <x-input wire:model="editing.name" id="name" type="text" readonly />
            </x-input.group>

            <x-input.group for="maxbpm" label="Use maximum bipartite matching?  ">
                <input id="maxbpm" wire:model="editing.maxbpm" type="checkbox"/>
            </x-input.group>

            <x-input.group for="autobpm" label="Use automatic detection for maximum bipartite matching?  ">
                <input id="autobpm" wire:model="editing.autobpm" type="checkbox" />
            </x-input.group>

            <x-input.group for="ngrampos" label="Use N-Gram POS service?  ">
                <input id="ngrampos" wire:model="editing.ngrampos" type="checkbox" />
            </x-input.group>

            <x-input.group for="canonical" label="Use the canonical method?  ">
                <input id="canonical" wire:model="editing.canonical" type="checkbox" />
            </x-input.group>

            <x-input.group for="timeout" label="Comparison timeout in seconds: (10-9999) ">
                <input id="timeout" wire:model="editing.timeout" type="text" size ="5" pattern="[1-9][0-9]{1,3}" />
            </x-input.group>

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
