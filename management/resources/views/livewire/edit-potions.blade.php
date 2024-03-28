{{-- Editor dialog to make changes to the record (or make a new one). --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="req_lv" label="REQ LV" :error="$errors->first('editing.req_lv')">
                <x-input wire:model="editing.req_lv" id="req_lv" type="number"/>
            </x-input.group>

            <x-input.group for="hp" label="HP ">
                <input id="hp" wire:model="editing.hp" type="number"/>
            </x-input.group>

            <x-input.group for="mp" label="MP">
                <input id="mp" wire:model="editing.mp" type="number" />
            </x-input.group>

            <x-input.group for="atk" label="ATK">
                <input id="atk" wire:model="editing.atk" type="number" />
            </x-input.group>

            <x-input.group for="def" label="DEF">
                <input id="def" wire:model="editing.def" type="number" />
            </x-input.group>

            <x-input.group for="dex" label="DEX">
                <input id="dex" wire:model="editing.dex" type="number"/>
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
