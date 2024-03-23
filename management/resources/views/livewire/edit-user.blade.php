{{-- Editor dialog to make changes to the record (or make a new one).
     I don't feel that there's much value in extracting this to a template since almost everything could be different,
     except for the save and cancel buttons, maybe.
 --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="username" label="Username" :error="$errors->first('editing.username')">
                <x-input wire:model="editing.username" id="username" type="text" />
            </x-input.group>

            <x-input.group for="email" label="Email" :error="$errors->first('editing.email')">
                <x-input wire:model="editing.email" id="email" type="text" />
            </x-input.group>

            <x-input.group for="locale_id" label="Language" :error="$errors->first('editing.locale_id')">
                <select class="w-full" id="locale_id" wire:model="editing.locale_id">
                    <option value="">Select a Language</option>
                    @foreach(\App\Models\Language::where('supported', 1)->get() as $language)
                        <option value="{{$language->locale_id}}">{{ $language->name }} ({{ $language->locale_id }})</option>
                    @endforeach
                </select>
            </x-input.group>


            <x-input.group for="passwordChanging" label="Change Password" :error="$errors->first('passwordChange')">
                @if (! $passwordChanging)
                    <input type="checkbox" id="passwordChanging" wire:model="passwordChanging" />
                @else
                    <input type="hidden" wire:model="passwordChanging" />
                    <x-input wire:model="passwordChange" id="passwordChange" type="text" />
                    <div class="mt-2">
                        <x-button wire:click="generatePassword">Generate<i class="fas fa-random pl-2"></i></x-button>
                        <x-button class="ml-3 text-center" id="copyNewPasswordBtn" onclick="copyNewPassword()">Copy<i class="fas fa-copy pl-2"></i></x-button>
                    </div>
                @endif

            </x-input.group>

            @if($editing->id)
                <x-input.group label="Players">
                    @foreach($editing->players as $player)
                        <div wire:key="player-{{$player->id}}">
                            <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$player->id}}', name:'{{addSlashes($player->name)}}', className:'Player' })">
                                <i class="fas fa-times text-red-500"></i>
                            </button>
                            <a class="link ml-3" href="{{route('players')}}?filters[id]={{$player->id}}">{{ $player->name }}</a>
                            <span class="ml-1 opacity-80 text-xs">{{ $player->experience }} <span class="opacity-50 text-xs">XP</span></span>
                        </div>
                    @endforeach
                </x-input.group>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

    <script>
        function copyNewPassword() {
            /* Get the text field */
            let copyText = document.getElementById("passwordChange");

            /* Select the text field */
            copyText.select();
            copyText.setSelectionRange(0, 99); /* For mobile devices */

            /* Copy the text inside the text field */
            document.execCommand("copy");

            document.getElementById('copyNewPasswordBtn').innerHTML = 'Copy<i class="fas fa-check pl-2"></i>';
        }
    </script>

</form>
