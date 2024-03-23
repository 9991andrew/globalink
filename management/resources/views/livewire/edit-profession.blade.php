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

            <x-input.group for="require_all_prerequisites" label="Require All Prerequisites" :error="$errors->first('editing.require_all_prerequisites')">
                <input wire:model="editing.require_all_prerequisites" id="require_all_prerequisites" type="checkbox" />
                <div class="text-xs">If checked, players must first obtain every prerequisite profession/xp.<br>
                Else players only require any one prerequisite.</div>
            </x-input.group>

            {{-- Fill in any prerequisite professions. Probably will work a bit like the color picker for avatar items --}}
            @isset($editing->id)
                <x-input.group label="Prerequisites">
                    <div class="flex space-x-2">
                        <select wire:model="profession_id_req" id="profession_id_req" class="flex-1 min-w-0" max="35" placeholder="Profession">
                            <option></option>
                            @php
                                $existingReqs = ProfessionPrerequisite::where('profession_id', $editing->id)
                                    ->pluck('profession_id_req')->toArray();
                            @endphp
                            @foreach(Profession::whereNotIn('id', $existingReqs)->where('id', '!=', $editing->id)->get() as $profession)
                                <option value="{{$profession->id}}">{{$profession->name}}</option>
                            @endforeach
                        </select>
                        <input type="text" class="w-16" wire:model="profession_xp_req" placeholder="xp"/>
                        <x-button type="button" class="ml-2" wire:click="addPrerequisiteProfession">Add</x-button>
                    </div>
                    <div class="text-xs">The following profession XP must be obtained first</div>
                    @if($errors->first('profession_id_req'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('profession_id_req')}}</div>@endif
                    @if($errors->first('profession_xp_req'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('profession_xp_req')}}</div>@endif
                    <ul>
                        @foreach($editing->prerequisites as $prerequisite)
                            <li>
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$prerequisite->id}}', name:'{{addSlashes($prerequisite->professionRequired->name)}}', className:'ProfessionPrerequisite' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{ $prerequisite->professionRequired->name }} <span class="ml-2 text-gray-500">{{$prerequisite->profession_xp_req}}<span class="text-xs"> xp</span></span>
                            </li>
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
