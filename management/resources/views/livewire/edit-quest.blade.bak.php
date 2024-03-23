{{-- Editor dialog to make changes to the record (or make a new one).
     I don't feel that there's much value in extracting this to a template since almost everything could be different,
     except for the save and cancel buttons, maybe.
 --}}
<form wire:submit.prevent="save">
    <x-modal.dialog class="w-20" wire:model.defer="showModal">
        <x-slot name="title">
            @isset($editing->id)
                <div class="flex justify-between">
                    <span>Edit {{ camelToSpaced($objectClass) }} ID <strong>{{ $editing->id }}</strong></span>
                    <x-button wire:click="readyTestPlayer" title="Moves TEST PLAYER to Giver NPC and gives prerequisite quests and professions.">Ready Player 0 for Test</x-button>
                </div>
            @else New {{ camelToSpaced($objectClass) }}
            @endisset
        </x-slot>
        <x-slot name="content">
            <x-input.group for="name" label="Name" :error="$errors->first('editing.name')">
                <x-input wire:model="editing.name" id="name" type="text" />
            </x-input.group>

            <x-input.group for="level" label="Level" :error="$errors->first('editing.level')">
                <x-input wire:model="editing.level" id="level" type="text" />
            </x-input.group>


            <x-input.group for="repeatable" label="Repeatable" :error="$errors->first('editing.repeatable')">
                <input wire:model="editing.repeatable" id="repeatable" type="checkbox" />
            </x-input.group>

            @if($editing->repeatable)
            <x-input.group for="repeatable_cd_in_minute" label="Repeat Cooldown" :error="$errors->first('editing.repeatable_cd_in_minute')">
                <x-input wire:model="editing.repeatable_cd_in_minute" id="repeatable_cd_in_minute" type="text" />
                <div class="text-xs mt-1">Minutes before a player can repeat a quest after successful completion.</div>
            </x-input.group>
            @endif

            {{-- pickup_limitation could be filled automatically or we can probably get rid of it and determine this through
            the quest_prerequisite_professions DB relationship --}}
            <x-input.group label="Giver NPC">
                <div class="flex space-x-2 my-2">
                    <livewire:item-picker objectClass="Npc" :selectedId="$editing->giver_npc_id" field="giver_npc_id" :key="'GiverNpc'.$editing->id" />
                    <input tabindex="-1" type="hidden" wire:model="editing.giver_npc_id">
                </div>
                @if($errors->first('editing.giver_npc_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('editing.giver_npc_id')}}</div>@endif
            </x-input.group>

            <x-input.group for="npc_has_quest_item" label="NPC Gives Quest Items" :error="$errors->first('editing.npc_has_quest_item')">
                <input wire:model="editing.npc_has_quest_item" id="npc_has_quest_item" type="checkbox" />
                <div class="text-xs mt-1">Upon accepting the quest, the player will receive any items or tools the NPC has for this quest.</div>
            </x-input.group>

            @isset($editing->id)
                <x-input.group for="profession_id" label="Required Professions">
                    <div class="flex space-x-2">
                        <select wire:model="profession_id" id="profession_id" class="flex-1 min-w-0">
                            <option></option>
                            @php
                                $existingProfs = QuestPrerequisiteProfession::where('quest_id', $editing->id)
                                    ->pluck('profession_id')->toArray();
                            @endphp
                            @foreach(Profession::whereNotIn('id', $existingProfs)->get() as $profession)
                                <option value="{{$profession->id}}">{{$profession->name}}</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addProfession">Add</x-button>
                    </div>
                    @if($errors->first('profession_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('profession_id')}}</div>@endif
                    <ul>
                        @foreach($editing->prerequisiteProfessions as $prerequisiteProfession)
                            <li wire:key="prerequisiteProfessionId{{$prerequisiteProfession->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$prerequisiteProfession->id}}', name:'{{addSlashes($prerequisiteProfession->profession->name)}}', className:'QuestPrerequisiteProfession' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{ $prerequisiteProfession->profession->name }}
                            </li>
                        @endforeach
                    </ul>
                    <div class="text-xs mt-1">Players require all the above professions before being offered this quest.</div>
                </x-input.group>

                <x-input.group for="prerequisite_quest_id" label="Required Quests">
                    <div class="flex space-x-2">
                        <select wire:model="prerequisite_quest_id" id="prerequisite_quest_id" class="flex-1 min-w-0">
                            <option></option>
                            @php
                                $existingQuests = QuestPrerequisiteQuest::where('quest_id', $editing->id)
                                    ->pluck('prerequisite_quest_id')->toArray();
                            @endphp
                            @foreach(Quest::whereNotIn('id', $existingQuests)->get() as $quest)
                                <option value="{{$quest->id}}">{{$quest->name}} ({{$quest->id}})</option>
                            @endforeach
                        </select>
                        <x-button type="button" class="ml-2" wire:click="addQuest">Add</x-button>
                    </div>
                    @if($errors->first('prerequisite_quest_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('prerequisite_quest_id')}}</div>@endif
                    <ul>
                        @foreach($editing->prerequisiteQuests as $prerequisiteQuest)
                            <li wire:key="prerequisiteQuestId{{$prerequisiteQuest->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$prerequisiteQuest->id}}', name:'{{addSlashes($prerequisiteQuest->prerequisiteQuest->name)}}', className:'QuestPrerequisiteQuest' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{ $prerequisiteQuest->prerequisiteQuest->name }}
                            </li>
                        @endforeach
                    </ul>
                    <div class="text-xs mt-1">Players must complete all the above quests before being offered this quest.</div>
                </x-input.group>

                {{-- Items that an NPC will offer if the player has this quest --}}
                <x-input.group for="npc_item_id" label="NPC Items">
                    <div class="flex space-y-2 my-2 flex-wrap">
                        <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$npc_item_id" field="npc_item_id" :key="'NpcItemItem'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="npc_item_id">

                        <livewire:item-picker class="flex-1" objectClass="Npc" :selectedId="$npc_item_npc_id" field="npc_item_npc_id" :key="'NpcItemNpc'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="npc_item_npc_id">
                        <x-button type="button" class="flex-1" wire:click="addNpcItem">Add NPC Item</x-button>
                    </div>

                    @if($errors->first('npc_item_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('npc_item_id')}}</div>@endif
                    @if($errors->first('npc_item_npc_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('npc_item_npc_id')}}</div>@endif

                    <ul class="space-y-2">
                        @foreach($editing->npcItems as $npcItem)
                            <li class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$npcItem->id}}', name:'{{addSlashes($npcItem->item->name)}}', className:'NpcItem' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                @isset($npcItem->item->itemIcon)<img class="mx-2" width="50" src="{{ $npcItem->item->itemIcon->tnUrl }}">@endisset
                                <div>
                                    <div><a class="link" href="{{route('items')}}/?filters%5Bid%5D={{$npcItem->item->id}}">{{ $npcItem->item->name }}</a></div>
                                    @isset($npcItem->npc_id)<div class="text-sm">NPC <span class="text-xs text-gray-500">{{$npcItem->npc_id}}</span> <a href="{{route('npcs')}}?filters%5Bid%5D={{$npcItem->npc_id}}" class="link">{{ $npcItem->npc->name }}</a></div>@endisset
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="text-xs mt-1">Items that can be obtained from NPCs if the player has this quest.</div>
                </x-input.group>

                {{-- Quest Tools --}}
                <x-input.group for="quest_tool_id" label="Quest Tools">
                    <div class="flex space-y-2 my-2 flex-wrap">
                        <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$quest_tool_id" field="quest_tool_id" :key="'QuestTool'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="quest_tool_id">
                        {{-- I don't see why you'd ever want to change the quest amount from 1, so I'll hide this and use the default (1) --}}
                        {{--<x-input.prefix outerClasses="flex-1" wire:model="quest_tool_amount" type="text" class="w-full">Amount</x-input.prefix>--}}
                        <x-button type="button" class="flex-1" wire:click="addQuestTool">Add</x-button>
                    </div>

                    @if($errors->first('quest_tool_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('quest_tool_id')}}</div>@endif
                    @if($errors->first('quest_tool_amount'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('quest_tool_amount')}}</div>@endif

                    <ul class="space-y-2">
                        @foreach($editing->questTools as $questTool)
                            <li class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$questTool->id}}', name:'{{addSlashes($questTool->item->name)}}', className:'QuestTool' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                @isset($questTool->item->itemIcon)<img class="mx-2" width="50" src="{{ $questTool->item->itemIcon->tnUrl }}">@endisset
                                <div class="flex-1">
                                    <a class="mr-2 text-green-500 hover:text-green-700 dark:hover:text-green-300" target="_blank" href="{{route('quest-tools')}}?filters%5Bid%5D={{$questTool->id}}"><i class="fas fa-pencil-alt"></i></a>
                                    <a class="link" href="{{route('items')}}/?filters%5Bid%5D={{$questTool->item->id}}">{{ $questTool->item->name }}</a> {{--<span class="ml-2 text-sm">Amt: {{ $questTool->item_amount }}</span>--}}
                                    <div class="text-xs flex-1">{{ Str::words($questTool->item->description, 20) }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="text-xs mt-1">Quest tools may be used to find other items or complete quests.
                        <br>Click the <strong><i class="fas fa-pencil-alt"></i> pencil icons</strong> to edit tools and add locations where items may be found.
                    </div>
                </x-input.group>

                <x-input.group label="Target NPC">
                    <div class="flex space-x-2 my-2">
                        <livewire:item-picker objectClass="Npc" :selectedId="$editing->target_npc_id" field="target_npc_id" :key="'TargetNpc'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="editing.target_npc_id">
                    </div>
                    @if($errors->first('editing.target_npc_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('editing.target_npc_id')}}</div>@endif
                    <div class="text-xs mt-1">The player checks in with this NPC to complete the quest. If none is specified, it defaults to the giver NPC.</div>
                </x-input.group>

                <x-input.group for="prologue" label="Prologue" :error="$errors->first('editing.prologue')">
                    <textarea wire:model="editing.prologue" id="prologue" class="w-full h-32 text-sm"></textarea>
                    <div class="text-xs mt-1">A brief description of the quest shown before the player accepts it.</div>
                </x-input.group>

                <x-input.group for="content" label="Content" :error="$errors->first('editing.content')">
                    <textarea wire:model.lazy="editing.content" id="content" class="w-full h-32 text-sm" wire:change="refreshPreview"></textarea>
                    <div class="text-xs mt-1">Full details quest shown after the player accepts it. May include an explanation of questions players will answer.</div>
                    <ul class="text-xs mt-2">
                        <li><i class="fas fa-check-circle text-green-500"></i> Basic HTML is supported here. <strong>Ensure HTML is valid in the preview below or you may break the game!</strong></li>
                        @if(isset($editing->quest_result_type_id))
                            @if($editing->questResultType->uses_variables)
                                <li><i class="fas fa-check-circle text-green-500"></i> MathML & LaTeX is supported for formatted mathematical notation.</li>
                                <li wire:ignore><i class="fas fa-info-circle text-blue-500"></i> <strong>For LaTeX:</strong> use \<span>[</span>\sqrt5\<span>]</span> for blocks or \<span>(</span>\sqrt5\<span>)</span> for inline. eg: \(\sqrt5\)</li>
                                <li><i class="fas fa-check-circle text-green-500"></i> You may click to copy the variable placeholders below to insert the player's values in the above content.
                                    <ul class="mt-2">
                                        @foreach($editing->questVariables as $questVariable)
                                            <li wire:key="questVariableCont{{ $questVariable->id }}" class="flex items-center">
                                                {{-- Make this pulse when clicked and copy to the clipboard --}}
                                                <button type="button" class="ml-2 text-base cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50" onclick="copy(this)"><span class="opacity-50">@{{</span><strong>@php echo $questVariable->var_name; @endphp</strong><span class="opacity-50">}}</span></button> <span class="ml-2 opacity-80 text-sm">{{ round($questVariable->min_value) }} .. {{ round($questVariable->max_value) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @elseif($editing->quest_result_type_id == 7)
                                <li><i class="fas fa-check-circle text-green-500"></i> Cloze quests can have fields for answers inserted into quest content. Click an answer name to copy the placeholder text to the clipboard.
                                    <ul class="mt-2">
                                        @foreach($editing->questAnswers as $questAnswer)
                                            <li wire:key="questAnswerCont{{ $questAnswer->id }}" class="flex items-center">
                                                {{-- Make this pulse when clicked and copy to the clipboard --}}
                                                <button type="button" class="ml-2 text-base cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50" onclick="copy(this)"><span class="opacity-50">@{{</span><strong>@php echo $questAnswer->name; @endphp</strong><span class="opacity-50">}}</span></button> <span class="ml-2 opacity-80 text-xs">{{ $questAnswer->answer_string }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endif
                    </ul>

                    @isset($editing->content)
                        {{-- I make the user manually refresh the preview
                            - To avoid breaking the page with bad HTML if they are in the middle of an edit
                            - To avoid MathJax constantly flashing and re-typesetting MathML/LaTeX and making the page bounce around
                        --}}
                        <button class="btn block m-auto mt-3 mb-2 flex items-center" type="button" wire:click="refreshPreview">
                            Refresh Preview
                        </button>
                        {{-- Badly formatted HTML could break this page, so perhaps require clicking a button before showing this --}}
                        <div wire:key="item{{ $editing->id }}preview{{$previewCount}}" id="preview" class="managementContent p-2 shadow-inset-dark dark:bg-gray-900 rounded-md">
                            @if (htmlCheck($editing->content))
                                <div wire:ignore>
                                    {!!$editing->content!!}
                                </div>
                            @else
                                <div class="text-red-500">ERROR: Content has invalid HTML.</div>
                            @endif
                        </div>
                    @endisset
                </x-input.group>

                <x-input.group for="target_npc_prologue" label="Target NPC Prologue" :error="$errors->first('editing.target_npc_prologue')">
                    <textarea wire:model="editing.target_npc_prologue" id="target_npc_prologue" class="w-full h-32 text-sm"></textarea>
                    <div class="text-xs mt-1">Greeting shown by target before attempting to complete the quest.</div>
                </x-input.group>

                <x-input.group for="success_words" label="Success Response" :error="$errors->first('editing.success_words')">
                    <textarea wire:model="editing.success_words" id="success_words" class="w-full h-32 text-sm"></textarea>
                    <div class="text-xs mt-1">Response from NPC when player answers correctly.</div>
                </x-input.group>

                <x-input.group for="failure_words" label="Failure Response" :error="$errors->first('editing.failure_words')">
                    <textarea wire:model="editing.failure_words" id="failure_words" class="w-full h-32 text-sm"></textarea>
                    <div class="text-xs mt-1">Response from NPC when player answers incorrectly.</div>
                </x-input.group>

                <x-input.group for="cd_in_minute" label="Retry Cooldown" :error="$errors->first('editing.cd_in_minute')">
                    <x-input wire:model="editing.cd_in_minute" id="cd_in_minute" type="text" />
                    <div class="text-xs mt-1">Minutes before a player can retry completing a quest after a failure.</div>
                </x-input.group>

                <x-input.group for="reward_profession_xp" label="Reward Profession XP" :error="$errors->first('editing.reward_profession_xp')">
                    <x-input wire:model="editing.reward_profession_xp" id="reward_profession_xp" type="text" />
                    <div class="text-xs mt-1">The XP given towards the relevant player profession.</div>
                </x-input.group>

                <x-input.group for="reward_xp" label="Reward XP" :error="$errors->first('editing.reward_xp')">
                    <x-input wire:model="editing.reward_xp" id="reward_xp" type="text" />
                    <div class="text-xs mt-1">The general XP awarded to the player.</div>
                </x-input.group>

                <x-input.group for="reward_money" label="Reward Money" :error="$errors->first('editing.reward_money')">
                    <x-input.prefix wire:model="editing.reward_money" outerClasses="w-full" class="w-full" id="reward_money" type="text">$</x-input.prefix>
                    <div class="text-xs mt-1">MEGA$ awarded to the player.</div>
                </x-input.group>

                <x-input.group for="reward_item_id" label="Reward Items">
                    <div class="flex space-y-2 my-2 flex-wrap">
                        <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$reward_item_id" field="reward_item_id" :key="'RewardItem'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="reward_item_id">
                        <x-input.prefix outerClasses="flex-1" wire:model="reward_item_amount" type="text" class="w-full">Amount</x-input.prefix>
                        <x-button type="button" class="ml-2" wire:click="addRewardItem">Add</x-button>
                    </div>

                    @if($errors->first('reward_item_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('reward_item_id')}}</div>@endif
                    @if($errors->first('reward_item_amount'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('reward_item_amount')}}</div>@endif

                    <ul class="space-y-2">
                        @foreach($editing->rewardItems as $rewardItem)
                            <li class="flex items-center" wire:key="rewardItemId{{$rewardItem->id}}">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$rewardItem->id}}', name:'{{addSlashes($rewardItem->item->name)}}', className:'QuestRewardItem' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                @isset($rewardItem->item->itemIcon)<img class="mx-2" width="50" src="{{ $rewardItem->item->itemIcon->tnUrl }}">@endisset
                                <a class="link" href="{{route('items')}}/?filters%5Bid%5D={{$rewardItem->item->id}}">{{ $rewardItem->item->name }}</a> <span class="ml-2 text-sm">Amt: {{ $rewardItem->item_amount }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="text-xs mt-1">These items will be given to the player upon completing the quest.</div>
                </x-input.group>

                <h2 class="text-xl font-bold mt-4">Auto-marking</h2>
                <div class="text-sm mt-1">Only used in long text answer, speaking, and conversation quests.</div>

                <x-input.group for="waiting_words" label="Waiting message" :error="$errors->first('editing.waiting_words')">
                    <textarea wire:model="editing.waiting_words" id="waiting_words" class="w-full h-32 text-sm"></textarea>
                    <div class="text-xs mt-1">This message will be shown to the player until auto-marking is complete.</div>
                </x-input.group>

                <x-input.group for="base_reward_automark_percentage" label="Base Reward Percentage" :error="$errors->first('editing.base_reward_automark_percentage')">
                    <x-input wire:model="editing.base_reward_automark_percentage" id="base_reward_automark_percentage" type="text" />
                    <div class="text-xs mt-1">The minimum percentage score required to get the full rewards.<br></div>
                </x-input.group>

                @if($errors->first('editing.quest_result_type_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('editing.quest_result_type_id')}}</div>@endif
                <h2 class="text-xl font-bold mt-4">Correct Response Data</h2>
                <div class="text-xs mt-1 mb-2">
                    Each result type requires different interactions by the player to fulfil the requirements of the quest.
                </div>

                <x-input.group for="quest_result_type_id" label="Result Type" :error="$errors->first('editing.quest_result_type_id')">
                    <div class="text-xs mb-1"><strong>Warning:</strong> Changes to this will immediately save and take effect.</div>
                    <select wire:model="editing.quest_result_type_id" class="w-full" id="quest_result_type_id">
                        <option value="" @if(isset($editing->questResultType)) disabled @endif>Select a result type</option>
                        @foreach(QuestResultType::orderBy('id')->get() as $questResultType)
                            <option value="{{$questResultType->id}}">{{$questResultType->name}}</option>
                        @endforeach
                    </select>
                    <div wire:key="quest{{$editing->id}}questResultSpecs">
                    @if(isset($editing->questResultType))
                        @if($editing->questResultType->uses_string)
                            <div class="text-xs mt-1">Requires answer strings</div>
                        @endif
                        @if($editing->questResultType->uses_bool)
                            <div class="text-xs mt-1">Requires booleans</div>
                        @endif
                        @if($editing->quest_result_type_id == 5)
                            <div class="text-xs mt-1">Drag quest items into the correct order below.</div>
                        @endif
                        @if($editing->quest_result_type_id == 13)
                            <div class="text-xs mt-1">Specify X and Y coordinates below.
                                These coordinates may be specified using an equation with numbers randomly selected in a variable range.<br>
                                Refer to a variable in the X or Y fields with the notation<br><strong>@{{A}}</strong> for variable A, <strong>@{{B}}</strong> for variable B, etc.
                            </div>
                        @endif
                    @endif
                    </div>
                </x-input.group>

                @if(isset($editing->questResultType) && $editing->quest_result_type_id == 16)
                    <x-input.group label="Conversation URL">
                        <textarea class="w-full mb-2" wire:model.lazy="question" placeholder="https://conversation.megaworld.game-server.ca/task.php">
                            @php $this->question = $this->question ? $this->question : $editing->questAnswers()->get()[0]->question @endphp
                        </textarea>
                    </x-input.group>
                @elseif(isset($editing->questResultType) && $editing->questResultType->uses_answers)
                    @php $this->question = '' @endphp
                    <x-input.group label="Questions & Answers">
                        <div class="flex flex-wrap mb-6">
                            @if(isset($editing->questResultType) && $editing->questResultType->uses_questions)
                                <textarea class="w-full mb-2" wire:model.lazy="question" placeholder="Question"></textarea>
                            @endif
                            @if(isset($editing->questResultType) && $editing->questResultType->uses_bool)
                                <select class="w-full mb-2" wire:model="correct_bool">
                                    <option value="" disabled>Choose T/F</option>
                                    <option value="1">True/Selected</option>
                                    <option value="0">False/Unselected</option>
                                </select>
                            @endif
                            <x-input.prefix  class="w-16 mr-2" wire:model="answer_name" type="text" placeholder="A">Name</x-input.prefix>
                            <x-input.prefix  outerClasses="flex-1 mr-2" class="w-full" wire:model.lazy="answer_string" type="text">Value</x-input.prefix>
                            <x-button type="button" wire:click="addQuestAnswer">Add</x-button>
                        </div>
                        <hr class="my-6 opacity-40">
                        @if($errors->first('answer_name'))<div class="mt-1 text-red-500 text-sm">A unique answer name is required.</div>@endif
                        @if($errors->first('answer_string'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('var_value')}}</div>@endif
                        @if($editing->quest_result_type_id == 12)
                            <div class="text-xs mt-1">For answers, use placeholders in questions to represent a random number chosen from a specified range with format <strong>@{{a}}</strong>.<br><br>
                                The value field accepts EvalMath equations using the variable names above (without brackets), constants "<strong>pi</strong>" or "<strong>e</strong>" (Euler's constant) as well as a number of math functions:
                                <div class="font-bold">'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh', 'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh', 'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh', 'sqrt', 'abs', 'ln', 'log');</div>
                                eg: <span class="border text-sm rounded border-gray-500 px-1.5">a-8(5/2)^2*(1-sqrt(4))-8pi</span> will evaluate to about 25.867 if variable a==1.
                            </div>
                        @endif
                        <ul>
                            @foreach($editing->questAnswers()->get() as $questAnswer)
                                <li wire:key="questAnswer{{ $questAnswer->id }}" class="flex my-5 p-2 tems-center border border-gray-500 border-opacity-10 rounded-md bg-gray-500 bg-opacity-20 shadow">
                                    <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$questAnswer->id}}', name:'{{addSlashes($questAnswer->name)}}', className:'QuestAnswer' })">
                                        <i class="fas fa-times text-red-500"></i>
                                    </button>
                                    <div class="ml-2 flex-1 items-center">
                                        @if(isset($editing->questResultType) && $editing->questResultType->uses_questions)
                                            <textarea wire:key="questAnswer{{ $questAnswer->id }}question" class="w-full" placeholder="Question" x-on:change="$wire.setQuestAnswerQuestion({{$questAnswer->id}}, $event.target.value)">{{ $questAnswer->question }}</textarea>
                                            <div class="managementContent"><strong class="mr-1">Q {{$questAnswer->name}}:</strong>@if(htmlCheck($questAnswer->question)) {!! $questAnswer->question !!} @else <span class="text-red-500">ERROR: Invalid HTML.</span> @endif</div>
                                        @endif

                                        @if($editing->questResultType->uses_bool)
                                            <span wire:key="questAnswer{{ $questAnswer->id }}bool" class="ml-2">
                                            <label class="mr-2" for="questAnswer{{ $questAnswer->id }}boolT">T <input type="radio" wire:change="setQuestAnswerBool({{$questAnswer->id}}, 1)" name="questAnswer{{ $questAnswer->id }}bool" id="questAnswer{{ $questAnswer->id }}boolT" value="1" @if($questAnswer->correct_bool) checked @endif /></label>
                                            <label class="mr-2" for="questAnswer{{ $questAnswer->id }}boolF">F <input type="radio" wire:change="setQuestAnswerBool({{$questAnswer->id}}, 0)" name="questAnswer{{ $questAnswer->id }}bool" id="questAnswer{{ $questAnswer->id }}boolF" value="0" @if(!$questAnswer->correct_bool) checked @endif /></label>
                                        </span>
                                        @endif
                                        @if($editing->quest_result_type_id == 11)
                                            <textarea wire:key="questAnswer{{ $questAnswer->id }}bool" class="w-full" x-on:change="$wire.setQuestAnswer({{$questAnswer->id}}, $event.target.value)" placeholder="Answer {{$questAnswer->name}}">{{ $questAnswer->answer_string }}</textarea>
                                        @else
                                            <x-input.prefix outerClasses="w-full mt-1" class="w-full" type="text" x-on:change="$wire.setQuestAnswer({{$questAnswer->id}}, $event.target.value)" value="{{ $questAnswer->answer_string }}" placeholder="Answer">{{ $questAnswer->name }}</x-input.prefix>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-input.group>
                @endif

                {{--
                    Quest result types that use items require one or more of these.
                    Depending on the result type, different fields must be filled in here.
                --}}
                <x-input.group for="quest_item_id" label="Quest Items">
                    <div class="flex space-y-2 my-2 flex-wrap">
                        <livewire:item-picker class="flex-1" objectClass="Item" :selectedId="$quest_item_id" field="quest_item_id" :key="'QuestItem'.$editing->id" />
                        <input tabindex="-1" type="hidden" wire:model="quest_item_id">

                        @if(isset($editing->questResultType) && $editing->questResultType->uses_items && $editing->questResultType->uses_bool)
                        <select class="flex-1" wire:model="answer_bool">
                            <option value="" disabled>Choose T/F</option>
                            <option value="1">True/Selected</option>
                            <option value="0">False/Unselected</option>
                        </select>
                        @endif

                        @if(isset($editing->questResultType) && $editing->questResultType->uses_items && $editing->questResultType->uses_string)
                            <x-input.prefix outerClasses="flex-1 " wire:model.lazy="item_answer_string" type="text" class="w-full">Answer</x-input.prefix>
                        @endif

                        {{-- I'm not sure if this is really used for anything --}}
                        @if($errors->first('quest_item_amount'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('quest_item_amount')}}</div>@endif

                        {{-- Only show this if we don't use another kind of value for this item --}}
                        @if(isset($editing->quest_result_type_id) && $editing->questResultType->uses_bool == 0 && $editing->questResultType->uses_string == 0 )
                        <x-input.prefix outerClasses="flex-1" wire:model="quest_item_amount" type="text" class="w-full">Amount</x-input.prefix>
                        @endif

                        <x-button type="button" class="w-full" wire:click="addQuestItem">Add</x-button>
                    </div>

                    @if($errors->first('quest_item_id'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('quest_item_id')}}</div>@endif

                    {{-- If quest_result_type == 5 note that it is draggable. --}}
                    <ul wire:sortable="updateQuestItemOrder" class="space-y-2" x-data="">
                        @foreach($editing->questItems()->orderBy('answer_seq')->get() as $questItem)
                            <li @if($editing->quest_result_type_id == 5) draggable="true" wire:sortable.item="{{ $questItem->id }}" @endif wire:key="questItem{{ $questItem->id }}" class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$questItem->id}}', name:'{{addSlashes($questItem->item->name)}}', className:'QuestItem' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{-- Grabber for dragging into the correct order --}}
                                @if($editing->quest_result_type_id == 5)
                                    <i class="fas fa-align-justify ml-3 opacity-20"></i>
                                @endif

                                @isset($questItem->item->itemIcon)<img class="mx-2" width="50" src="{{ $questItem->item->itemIcon->tnUrl }}">@endisset
                                <div class="flex-1">
                                    @if($editing->questResultType->uses_bool)
                                        <span wire:key="questItem{{ $questItem->id }}bool">
                                            <label class="mr-2" for="questItem{{ $questItem->id }}boolT">T <input type="radio" wire:change="setQuestItemBool({{$questItem->id}}, 1)" name="questItem{{ $questItem->id }}bool" id="questItem{{ $questItem->id }}boolT" value="1" @if($questItem->answer_bool) checked @endif /></label>
                                            <label class="mr-2" for="questItem{{ $questItem->id }}boolF">F <input type="radio" wire:change="setQuestItemBool({{$questItem->id}}, 0)" name="questItem{{ $questItem->id }}bool" id="questItem{{ $questItem->id }}boolF" value="0" @if(!$questItem->answer_bool) checked @endif /></label>
                                        </span>
                                    @elseif(isset($editing->questResultType) && $editing->questResultType->uses_string)
                                        {{-- Could add additional formatting/style for items that use answer strings here.--}}
                                    @else
                                        <x-input.prefix outerClasses="mr-2 text-sm w-16" class="w-full p-0 text-center" type="text" inputmode="numeric"  x-on:change="$wire.setQuestItemAmount({{$questItem->id}}, $event.target.value)" value="{{ $questItem->item_amount }}">Amt</x-input.prefix>
                                    @endif
                                    <a class="link" href="{{route('items')}}/?filters%5Bid%5D={{$questItem->item->id}}">{{ $questItem->item->name }}</a>
                                    <div class="text-xs flex-1">{{ $questItem->item->description }}</div>
                                    @if(isset($editing->questResultType) && $editing->questResultType->uses_string)
                                        <x-input.prefix outerClasses="mr-2 text-sm w-full" class="w-full py-0 px-1 text-sm" type="text" x-on:change="$wire.setQuestItemString({{$questItem->id}}, $event.target.value)" value="{{ $questItem->answer_string }}">Answer</x-input.prefix>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="text-xs mt-1">These are required in order to complete the quest.<br>They must be acquired or given by the giver NPC.</div>
                    @if(isset($editing->questResultType) && $editing->questResultType->uses_items && $editing->questResultType->uses_bool)
                        <div class="text-xs mt-2">This quest needs a boolean value associated with each item (e.g. true if selected).</div>
                    @endif
                </x-input.group>

                @if(isset($editing->questResultType) && $editing->questResultType->uses_variables)
                    <x-input.group label="Quest Variables">
                        <div class="flex space-x-2">
                            <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="var_name" type="text" placeholder="a">Name</x-input.prefix>
                            <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="min_value" type="text">Min</x-input.prefix>
                            <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="max_value" type="text">Max</x-input.prefix>
                            <x-button type="button" wire:click="addQuestVariable">Add</x-button>
                        </div>
                        @if($errors->first('var_name'))<div class="mt-1 text-red-500 text-sm">A unique variable name is required.</div>@endif
                        @if($errors->first('min_value'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('min_value')}}</div>@endif
                        @if($errors->first('max_value'))<div class="mt-1 text-red-500 text-sm">{{$errors->first('max_value')}}</div>@endif
                        <div class="text-xs mt-1">Quests using numeric answers may specify a range of numbers from which a value will be randomly selected each time a quest is accepted.</div>
                        <div class="text-xs mt-2">Click a variable to copy the variable to the clipboard.</div>
                        <ul>
                        @foreach($editing->questVariables as $questVariable)
                            <li wire:key="questVariable{{ $questVariable->id }}" class="flex items-center">
                                <button type="button" x-on:click="$dispatch('confirm-action', { id:'{{$questVariable->id}}', name:'{{addSlashes($questVariable->var_name)}}', className:'QuestVariable' })">
                                    <i class="fas fa-times text-red-500"></i>
                                </button>
                                {{-- Make this pulse when clicked --}}
                                <button type="button" class="ml-2 cursor-default rounded-md hover:text-cyan-700 dark:hover:text-cyan-300 border border-transparent focus:border-cyan-300 transition ease-in-out duration-150 focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50" onclick="copy(this)"><span class="opacity-50"></span><strong>@php echo $questVariable->var_name; @endphp</strong><span class="opacity-50"></span></button> <span class="ml-2 text-sm opacity-80">{{ round($questVariable->min_value) }} .. {{ round($questVariable->max_value) }}</span>
                            </li>
                        @endforeach
                        </ul>
                    </x-input.group>
                @endif

                {{-- Only applies to quests using result_x, result_y, and result_map_id --}}
                @if($editing->quest_result_type_id == 13)
                    <x-input.group for="result_map_id" label="Result Location">
                        <select wire:model="editing.result_map_id" class="w-full" id="result_map_id">
                            <option value="">Select a map</option>
                            @foreach(Map::orderBy('id')->get() as $map)
                                <option value="{{$map->id}}">{{$map->name}} ({{$map->id}})</option>
                            @endforeach
                        </select>
                        <div class="flex space-x-2 mt-2">
                            <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="editing.result_x" type="text">X</x-input.prefix>
                            <x-input.prefix outerClasses="flex-1" class="w-full" wire:model="editing.result_y" type="text">Y</x-input.prefix>
                        </div>
                        <div class="text-xs mt-1">This quest can be completed when the player is at this location.</div>
                        <ul class="text-xs mt-2">
                            <li><i class="fas fa-check-circle text-green-500"></i> X and Y coordinates may be specified with EvalMath equations that use variables (results will be rounded to nearest integer).</li>
                            <li>e.g. <span class="border text-sm rounded border-gray-500 px-1.5">a-8(5/2)^2*(1-sqrt(4))-8pi</span> </li>
                        </ul>
                    </x-input.group>
                @endif

            @else {{-- If this is a new item, show this message here --}}
                <div class="mt-3 text-center text-lg">
                    Save to continue editing.
                </div>
            @endisset
            {{-- Used for result types
                5 - Text answers (although this only seems to support one answer..)
                Technically 15, although this should just be refactored to use a variable, I think
                13 needs to be refactored to use the map_id, X, Y fields
                14 needs to be refactored to use the quest_tools_locations table as well
                18 does use this, but since multiple are possible, this should use the quest_variables field.
            --}}
            @isset($editing->result)
            <x-input.group for="result" label="Legacy Result" :error="$errors->first('editing.result')">
                <textarea {{--wire:model="editing.result"--}} disabled id="result" class="w-full text-sm" rows="5">{{ $editing->result }}</textarea>
                <div class="text-xs mt-1">This is no longer used but may contain answers for old MWv2 quests.</div>
            </x-input.group>
            @endisset

            {{-- This field has a cryptic format, but was intended to be editable by humans.
                Here are the instructions for this field from the original editor:

                For True/False (question is entered in quest content instead of using quest items) type, you may simply type "true" or "false" here.
                For Single Choice (question and its options are entered in quest content instead of using quest items) type, you may simply type "1", "2", "3" etc. here.
                For Cloze type, a quset content may look like as following:

                There's the recipe: Recipe:
                public static void {{A}} (String[] args) {
                {{B}} dist = new Distance( intValue(args[0]), intValue(args[1]), intValue(args[2]), intValue(args[3])); {{C}}.printDistance();
                }

                For the quest content, the quest result should be: A#$#main$#$B#$#Distance$#$C#$#dist

                As you can see, the answer of "A" is main, you need to type "#$#" between A and A's answer such as A#$#main. However, if you want to make another answer such like B. You need to type $#$ to connect A and B such as A#$#main$#$B#$#Distance.
                For Text type, you may simply put any text (i.e., the key you want the NPC to use for marking players' answers) here.
                For Multiple answers (question involves several short-answer questions are entered in quest content instead of using quest items) type, please use ;;; to separate two text answers (the keys you want the NPC to use for marking players' answers).
                For Coordinates type, you may simply put the coordinates that your quest requires the players to go to. For instance, (3, 15) of Canada, Edmonton, you may put "3$#$15$#$Canada$#$Edmonton" here. On the other hand, if your quest uses a random variable to generate random coordinates for players, you may put "({{a}}^/3)+2$#$(^{{b}}^+3)-2$#$Canada$#$Edmonton" instead. Please note that the variable symbols you put here must be consistent with the ones you entered in the quest content or quest items.
                For Calculation type, you may simply put your answers of calculations here. For instance, if your quest's calculation's equation is X = 20+3 and Y=30+4, the quest result is "23.34", the you should simply put "X=23,Y=34" here. On the other hand, if your quest uses a random variable to generate calculation question for the players, you may put "X=(^##a##^-3)/5,Y=(^##b##^*3)+1" for simple math calculation here. Please note that the variable symbols you put here must be consistent with the ones you entered in the quest content or quest items.
                For Multiple Calculation (question involves several calculation results are entered in quest content instead of using quest items) type, please use , to separate two calculation results or two calculation equations. For instance, if your quest has more than one calculation results, please enter "X=23,Y=34" or "X=23,Y=34,Z=(^##a##^-3)/5" or "X=(^##a##^-3)/5,Y=Y=(^##b##^*3)+1".
            --}}

        </x-slot>
        <x-slot name="footer">
            <x-button class="highlight" type="submit">Save</x-button>
            <x-button @click="show = false;">Cancel</x-button>
        </x-slot>
    </x-modal.dialog>

</form>
