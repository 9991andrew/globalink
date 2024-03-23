{{-- Editor dialog to make changes to the record (or make a new one). --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id) Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="name" label="Login Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="email" label="Email" :error="$errors->first('editing.email')">
                <x-input wire:model="editing.email" id="email" type="text" />
                <div class="text-xs">This email is not used for login. In the future this could be used for password resets.</div>
            </x-input.group>

            {{-- I've just populated a few time zones here, this is far from comprehensive but should cover Canada --}}
            <x-input.group for="time_zone" label="Time Zone" :error="$errors->first('editing.time_zone')">
                <select class="w-full" wire:model="editing.time_zone">
                    <option value="UTC">UTC</option>
                    <option value="America/Chicago">Chicago</option>
                    <option value="America/Chihuahua">Chihuahua</option>
                    <option value="America/Dawson">Dawson</option>
                    <option value="America/Dawson_Creek">Dawson Creek</option>
                    <option value="America/Denver">Denver</option>
                    <option value="America/Detroit">Detroit</option>
                    <option value="America/Edmonton">Edmonton</option>
                    <option value="America/Halifax">Halifax</option>
                    <option value="America/Inuvik">Inuvik</option>
                    <option value="America/Iqaluit">Iqaluit</option>
                    <option value="America/Jamaica">Jamaica</option>
                    <option value="America/Juneau">Juneau</option>
                    <option value="America/Los_Angeles">Los Angeles</option>
                    <option value="America/Mexico_City">Mexico City</option>
                    <option value="America/Moncton">Moncton</option>
                    <option value="America/New_York">New York</option>
                    <option value="America/Phoenix">Phoenix</option>
                    <option value="America/Regina">Regina</option>
                    <option value="America/Sao_Paulo">Sao Paulo</option>
                    <option value="America/St_Johns">St Johns</option>
                    <option value="America/Swift_Current">Swift Current</option>
                    <option value="America/Thunder_Bay">Thunder Bay</option>
                    <option value="America/Toronto">Toronto</option>
                    <option value="America/Vancouver">Vancouver</option>
                    <option value="America/Whitehorse">Whitehorse</option>
                    <option value="America/Winnipeg">Winnipeg</option>
                    <option value="America/Yellowknife">Yellowknife</option>
                    <option value="Asia/Taipei">Asia/Taipei</option>
                </select>
            </x-input.group>

            <x-input.group for="passwordChanging" label="Change Password" :error="$errors->first('passwordChange')">
                @if (! $passwordChanging && isset($editing->id))
                    <input type="checkbox" id="passwordChanging" wire:model="passwordChanging" />
                @else
                    {{--<input type="hidden" wire:model="passwordChanging" />--}}
                    <x-input wire:model="passwordChange" id="passwordChange" type="text" />
                    <div class="mt-2">
                        <x-button wire:click="generatePassword">Generate<i class="fas fa-random pl-2"></i></x-button>
                        <x-button class="ml-3 text-center" id="copyNewPasswordBtn" onclick="copyNewPassword()">Copy<i class="fas fa-copy pl-2"></i></x-button>
                    </div>
                @endif
            </x-input.group>

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
