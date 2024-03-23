<div style="margin-left: 30%" :data="$data">
    <br />
    <h1>WSNLP Configuration Options</h1>
    <br />

    <form id="config-form" wire:submit.prevent="save" method="POST">
        <x-input.group for="quest_id" label="Choose a quest:  ">
            <select id="quest_id" wire:model="quest_id">
                @foreach($data['quests'] as $quest)
                    <option value="{{ $quest[0] }}">&nbsp&nbsp{{ $quest[1] }}</option>
                @endforeach
            </select>
        </x-input.group>

        <x-input.group for="maxbpm" label="Use maximum bipartite matching?  ">
            <input id="maxbpm" wire:model="maxbpm" type="checkbox"/>
        </x-input.group>

        <x-input.group for="autobpm" label="Use automatic detection for maximum bipartite matching?  ">
            <input id="autobpm" wire:model="autobpm" type="checkbox" />
        </x-input.group>

        <x-input.group for="ngrampos" label="Use N-Gram POS service?  ">
            <input id="ngrampos" wire:model="ngrampos" type="checkbox" />
        </x-input.group>

        <x-input.group for="canonical" label="Use the canonical method?  ">
            <input id="canonical" wire:model="canonical" type="checkbox" />
        </x-input.group>

        <x-input.group for="timeout" label="Comparison timeout in seconds: (10-9999) ">
            <input id="timeout" wire:model="timeout" type="text" size ="5" pattern="[1-9][0-9]{1,3}" />
        </x-input.group>

        <x-input.group for="language" label="Select a language to use:  ">
            <select id="language" wire:model="language">
                @foreach($data['languages'] as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </x-input.group>

        <br />
        <x-button class="highlight" type="submit">Save</x-button>
        &nbsp&nbsp
        <x-button onclick="window.location='/'">Cancel</x-button>
    </form>
</div>
