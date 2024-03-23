{{-- I wish I didn't have to pass all this data through... maybe there's an easier way?--}}
<x-data-table :objectClass="$objectClass" :objects="$objects">
    <x-slot name="bulkActions">
        <x-dropdown-link href="#" class="cursor-pointer" wire:click="selectedReturnToBirthplace">
            <i class="fas fa-map-marked-alt"></i> &nbsp; Return to Birthplace
        </x-dropdown-link>

        <x-dropdown-link href="#" class="cursor-pointer" wire:click="addMovementToSelected(500)">
            <i class="fas fa-shoe-prints"></i> &nbsp; Add 500 Movement
        </x-dropdown-link>

        <x-dropdown-link href="#" class="cursor-pointer" wire:click="addMoneyToSelected(20)">
            <i class="fas fa-coins"></i> &nbsp; Give $20
        </x-dropdown-link>

        <x-dropdown-link href="#" class="cursor-pointer" wire:click="healSelected">
            <i class="fas fa-heartbeat"></i> &nbsp; Heal to 100
        </x-dropdown-link>

        <x-dropdown-link href="#" class="cursor-pointer text-red-500" x-on:click="$dispatch('confirm-bulk-action')">
            <i class="fas fa-trash"></i> &nbsp; Delete Selected
        </x-dropdown-link>
    </x-slot>

    <x-slot name="filterInputs">
        <x-input wire:model="filters.name"
                 type="text"
                 placeholder="search by name/id"
                 class="w-full sm:w-80 text-center"
        />
        <select wire:model="filters.birthplace_id">
            <option disabled>Filter by Birthplace</option>
            <option value="">All Birthplaces</option>
            @foreach(Birthplace::orderBy('name')->get() as $bp)
                <option value="{{$bp->id}}">{{Str::words($bp->name, 4)}}</option>
            @endforeach
        </select>
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name/User</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="birthplace_id">Birthplace</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="map_id" class="text-left">Location</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="movement" class="text-right">Energy</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="money" class="text-right">Money</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="health" class="text-right">HP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="experience" class="text-center">XP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="professions_count" class="text-left">Professions</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="player_items_count" class="text-center">Items</x-datatable.th>
        {{-- this version allowed sorting by the number of professions. Probably not useful enough to be worth the trouble.
        <x-datatable.th sortable multi-column field="professions_count">Professions</x-datatable.th>--}}
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td>
                <input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" />
            </td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>

            <td>
                <span class="text-black dark:text-white">{{ $object->name }}</span>
                <div>
                    <span class="text-xs text-gray-500/80">XP</span> {{ $object->experience }} &nbsp;
                    <i class="text-base fas @if($object->gender->name=='Male') fa-mars text-cyan-400 @elseif($object->gender->name=='Female') fa-venus text-red-400 @endif "></i>
                    <span class="text-xs text-gray-500/80">{{ substr($object->race->name, 0, 3) }}</span>
                </div>
                {{-- Show the user info - perhaps just a user icon without the name would work, click it to pop up a user --}}
                {{-- <span class="text-xs"><i class="text-gray-500/80 fas fa-user"></i> &nbsp; {{ $object->user->username }}</span> --}}
            </td>

            <td>@isset($object->birthplace)
                {{ Str::words($object->birthplace->name, 5) }}<br>
                <span class="text-xs"><span class="text-gray-500/80">{{ $object->birthplace->map->maptype->name }}</span> &nbsp; {{ $object->birthplace->map->name }}</span>
                @endisset
            </td>

            <td>@isset($object->map)
                {{ $object->map->name }}<br>
                <span class="text-xs"><span class="text-gray-500/80">x </span>{{$object->x}}&nbsp; <span class="text-gray-500/80">y </span>{{$object->y}}</span>
                @endisset
            </td>

            <td class="text-right">{{ $object->movement }}</td>

            <td class="text-right">
                <span class="text-gray-500/80">$</span>{{ $object->money }}
            </td>

            <td class="text-right">
                {{ $object->health }}<br>
                <span class="text-xs"><span class="text-gray-500/80">of </span>{{$object->health_max}}</span>
            </td>

            <td class="text-center">
                {{ $object->experience }}
            </td>

            <td>
                @foreach($object->professions as $profession)
                    <div class="mb-2 leading-tight">
                        {{ $profession->name }}
                        <div class="text-xs"><span class="text-gray-500/80">Level </span>{{$profession->pivot->profession_level}} &nbsp; <span class="text-gray-500/80">XP</span> {{$profession->pivot->profession_xp}}</div>
                    </div>
                @endforeach
            </td>

            <td class="text-center">{{ $object->player_items_count }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                {{-- This will break when we have objects without name or id...
                I'll add conditional handling when that happens --}}
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-player /></x-slot>

</x-data-table>
