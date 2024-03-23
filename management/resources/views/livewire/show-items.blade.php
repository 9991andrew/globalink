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
                 placeholder="search by name/description/id"
                 class="w-full sm:w-80 text-center"
        />

        <select wire:model="filters.item_category_id">
            <option value="">All Categories</option>
            <option value="__NULL__">No Category</option>
            @foreach(ItemCategory::orderBy('name')->get() as $category)
                <option value="{{$category->id}}">{{ $category->name }}</option>
            @endforeach
        </select>
        {{-- Could add filter by creation date - existing users dont' have one. --}}
    </x-slot>

    <x-slot name="tableHeadings">
        <x-datatable.th :sorts="$sorts" field="id">ID</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="name" class="text-left">Name</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="item_category_id" class="text-left">Category</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="item_icon_id" class="text-left">Icon</x-datatable.th>
        <x-datatable.th class="text-center">Thumbnail</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="amount">Qty</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="description" class="text-left">Description</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="item_effect_id" class="text-left">Effect</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="item_effect_parameters" class="text-left">Param</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="weight">Weight</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="required_level">Req.Lvl</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="level">Lvl</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="price">Price</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="max_amount">Max</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="players_count">Owners</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="quests_count">Quests</x-datatable.th>
        <x-datatable.th :sorts="$sorts" field="tools_count">Tools</x-datatable.th>
    </x-slot>

    {{-- Begin main slot - all the data rows --}}
    @foreach($objects as $object)
        <tr wire:key="row-{{ $object->id }}">
            <td><input type="checkbox" class="selectRow" value="{{ $object->id }}" x-model="selected" /></td>
            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})">{{ $object->id }}</x-button.edit>
            </td>
            @if($object->disabled)
                <td class="text-black dark:text-white" title="disabled"><s class="opacity-60">{{ $object->name }}</s></td>
            @else
                <td class="text-black dark:text-white">{{ $object->name }}</td>
            @endif

            {{-- I suspect I could modify the eloquent query to include these names to improve performance --}}
            <td>{{ $object->item_category_id?$object->itemCategory->name:'' }}</td>
            <td>{{ $object->item_icon_id?$object->itemIcon->name:'' }}</td>
            <td class="text-center">@if($object->item_icon_id)<img width="75" src="{{ $object->itemIcon->tnUrl }}">@endif</td>
            <td class="text-center">{{ $object->amount }}</td>
            <td>{{ Str::words($object->description, 60) }}</td>
            <td>{{ $object->item_effect_id?$object->itemEffect->name:'' }}</td>
            <td>{{ $object->item_effect_parameters }}</td>
            <td class="text-center">{{ $object->weight }}</td>
            <td class="text-center">{{ $object->required_level }}</td>
            <td class="text-center">{{ $object->level }}</td>
            <td class="text-center">{{ $object->price }}</td>
            <td class="text-center">{{ $object->max_amount }}</td>
            <td class="text-center">{{ $object->players_count }}</td>
            <td class="text-center">{{ $object->quests_count }}</td>
            <td class="text-center">{{ $object->tools_count }}</td>

            <td class="text-center">
                <x-button.edit x-on:click="loadModal({{$object->id}})" />
            </td>

            <td class="text-center">
                <x-button.delete x-on:click="confirmAction('{{$object->id}}', '{{addSlashes($object->name)}}')" />
            </td>
        </tr>
    @endforeach
    {{-- End main slot --}}

    <x-slot name="editModal"><livewire:edit-item /></x-slot>

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
