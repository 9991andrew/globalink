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

            <x-input.group for="layer_index" label="Layer Index" :error="$errors->first('editing.layer_index')">
                <x-input wire:model="editing.layer_index" id="layer_index" type="text" />
                <div class="text-xs leading-tight">This determines the stacking order for rendering avatar parts. Typically skin is lowest, hair is highest.</div>
            </x-input.group>

            <x-input.group for="display_seq" label="Display Order" :error="$errors->first('editing.display_seq')">
                <x-input wire:model="editing.display_seq" id="display_seq" type="text" />
                <div class="text-xs leading-tight">Determines the order shown in the UI for users. This is typically set up so the height of the selection roughly matches the spot it appears on the avatar.</div>
            </x-input.group>

            @if($editing->id)
                <x-input.group label="CSS Colors">
                    <div class="flex">
                        <input wire:model="newColor" id="newColor" type="text" class="flex-1 min-w-0" max="35" placeholder="CSS Color"/>
                        <x-button type="button" class="ml-2" wire:click="addColor">Add Color</x-button>
                    </div>
                    <div class="text-xs">Colors offered as a choice when configuring this avatar part type.</div>
                    @if($errors->first('newColor'))<div class="mt-1 text-red-500 text-sm">This color is already used.{{-- $errors->first('newColor') --}}</div>@endif
                    <ul wire:sortable="updateColorOrder">
                    @foreach($editing->avatarImageTypeColors()->orderBy('display_seq')->get() as $color)
                        <li draggable="true" wire:sortable.item="{{ $color->id }}" wire:key="avatarItemTypeColor{{ $color->id }}" class="flex items-center">
                            <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$color->id}}', name:'{{$color->css_color}}', className:'AvatarImageTypeColor' })">
                                <i class="fas fa-times text-red-500"></i>
                            </button>

                            <i class="fas fa-align-justify ml-3 opacity-20"></i>

                            <div class="inline-block w-5 h-5 mx-2 rounded-full shadow" style="background-color:{{ $color->css_color }};"></div> {{ $color->css_color }}
                        </li>
                    @endforeach
                    </ul>
                </x-input.group>
            @endif

            <x-input.group for="disabled" label="Disable" :error="$errors->first('editing.disabled')">
                <input wire:model="editing.disabled" id="disabled" type="checkbox" />
            </x-input.group>

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
