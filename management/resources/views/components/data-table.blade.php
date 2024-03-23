@props([
    'objectClass' => null,
    'objects' => null,
    'title' => null,
    'total' => null,
    'pageOptions' => null,
    'noBulk' => false,
    'new' => false,
])
@php
    // Set some defaults
    if (! isset($title)) {
        $title = Str::plural(camelToSpaced($objectClass));
    }

    if (! isset($total)) {
        $total = $objectClass::count();
    }

    if (! isset($pageOptions)) {
        $pageOptions = array(10,25,100,500,1000,5000);
    }
@endphp
<div
    @show-edit.window="showModal = !showModal"
    x-data="loadComponent()"
    x-init="
     // Keep the allChecked checkbox in sync with whether all checkboxes on this page are checked or not
     $watch('selected', value => updateAllChecked()),
     $watch('allChecked', value => {if (allSelected && !allChecked) allSelected=false;}),
     // Remove duplicates from the selected array. This is required due to an apparent bug in Alpine.js
     $watch('selected', value => removeSelectedDuplicates())
    "
    x-on:confirm-bulk-action.window="confirmBulkAction"
    x-on:confirm-action.window="confirmAction($event.detail.id, $event.detail.name, $event.detail.className, $event.detail.pivot, $event.detail.pivotId)"
>
    <x-header>
        <x-title>
            {{ $title }} <span class="text-lg font-normal"> &nbsp; {{ $total }} records</span>

            <select wire:model="perPage"
                class="ml-4 text-sm"
                style="min-width:75px">
                @foreach($pageOptions as $pageOption)
                <option value="{{ $pageOption }}">{{ $pageOption }}</option>
                @endforeach
            </select> <span class="text-sm">per&nbsp;page</span>
        </x-title>

        <div id="titleButtons" class="mt-1 flex space-x-4 justify-end flex-auto">
            {{-- if the template has the new parameter set, we show a "new" button --}}
            @if($new) <x-button class="self-end" x-on:click="loadModal"><i class="fas fa-plus pr-2"></i>New</x-button> @endif
            @unless($noBulk)
            <div :class="{'opacity-50': !selected.length}">
            <x-dropdown width="48">
                <x-slot name="trigger">
                    <x-button >Bulk Actions &nbsp; <i class="fas fa-chevron-down"></i></x-button>
                </x-slot>

                <x-slot name="content">
                    {{ $bulkActions }}
                </x-slot>
            </x-dropdown>
            </div>
            @endunless
        </div>
    </x-header>
    <form class="m-4 flex flex-wrap justify-center space-x-3" wire:submit.prevent="$refresh">
        {{-- Mainly used when linking to a specific id --}}
        <input type="hidden" wire:model.lazy="filters.id" />
        {{ $filterInputs }}
        @if($total > $objects->total() && $total > 0)
            <div class="w-full text-center"><button class="link text-sm" wire:click="resetFilters">Show all {{ $total }} {{ Str::plural($objectClass) }}</button></div>
        @endif
    </form>

    <div id="page-links-top" class="flex justify-center">{{ $objects->links() }}</div>

    <div class="flex justify-center">
        <div class="loader fixed h-full w-full flex items-center justify-center"
             wire:loading.delay></div>
        <div class="m-2 shadow overflow-auto sm:rounded-lg">
            <table id="data-table" class="-mt-1 dataTable text-sm">
                <thead class="bg-gray-300 dark:bg-gray-700">
                    <tr>
                        {{-- Column headings --}}
                        @unless($noBulk)
                        <x-datatable.th class="w-8">
                            <input type="checkbox" class="mb-1" x-model="allChecked" @change="checkAllOnPage" />
                        </x-datatable.th>
                        @endUnless

                        {{ $tableHeadings }}
                        {{-- These columns originally had "sr-only" labels for screenreaders but they caused issues with narrow layouts--}}
                        <x-datatable.th></x-datatable.th>
                        <x-datatable.th></x-datatable.th>
                    </tr>
                    {{-- Set colspan to arbitrarily large value. Can this cause performance issues? --}}
                    <tr x-show="selected.length"><td colspan="20" class="text-center space-x-4">
                        <span x-show="!allSelected">
                            <span x-text="selected.length"></span> of {{ $objects->total() }} items selected
                        </span>
                        <span x-show="allSelected">All {{ $objects->total() }} items selected</span>
                        <x-button wire:click="selectAll" x-show="!allSelected" class="mb-1">Select All</x-button>
                        <x-button @click="selected=[]" class="mb-1">Deselect All</x-button>
                    </td></tr>
                </thead>
                {{-- Add wire:loading.class.delay="opacity-80" to have it "fade" when loading --}}
                <tbody wire:loading.class="opacity-70 transition-opacity duration-300 ease-in">
                    {{-- Here's the main slot where all the table columns will go --}}
                    @if(strlen(trim($slot)) > 10)
                        {{ $slot }}
                    @else
                        {{-- Show this if the slot has no content (implying no records were shown) --}}
                        <tr class="noRecords  even:bg-gray-200 dark:even:bg-gray-850 odd:bg-gray-50 dark:odd:bg-gray-950">
                            <td colspan="20" class="text-center text-gray-500/75 text-lg p-5">No records found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div id="page-links-bottom" class="flex justify-center">{{ $objects->links() }}</div>

    {{ $editModal }}

    {{-- Confirm Dialog used for deleting records. I suppose it could be used for other mass-editing operations --}}
    <form wire:submit.prevent="delete">
        <x-modal.confirmation>
            <x-slot name="title">
                {{-- @isset($confirmTitle){{ $confirmTitle }}@endisset --}}
                <span x-html="confirmTitleHtml"></span>
            </x-slot>

            <x-slot name="content">
                <span x-html="confirmHtml"></span>
            </x-slot>
            <x-slot name="footer">
                <form>
                <x-button x-on:keydown.enter.window="if (showConfirm) $refs.deleteBtn.click();" x-ref="deleteBtn" id="deleteBtn" class="highlight" type="submit">Delete</x-button>
                <x-button @click="showConfirm = false">Cancel</x-button>
                </form>
            </x-slot>
        </x-modal.confirmation>
    </form>

    <div class="text-center p-4 leading-tight"><a href="#page-links-top" class="link"><i class="fas fa-angle-double-up"></i><br>Jump to Top</a></div>

    <script>
        // Scroll to top when clicking on bottom page selector
        document.querySelector('#page-links-bottom').addEventListener('click', function() {
            document.querySelector('#data-table').scrollIntoView({
                // behavior: 'smooth'
            });
            return false;
        });

        // Fetch player information and load it into the modal
        function loadComponent() {
            return {
                showConfirm:@entangle('showConfirm').defer,
                confirmTitleHtml: '',
                confirmHtml: '',
                confirmId:@entangle('confirmId').defer,
                confirmClass:@entangle('confirmClass').defer,
                allChecked:@entangle('selectPage').defer,
                selected: @entangle('selected').defer,
                pivot: @entangle('pivot').defer,
                pivotId: @entangle('pivotId').defer,
                allSelected: @entangle('allSelected'),

                checkAllOnPage() {
                    // Array of all elements on page
                    let arr = Array.from(document.querySelectorAll('.selectRow')).map(el=>el.value);
                    if (this.allChecked) {
                        // Merge arrays without duplicates using a Set
                        this.selected = [...new Set(this.selected.concat(arr))];
                    } else {
                        // Else we remove these elements from the array
                        this.selected = this.selected.filter( ( el ) => !arr.includes(el) );
                    }
                },

                // This is to work around a bug in Alpine where if an item is deleted, selecting the next item
                // makes it show in the select list multiple times. I wish I didn't need this crappy hack.
                removeSelectedDuplicates() {
                    // Be careful not to cause an infinite loop
                    let s = [...new Set(this.selected)]
                    if (this.selected.length != s.length) this.selected=s;
                },

                updateAllChecked() {
                    // if all the '.selectRow' are checked, allSelected is true, else false.
                    let arr = Array.from(document.querySelectorAll('.selectRow')).map(el=>el.value);
                    this.allChecked = arr.every(el => this.selected.includes(el));
                },

                loadModal(id=null) {
                    Livewire.emit('updateEditing', id);
                },

                confirmAction(id,name,className='{{$objectClass}}',pivot,pivotId) {
                    // This should prevent errors when deleting an item that's loaded into the editor
                    if (className == '{{$objectClass}}' && typeof pivot === "undefined") Livewire.emit('cleanup');
                    this.confirmId=id;
                    this.confirmClass=className;
                    this.confirmTitleHtml='<span class="opacity-80">Delete '+className+'</span> <span class="pl-1 opacity-100">'+name+'</span><span class="opacity-80">?</span>';
                    this.confirmHtml="All associated data will be deleted.<br>This cannot be undone.";
                    this.showConfirm=true;
                    if (typeof pivot !== "undefined" && typeof pivotId !== "undefined") {
                        this.pivot = pivot;
                        this.pivotId = pivotId;
                    }
                    // Focus delete button
                    document.getElementById('deleteBtn').focus();
                },

                confirmBulkAction() {
                    Livewire.emit('cleanup');
                    this.confirmId=null;
                    this.confirmTitleHtml='<span class="opacity-80">Delete </span> <span class="opacity-100">'+this.selected.length+'</span> selected {{ Str::plural(camelToSpaced($objectClass)) }}<span class="opacity-80">?</span>';
                    this.confirmHtml="All associated data will be deleted.<br>This cannot be undone.";
                    this.showConfirm=true;
                }
            }
        }

        function copy(that){
            var inp =document.createElement('input');
            document.body.appendChild(inp)
            inp.value =that.textContent
            inp.select();
            document.execCommand('copy',false);
            inp.remove();
        }

    </script>

</div>
