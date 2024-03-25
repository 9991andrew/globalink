<x-app-layout>
    <x-header>
        <x-title>
            MEGA World Management
        </x-title>
    </x-header>
    <div class="max-w-5xl m-auto">
        <div class="px-4 py-2">
            <h2 class="text-xl font-bold text-center">Player Data</h2>
            <!-- List of routes with a short description of what they do. Show any relevant data here if necessary -->
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <x-definition objectClass="User" class="font-bold">Delete users, change passwords, see the players each user has.</x-definition>
                <x-definition objectClass="Player" class="font-bold">Player status info, heal, give money, return to birthplaces.</x-definition>
                <x-definition objectClass="Birthplace">Edit locations where players may start their game.</x-definition>
                <x-definition objectClass="AvatarImage">Discrete parts players can select to create an avatar.</x-definition>
                <x-definition objectClass="AvatarImageType">Types of avatar parts, here color options can be set.</x-definition>
            </dl>
        </div>

        <div class="px-4 py-2">
            <h2 class="text-xl font-bold text-center">Game World</h2>
            <!-- List of routes with a short description of what they do. Show any relevant data here if necessary -->
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <x-definition objectClass="MapType">Maps are grouped into "worlds" under these types.</x-definition>
                <x-definition objectClass="MapTileType">Administer the types of tiles that can be added to maps.</x-definition>
                <x-definition objectClass="Map" class="font-bold">Add/edit maps.</x-definition>
                <x-definition objectClass="Skill">Skills used by players to traverse certain map tile types.</x-definition>
                <x-definition objectClass="Building">Add/edit buildings to allow players to travel between maps.</x-definition>
                <x-definition objectClass="Profession">Add/edit Professions used by players and NPCs.</x-definition>
                <x-definition objectClass="ItemCategory">Categories of items used by quests or sold by NPCs.</x-definition>
            </dl>
        </div>

        <div class="px-4 py-2">
            <h2 class="text-xl font-bold text-center">Quest &amp; NPC</h2>
            <!-- List of routes with a short description of what they do. Show any relevant data here if necessary -->
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <x-definition objectClass="ItemIcon">Add/edit icons used by items.</x-definition>
                <x-definition objectClass="Item" class="font-bold">Edit items used by players in the game.</x-definition>
                <x-definition objectClass="NpcIcon">Add/edit icons used by NPCs.</x-definition>
                <x-definition objectClass="Npc" class="font-bold">Add/edit Non-Player Characters.</x-definition>
                <x-definition objectClass="Quest" class="font-bold">Add/edit quests that players can attempt to complete.</x-definition>
                <x-definition objectClass="QuestTool">Add/edit locations where quest tools can be used.</x-definition>
            </dl>
        </div>

        <div class="px-4 py-2">
            <h2 class="text-xl font-bold text-center">Management</h2>
            <!-- List of routes with a short description of what they do. Show any relevant data here if necessary -->
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <x-definition objectClass="ManagementUser">Add/edit administrative users for management.</x-definition>
                <x-definition objectClass="Language">Add or enable support for languages.</x-definition>
                <div class="py-2 px-4 w-full sm:w-1/2">
                    <dt class="text-lg">
                        {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
                        <a href="{{route('help')}}" class="link pr-1">Help Guide & Video</a>
                    </dt>
                    <dd>Video tutorial and documentation.</dd>
                </div>
            </dl>
        </div>
        <div class="px-4 py-2">
            <h2 class="text-xl font-bold text-center">Player versus Environment</h2>
            <!-- List of routes with a short description of what they do. Show any relevant data here if necessary -->
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <div class="py-2 px-4 w-full sm:w-1/2">
                    <dt class="text-lg">
                        {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
                        <a href="{{route('monster')}}" class="link pr-1">Monster</a>
                    </dt>
                    <dd>Add/Edit Monster</dd>
                </div>
            </dl>
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <div class="py-2 px-4 w-full sm:w-1/2">
                    <dt class="text-lg">
                        {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
                        <a href="{{route('potions')}}" class="link pr-1">Potion</a>
                    </dt>
                    <dd>Add/Edit Potion</dd>
                </div>
            </dl>
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <div class="py-2 px-4 w-full sm:w-1/2">
                    <dt class="text-lg">
                        {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
                        <a href="{{route('armors')}}" class="link pr-1">Armor</a>
                    </dt>
                    <dd>Add/Edit Armor</dd>
                </div>
            </dl>
            <dl class="bg-white dark:bg-gray-800 shadow p-2 sm:mx-auto text-sm rounded-lg flex flex-wrap flex-auto">
                <div class="py-2 px-4 w-full sm:w-1/2">
                    <dt class="text-lg">
                        {{--Note the crappy hack below to capitalize NPC. This won't scale if we have many words that need custom replacements.--}}
                        <a href="{{route('weapon')}}" class="link pr-1">Weapon</a>
                    </dt>
                    <dd>Add/Edit Weapon</dd>
                </div>
            </dl>
        </div>
    </div>

</x-app-layout>
