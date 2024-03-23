{{-- Add MathJax for this view --}}
@push('scripts')
    <script type="text/javascript" id="MathJax-script" async
            src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js">
    </script>
@endpush
<x-data-table :objectClass="$objectClass" :objects="$objects" new>
    <x-slot name="bulkActions">
        <x-dropdown-link href="#" class="cursor-pointer text-red-500" x-on:click="$dispatch('confirm-bulk-action')">
            <i class="fas fa-trash"></i> &nbsp; Delete Selected
        </x-dropdown-link>
    </x-slot>

    <x-slot name="filterInputs">
        <x-input wire:model="filters.name"
                 type="text"
                 placeholder="search by name/id/text"
                 class="w-full sm:w-80 text-center"
        />

        <select wire:model="filters.quest_profession_id">
            <option value="">All Professions</option>
            <option value="__NULL__">No Profession</option>
            @foreach(Profession::orderBy('name')->get() as $profession)
                <option value="{{$profession->id}}">{{ $profession->name }}</option>
            @endforeach
        </select>

        <select wire:model="filters.quest_result_type_id">
            <option value="">All Result Types</option>
            @foreach(QuestResultType::all() as $resultType)
                <option value="{{$resultType->id}}">{{ str_ireplace(' (quest content)', ' QC', $resultType->name) }}</option>
            @endforeach
        </select>

        {{-- This is here to help with clean up of weird types that I couldn't easily fix during the DB upgrade --}}
        <select wire:model="filters.old_result_type_id" class="w-28">
            <option value="">Old Results</option>
            <option value="1">True/False (items) - 1</option>
            <option value="2">Single Choice (items) - 2</option>
            <option value="3">Choose Multiple (items) - 3</option>
            <option value="4">Cloze - 4</option>
            <option value="5">Text Answers - 5</option>
            <option value="6">Order (items) - 6</option>
            <option value="7">Quest Items - 7</option>
            <option value="8">Check In (only) - 8</option>
            <option value="9">Check In (with Item) - 9</option>
            <option value="10">Calculation - 10</option>
            <option value="11">Multiple Calculation (items) - 11</option>
            <option value="12">Multiple text answers (items) - 12</option>
            <option value="13">Coordinates - 13</option>
            <option value="14">Multiple Coordinates (items) - 14</option>
            <option value="15">True/False - 15</option>
            <option value="16">Single Choice - 16</option>
            <option value="17">Multiple Calculation - 17</option>
            <option value="18">Multiple text answers - 18</option>
            <option value="19">Speaking (Conversation) - 19</option>
        </select>

    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="level" class="text-center">Level</x-datatable.th>
        {{--<x-datatable.th :sorts="$sorts" field="opportunity" class="text-center">Opp.</x-datatable.th>--}}
        <x-datatable.th :sorts="$sorts" field="giver_npc_id" class="text-left">Giver</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="target_npc_id" class="text-left">Target</x-datatable.th>
        {{--<x-datatable.th :sorts="$sorts" field="content" class="text-left">Content</x-datatable.th>--}}
        <x-datatable.th :sorts="$sorts" field="quest_result_type_id" class="text-left">Result Type</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quest_tools_count" class="text-center">Tools</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quest_items_count" class="text-center">Items</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="reward_profession_xp" class="text-center">ProfXP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="reward_xp" class="text-center">XP</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="reward_money" class="text-center">Reward</x-datatable.th>
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

            <td class="text-black dark:text-white text-base">
                {{ $object->name }}
            </td>

            <td class="text-center">{{$object->level}}</td>
            {{--<td class="text-center">{{$object->opportunity}}</td>--}}

            <td><a class="link" href="{{route('npcs')}}?filters%5Bid%5D={{$object->giver_npc_id}}">{{ $object->giverNpc->name }}</a></td>

            <td>@isset($object->target_npc_id)
                    <a class="link" href="{{route('npcs')}}?filters%5Bid%5D={{$object->target_npc_id}}">{{ $object->targetNpc->name }}</a>
                @endisset</td>

            <td><span class="text-xs text-gray-500 mr-1">{{ $object->quest_result_type_id }}</span>{{ $object->questResultType->name }}</td>
            <td class="text-center">{{ $object->quest_tools_count }}</td>
            <td class="text-center">{{ $object->quest_items_count }}</td>
            <td class="text-center">{{ $object->reward_profession_xp }}</td>
            <td class="text-center">{{ $object->reward_xp }}</td>
            <td class="text-center">${{ $object->reward_money }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                @if($object->players_count == 0)
                    <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
                @endif
            </td>
        </tr>

        <tr class="hidden"><td colspan="13"></td></tr>
        <tr wire:key="row-{{$object->id}}map">
            <td colspan="5" class="max-w-xs align-top"><div class="pt-2 pb-6"><span class="inline-block mr-2 opacity-60 font-semibold text-xs rounded bg-gray-500/30 p-0.5 px-1">Prologue</span>{{ Str::words($object->prologue, 100) }}</div></td>
            <td colspan="3" class="max-w-xs align-top"><div class="pt-2 pb-6"><span class="inline-block mr-2 opacity-60 font-semibold text-xs rounded bg-gray-500/30 p-0.5 px-1">Content</span>{{ Str::words($object->content, 100) }}</div></td>
            <td colspan="8" class="max-w-xs align-top"><div class="pt-2 pb-6"><span class="inline-block mr-2 opacity-60 font-semibold text-xs rounded bg-gray-500/30 p-0.5 px-1">Target NPC</span>{{ Str::words($object->target_npc_prologue, 70) }}</div></td>
        </tr>

    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-quest /></x-slot>


</x-data-table>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        Livewire.on('typesetMathJax', function(){
            // console.log('Typesetting Math...');
            setTimeout(function(){
                MathJax.typeset();
            });


        });
    });
</script>
