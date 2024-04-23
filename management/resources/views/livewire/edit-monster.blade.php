{{-- Editor dialog to make changes to the monster record (or create a new one). --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-30" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id)
                Edit Monster ID <strong>{{ $editing->id }}</strong>
            @else
                New Monster
            @endisset
        </x-slot>

        <x-slot name="content">
            {{-- Monster Name --}}
            <x-input.group for="name" label="Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" class="form-control w-full sm:w-auto"/>
            </x-input.group>

            {{-- Monster HP --}}
            <x-input.group for="hp" label="HP" :error="$errors->first('editing.hp')">
                <x-input wire:model="editing.hp" id="hp" type="number" class="form-control w-full sm:w-auto"/>
            </x-input.group>

            {{-- Associated Item --}}
            <x-input.group for="item_id" label="Item" :error="$errors->first('editing.item_id')">
                <select wire:model="editing.item_id" id="item_id" class="form-control w-full sm:w-auto">
                    <option value="">Select an Item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            {{-- Item Drop Rate --}}
            <x-input.group for="drop_rate" label="Item Drop Rate" :error="$errors->first('editing.drop_rate')">
                 <x-input wire:model="editing.drop_rate" id="drop_rate" type="number" step="0.01" class="form-control w-full sm:w-auto"/>
            </x-input.group>

            {{-- Map ID --}}
            <x-input.group for="map_id" label="Map" :error="$errors->first('editing.map_id')">
                <select wire:model="editing.map_id" id="map_id" class="form-control w-full sm:w-auto">
                    <option value="">Select a Map</option>
                    @foreach ($maps as $map)
                        <option value="{{ $map->id }}">{{ $map->name }}</option>
                    @endforeach
                </select>
            </x-input.group>
        </x-slot>

        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="showModal = false">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
