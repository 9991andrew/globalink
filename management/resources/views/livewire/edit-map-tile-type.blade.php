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

            <x-input.group label="Image">
                @if($newImage)
                    <img class="w-full m-auto" src="{{ $newImage->temporaryUrl() }}" style="max-width:400px;">
                @else
                    @isset($editing->id)
                        <img class="w-full m-auto" src="{{$editing->imageUrl}}" style="max-width:400px;">
                    @endisset
                @endif
            </x-input.group>

            <x-input.group label="Upload New Image" for="newImage{{ $iteration }}" :error="$errors->first('newImage')">
                <x-input type="file" id="newImage{{ $iteration }}" wire:model="newImage" wire:loading.attr="disabled" wire:target="newImage"/>
            </x-input.group>

            <x-input.group label="Upload 3D Model"  for="new3DModel{{ $iteration }}" :error="$errors->first('new3DModel')">
                <x-input type="file" id="new3DModel{{ $iteration }}" wire:model="new3DModel" wire:loading.attr="disabled" wire:target="new3DModel" />
            </x-input.group>


            <x-input.group for="map_tile_type_id_image" label="Use Existing Tile" :error="$errors->first('editing.map_tile_type_id_image')">
                <select wire:model="editing.map_tile_type_id_image" id="map_tile_type_id_image">
                    <option value="">Use Own Map Tile</option>
                    @foreach(MapTileType::whereNull('map_tile_type_id_image')->orderBy('id')->get() as $tile)
                        <option value="{{ $tile->id }}">{{$tile->id}} - {{$tile->name}}</option>
                    @endforeach
                </select>
                <div class="text-xs leading-tight">This may be used when the same graphic is used on two tiles that look the same but have different names or properties for movement/skill, etc.</div>
                <div class="text-xs leading-tight mt-4">
                    <strong>Important:</strong> Use PNG tiles with width of 400px and height at least 200px.<br>
                    <p class="my-2">Tiles will automatically be converted into WebP format for better efficiency, but the PNGs will be kept as high-quality originals and used for backwards compatibility.</p>
                    <a class="link" href="https://bitbucket.org/vipresearch/migrating-mega-world-from-jsp-to-php/src/master/Graphics%20Assets/">Photoshop templates are available</a> for these tiles.
                </div>
            </x-input.group>


            <x-input.group for="movement_req" label="Movement Required" :error="$errors->first('editing.movement_req')">
                <x-input wire:model="editing.movement_req" id="movement_req" type="text" />
                <div class="text-xs leading-tight">The number of movement points moving through this tile requires. E.g. a road would take fewer than grass or mountains.</div>
            </x-input.group>

            <x-input.group for="skill_id_req" label="Skill Required" :error="$errors->first('editing.skill_id_req')">
                <select id="skill_id_req" class="w-full" wire:model="editing.skill_id_req">
                    <option value="">No Skill Required</option>
                    @foreach(Skill::all() as $skill)
                        <option value="{{$skill->id}}">{{ $skill->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" wire:loading.attr="disabled" wire:target="new3DModel,newImage" type="submit">Save</x-button>
            <x-button @click="show = false;"  wire:loading.attr="disabled" wire:target="new3DModel,newImage" >Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
