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

            <x-input.group for="author" label="Author" :error="$errors->first('editing.author')">
                <x-input wire:model="editing.author" id="author" type="text" />
            </x-input.group>

            @isset($editing->id)
            @php $pageSize = 50; @endphp
            <x-input.group label="Used by">
                    <ul class="gap-x-5 gap-y-1 leading-tight grid grid-cols-4 text-xs">
                        @foreach(Npc::where('npc_icon_id', $editing->id)->paginate($pageSize) as $npc)
                            <li><a class="link" href="{{route('npcs')}}?filters[id]={{$npc->id}}">{{ $npc->name }}</a></li>
                        @endforeach
                    </ul>
                @if($editing->npcs->count() > $pageSize)
                    <div class="text-base text-center">and {{ $editing->npcs->count() - $pageSize }} more</div>
                @endif
            </x-input.group>
            @endisset


        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
