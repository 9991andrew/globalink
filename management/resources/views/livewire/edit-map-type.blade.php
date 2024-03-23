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

            <x-input.group for="description" label="Description" :error="$errors->first('editing.description')">
                <textarea class="w-full" wire:model="editing.description" id="description"></textarea>
                <div class="text-xs leading-tight">Briefly describe the purpose and contents of this world.</div>
            </x-input.group>

            <x-input.group label="Maps">
                <ul class="gap-x-5 grid grid-cols-2">
                    @foreach($editing->maps as $map)
                        <li><a class="link" href="{{ route('maps') }}?filters[id]={{$map->id}}">{{ $map->name }}</a></li>
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
