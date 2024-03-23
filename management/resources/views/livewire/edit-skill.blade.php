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

            @isset($editing->id)
                <x-input.group label="Had by Players">
                    <ul class="text-sm gap-x-5 grid grid-cols-3 text">
                        @foreach($editing->players as $player)
                            <li><span class="text-gray-500 text-xs mr-1">{{$player->id}}</span> <a class="link" href="{{route('players')}}?filters%5Bid%5D={{$player->id}}">{{$player->name}}</a></li>
                        @endforeach
                    </ul>
                </x-input.group>

                <x-input.group label="Required by Tiles">
                    <ul class="gap-x-5 grid grid-cols-3 text">
                        @foreach($editing->mapTileTypes as $mapTileType)
                            <li><span class="text-gray-500 text-xs mr-1">{{$mapTileType->id}}</span> <a class="link" href="{{route('map-tile-types')}}?filters%5Bid%5D={{$mapTileType->id}}">{{$mapTileType->name}}</a></li>
                        @endforeach
                    </ul>
                </x-input.group>

            @endisset


        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>
</form>
