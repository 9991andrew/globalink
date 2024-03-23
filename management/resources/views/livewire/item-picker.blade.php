<div class="itemPicker relative py-0 px-1 w-full border border-black"
    x-data="{searchMode:@entangle('searchMode')}"
    x-on:click.away="searchMode = false"
    x-on:keydown.escape.stop="searchMode = false; return false;"
    x-on:keydown.enter.stop="searchMode = !searchMode; return false;">
    {{--  Control whether we are in search or display mode  --}}
    {{-- store the id of the currently selected item --}}
    {{--<input type="hidden" wire:model="selectedId">--}}
    <x-input wire:model="filters.name"
             type="text"
             placeholder="search by name/id"
             class="w-full mb-0 mt-1"
             x-ref="searchText"
             style="border-radius:4px;"
    />
        {{-- Now make a pretty list of all the options with metadata --}}
    <div x-show="searchMode" class="absolute flex flex-col space-y-1 overflow-x-scroll dark:bg-gray-800 bg-gray-100 w-full rounded shadow-xl border border-gray-500" style="max-height:200px;z-index:60">
        @foreach($objects as $object)
            <label for="{{$field}}{{$object->id}}" class="flex items-center rounded border border-transparent hover:border-gray-500 hover:bg-gray-300 dark:hover:bg-black cursor-pointer m-1">
                <div class="w-6 text-center">
                    <input x-bind:disabled="!searchMode" class="" type="radio" id="{{$field}}{{$object->id}}" value="{{ $object->id }}" wire:model="selectedId">
                </div>
                @php
                    $tnUrl = '';
                    $info = '';
                    if (isset($object->tnUrl)) $tnUrl=$object->tnUrl;
                    else if (isset($object->itemIcon->tnUrl)) {
                        $tnUrl=$object->itemIcon->tnUrl;
                        $info = '$'.$object->price.' <strong>Lev</strong> '.$object->level.' <strong class="ml-1">Req</strong> '.$object->required_level.' <strong class="ml-1">Qty</strong> '.$object->amount;
                    }
                    else if (isset($object->item->itemIcon->tnUrl))
                    {
                        $tnUrl=$object->item->itemIcon->tnUrl;
                        $info = '$'.$object->item->price.' <strong>Lev</strong> '.$object->item->level.' <strong class="ml-1">Req</strong> '.$object->item->required_level.' <strong class="ml-1">Qty</strong> '.$object->amount;
                    }
                    // Support NPCs
                    else if (isset($object->npcIcon))
                    {
                        $tnUrl=$object->npcIcon->tnUrl;
                        $info = '<strong>Lev</strong> '.$object->level;
                        if (isset($object->map_id)) $info.=' <strong>Map</strong> '.$object->map->name;
                    }

                    $info = '<div class="text-xs text-gray-500">'.$info.'</div>';

                @endphp
                <img class="m-1 mr-2" src="{{ $tnUrl }}" style="max-width:50px;max-height:50px;width:auto;height:auto;">
                <div class="flex flex-col">
                    <div><span class="text-sm mr-1 text-gray-500">{{ $object->id }}</span>{{ $object->name }}</div>
                    @isset($info){!! $info !!}@endisset
                </div>
            </label>
        @endforeach
        @if($count < $totalMatches)
                <div class="text-center"><button x-bind:disabled="!searchMode" type="button" class="link" wire:click="unlimitedPages">+ {{ $totalMatches - $count }} more</button></div>
        @endif
        @if($count == 0)
                <div class="text-center text-gray-500 text-sm">No matches found</div>
        @endif
    </div>

    <div x-on:click="searchMode = !searchMode; $refs.searchText.focus()" class="p-1 itemPickerSelected flex items-center rounded cursor-pointer" style="height:50px;">
        @isset($selected)
            @php
                $tnUrl = '';
                if (isset($selected->tnUrl)) $tnUrl=$selected->tnUrl;
                else if (isset($selected->itemIcon->tnUrl)) $tnUrl=$selected->itemIcon->tnUrl;
                else if (isset($selected->item->itemIcon->tnUrl)) $tnUrl=$selected->item->itemIcon->tnUrl;
                else if (isset($selected->npcIcon)) $tnUrl=$selected->npcIcon->tnUrl;
            @endphp
            <div class="w-5 flex justify-center items-center">
                <input class="" type="radio" value="{{ $selected->id }}" wire:model="selectedId">
            </div>
            <img class="m-1 mr-2" src="{{ $tnUrl }}" style="max-width:50px;max-height:50px;width:auto;height:auto;">
            <div class="flex flex-col leading-tight">
                <div><span class="text-sm mr-1 text-gray-500">{{ $selected->id }}</span> {{ $selected->name }}</div>
                @isset($selected->map_id)<div class="text-xs"><span class="mr-1 text-gray-500"><strong>Map</strong> {{$selected->map_id}}</span>{{$selected->map->name}}</div>@endisset
            </div>
        @else
            <div class="text-center p-1">Select an {{$objectClass}}</div>
        @endisset
    </div>
    {{--
        Need some JS magic here.

        Expected behaviour:
        By default no item is shown.
        When user clicks element, it expands and shows the text search and about three items.
        User can then type into text input and scroll through items.
        (Bonus points: User can use arrows to scroll through items as well)
        When user clicks on an item
            - it adds the item ID into the hiden input
            - It hides the text box and all items other than the selected input.

        So basically there are two modes:
            1. Display the current item
            2. Search for an item.

        Current item should be shown as selected (maybe through background color or border)
    --}}


    {{-- Use stacks to put this into the header or footer or wherever it makes sense. Can I do this conditionally so I
     don't have duplcate scripts if this is added to a page more than once? --}}

</div>
