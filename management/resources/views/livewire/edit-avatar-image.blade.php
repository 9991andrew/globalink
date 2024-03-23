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

            {{-- Consider making this a file input and just adding the SVG contents to the DB. --}}
            <x-input.group for="filename" label="Filename" :error="$errors->first('editing.filename')">
                <x-input wire:model="editing.filename" id="filename" type="text" />
                <span class="text-xs">Optional. If this is used this overrides the SVG.</span>
            </x-input.group>

            <x-input.group for="avatar_image_type_id" label="Type" :error="$errors->first('editing.avatar_image_type_id')">
                <select class="w-full" wire:model="editing.avatar_image_type_id">
                    <option value="">Select a Type</option>
                    @foreach(\App\Models\AvatarImageType::orderBy('display_seq')->get() as $type)
                        <option value="{{$type->id}}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="svg_code" label="SVG Code" :error="$errors->first('editing.svg_code')">
                <textarea class="w-full text-xs" wire:model.lazy="editing.svg_code" rows="5" id="svg_code"></textarea>
                <div class="text-xs leading-tight">
                    Paste SVG image code here (designed with viewBox="0 0 330 460") if an external file is not used.<br>
                    Outer SVG tags are not required and will be removed.<br>
                    Use classes of <strong>primary</strong>, <strong>secondary</strong>, and <strong>tertiary</strong> on elements that have user adjustable fill color.
                </div>
            </x-input.group>

            {{-- Consider making this a file input and just adding the SVG contents to the DB. --}}
            <x-input.group for="color_qty" label="Number of Colors" :error="$errors->first('editing.color_qty')">
                <x-input wire:model="editing.color_qty" id="color_qty" type="text" />
                <span class="text-xs leading-tight">Make this automatic. This is to specify whether this image has primary, secondary, or tertiary colors.</span>
            </x-input.group>

            <x-input.group label="Preview">
                <div class="bg-gray-400 rounded">
                    @isset($editing->svg_code)<svg class="w-60 m-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 330 460">{!! $editing->svg_code !!}</svg>@endisset
                </div>
            </x-input.group>

            <x-input.group for="gender_id" label="Gender" :error="$errors->first('editing.gender_id')">
                <select class="w-full" wire:model="editing.gender_id">
                    <option value="">No Gender</option>
                    @foreach(Gender::orderBy('name')->get() as $type)
                        <option value="{{$type->id}}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group for="race_id" label="Race" :error="$errors->first('editing.race_id')">
                <select class="w-full" wire:model="editing.race_id">
                    <option value="">No Race</option>
                    @foreach(Race::orderBy('name')->get() as $type)
                        <option value="{{$type->id}}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </x-input.group>

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

</form>
