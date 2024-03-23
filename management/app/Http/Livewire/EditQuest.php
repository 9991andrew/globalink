<?php

namespace App\Http\Livewire;
use App\Models\Item;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\Npc;
use App\Models\Player;
use App\Models\PlayerQuestsLog;
use App\Models\Profession;
use App\Models\Quest;
use App\Models\QuestItem;
use App\Models\NpcItem;
use App\Models\QuestPrerequisiteProfession;
use App\Models\QuestPrerequisiteQuest;
use App\Models\QuestResultType;
use App\Models\QuestRewardItem;
use App\Models\QuestTool;
use App\Models\QuestAnswer;
use App\Models\QuestVariable;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EditQuest extends EditObject
{
    public $objectClass = 'Quest';
    public $quest_id;
    public $profession_id;
    public $prerequisite_quest_id;
    public $quest_item_id;
    public $quest_item_amount = 1;
    public $quest_tool_id;
    public $quest_tool_amount = 1;
    public $answer_bool=1;
    public $item_answer_string;
    public $reward_item_id;
    public $reward_item_amount = 1;
    public $npc_item_id;
    public $npc_item_npc_id;
    // Used for quest_answer
    public $answer_name='A';
    public $question='';
    public $correct_bool=0;
    public $answer_string;
    public $var_name='a';
    public $min_value;
    public $max_value;
    public $previewCount = 0;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:200'],
            'editing.repeatable' => ['boolean'],
            'editing.repeatable_cd_in_minute' => ['required', 'numeric', 'integer'],
            'editing.level' => ['required', 'numeric', 'integer'],
            'editing.giver_npc_id' => ['required', 'in:'.Npc::pluck('id')->implode(',')],
            'editing.target_npc_id' => ['nullable', 'in:'.Npc::pluck('id')->implode(',')],
            'editing.prologue' => ['nullable'],
            'editing.content' => ['nullable'],
            'editing.target_npc_prologue' => ['nullable'],
            'editing.npc_has_quest_item' => ['boolean'],
            'editing.quest_result_type_id' => ['required', 'in:'.QuestResultType::pluck('id')->implode(',')],
            'editing.success_words' => ['nullable'],
            'editing.failure_words' => ['nullable'],
            'editing.waiting_words' => ['nullable'],
            'editing.cd_in_minute' => ['required', 'numeric', 'integer'],
            // 'editing.result' => ['nullable'],
            'editing.result_map_id' => ['nullable', 'in:'.Map::pluck('id')->implode(',')],
            'editing.result_x' => ['nullable'],
            'editing.result_y' => ['nullable'],
            'editing.base_reward_automark_percentage' => ['required', 'numeric', 'integer'],
            'editing.reward_profession_xp' => ['required', 'numeric', 'integer'],
            'editing.reward_xp' => ['required', 'numeric', 'integer'],
            'editing.reward_money' => ['required', 'numeric', 'integer'],
        ];
    }

    /**
     * Specify defaults for a new quest
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'repeatable' => 0,
            'repeatable_cd_in_minute' => 0,
            'level' => 0,
            'npc_has_quest_item' => 0,
            'base_reward_automark_percentage' => 0,
            'cd_in_minute' => 0,
            'quest_result_type_id' => 1, // Default to the most basic type of quest.
            'reward_profession_xp' => 0,
            'reward_xp' => 0,
            'reward_money' => 0,
        ]);
    }

    public function refreshPreview()
    {
        $this->previewCount++;
    }

    /**
     * Moves the specified player (defaults to TEST PLAYER id 0) to the map tile
     * of the giver NPC and gives the player the prerequisite quests, professions, and level.
     *
     * This makes it easy to test the quest without having to manually position a player or
     * play the game extensively before seeing what the quest looks like.
     * @param int $playerId
     */
    public function readyTestPlayer($playerId=0) {
        $player = Player::find($playerId);
        foreach($this->editing->prerequisiteProfessions as $questPrerequisiteProfession) {
            $profession = $questPrerequisiteProfession->profession;
            if (! $player->professions->contains('id', $profession->id)) {
                // Give the player the profession at the level of this quest.
                $player->professions()->attach($profession->id, ['profession_xp' => 0, 'profession_level' => $this->editing->level]);
            } else {
                // Otherwise set the level of the player's profession to the level of this quest
                $player->professions()->updateExistingPivot($profession->id, ['profession_level'=>$this->editing->level]);
            }
        }

        foreach($this->editing->prerequisiteQuests as $questPrerequisiteQuest) {
            $quest = $questPrerequisiteQuest->prerequisiteQuest;
            if (! $player->quests->contains('id', $quest->id)) {
                // Must have completed this quest
                $player->quests()->attach($quest->id, ['pickup_time' => Carbon::now(), 'success_time' => Carbon::now()]);
                // If necessary we can add a records of quest success into the logs, as the game looks for this
                $pql = PlayerQuestsLog::create([
                    'player_id' => $playerId,
                    'quest_id' => $quest->id,
                    'event_datetime' => Carbon::now(),
                    'quest_event_id' => 1, // Success
                    'npc_id' => null, // Could be the giver NPC but technically quest wasn't from them
                    'map_id' => $player->map_id,
                    'x' => $player->x,
                    'y' => $player->y,
                    'ratio' => 0,
                    'quest_result' => 0,
                ]);
            }
        } // end foreach prerequisiteQuests

        if (isset($this->editing->giver_npc_id)) {
            $npc = Npc::find($this->editing->giver_npc_id);
            $player->map_id = $npc->map_id;

            // Put player at the bottom left of the target NPC's region, unless that region
            // is one where the player can't move to because of skill requirements
            for ($x = min($npc->x_left, $npc->x_right); $x <= max($npc->x_right, $npc->x_left); $x++) {
                for ($y = min($npc->y_top, $npc->y_bottom); $y <= max($npc->y_bottom, $npc->y_top); $y++) {
                    $mapTile = MapTile::where('map_id', $npc->map_id)->where('x', $x)->where('y', $y)->first();
                    $skillId = $mapTile->mapTileType->skill_id_req;
                    if (is_null($skillId)) {
                        $player->x = $x;
                        $player->y = $y;
                        break 2;
                    } elseif ($player->skills->contains('id', $skillId)) {
                        // Move the player here if they have the skill to move here
                        $player->x = $x;
                        $player->y = $y;
                        break 2;
                    }
                }
            }// end outer for loop
        }// end if isset giver_npc_id
        $player->save();

    }

    public function updatePickerItem($selectedId, $field)
    {
        if ($field == 'giver_npc_id') {
            $this->editing->giver_npc_id = $selectedId;
        } else if ($field == 'target_npc_id') {
            $this->editing->target_npc_id = $selectedId;
        } else if ($field == 'reward_item_id') {
            $this->reward_item_id = $selectedId;
        } else if ($field == 'quest_item_id') {
            $this->quest_item_id = $selectedId;
        } else if ($field == 'quest_tool_id') {
                $this->quest_tool_id = $selectedId;
        } else if ($field == 'npc_item_id') {
            $this->npc_item_id = $selectedId;
        } else if ($field == 'npc_item_npc_id') {
            $this->npc_item_npc_id = $selectedId;
        }
        // For some reason changing a picker can mess up the result type.
        // This should be a hack that fixes that.
        // $this->editing->questResultType=QuestResultType::find($this->editing->quest_result_type_id);
    }

    /**
     * This function simply sets the next answer name in the sequence
     */
    protected function setAnswerName() {
        $lastAnswer = QuestAnswer::where('quest_id', $this->editing->id)->orderBy('name', 'desc')->first();
        // Increment $lastName
        if (is_null($lastAnswer)) $this->answer_name = 'A';
        else $this->answer_name = ++$lastAnswer->name;
    }

    /**
     * This function simply sets the next variable name in the sequence
     */
    protected function setVariableName() {
        $lastVar = QuestVariable::where('quest_id', $this->editing->id)->orderBy('var_name', 'desc')->first();

        // Increment $lastVarName
        if (is_null($lastVar)) $this->var_name = 'a';
        else $this->var_name = ++$lastVar->var_name;
    }

    /**
     * This method is overridden so that I can set the next variable name for the variables editor.
     * @param $objectId
     */
    public function setEditing($objectId)
    {
        // A bit less elegant when you don't know in advance what the class will be
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
            $this->setAnswerName();
            $this->setVariableName();
        } else {
            $this->editing = $this->makeBlankObject();
            $this->answer_name = 'A';
            $this->var_name = 'a';
        }

        $this->question = '';
        $this->showModal = true;
    }


    /**
     * I'm having problems when you try to change the result type as fiddling with anything after that
     * causes it to get reset. There's probably something I'm missing, but this is an attempt to
     * work around that bug.
     */
    public function updatedEditing()
    {
        // This might be a bit inefficient, it has to run on any update to any property.
        // I only care about doing this when quest_result_type_id is updated
        if (isset($this->editing->questResultType) && $this->editing->quest_result_type_id != $this->editing->questResultType->id) {
            // All I can think to do is just save the result_type_id, but that sucks if you're screwing around and meant to cancel.
            if (isset($this->editing->id)) {
                $q = Quest::find($this->editing->id);
                $q->quest_result_type_id = $this->editing->quest_result_type_id;
                $q->save();

                // If result_type_id is 5 (drag and drop) ensure all the items have answer_seq
                if ($this->editing->quest_result_type_id == 5)
                {
                    $count = QuestItem::where('quest_id', $this->quest_id)->max('answer_seq');
                    if (is_null($count)) {$count = 1;}
                    foreach($this->editing->questItems as $questItem) {
                        if (is_null($questItem->answer_seq)) {
                            $questItem->answer_seq = $count++;
                            $questItem->save();
                        }
                    }
                }

            }
            $this->editing->load('questResultType');
        }
    }

    /**
     * On any updates to content, run the JS that typesets MathJax.
     * I'm worried this might slow down the client, but this ensures that MathJax is always rendering when the user updates content.
     */
    public function dehydrate()
    {
        // Only do MathJax typesetting if the quest uses variables (ie it's a calculation type).
        if ($this->editing->questResultType->uses_variables) {
            $this->emit('typesetMathJax');
        }
    }

    public function professionRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingProfs = QuestPrerequisiteProfession::where('quest_id', $this->editing->id)
                ->pluck('profession_id')->toArray();
        else
            $existingProfs = [];
        return [
            'quest_id' => ['required', 'numeric'],
            'profession_id' => ['required', 'in:'.Profession::whereNotIn('id', $existingProfs)->pluck('id')->implode(',')],
        ];
    }

    public function addProfession()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->professionRules());

        $prerequisiteProfession = QuestPrerequisiteProfession::create($validatedData);
        $prerequisiteProfession->quest_id = $this->editing->id;
        $prerequisiteProfession->save();

        $this->profession_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function prerequisiteQuestRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingQuests = QuestPrerequisiteQuest::where('quest_id', $this->editing->id)
                ->pluck('prerequisite_quest_id')->toArray();
        else
            $existingQuests = [];
        return [
            'quest_id' => ['required', 'numeric'],
            'prerequisite_quest_id' => ['required', 'in:'.Quest::whereNotIn('id', $existingQuests)->pluck('id')->implode(',')],
        ];
    }

    public function addQuest()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->prerequisiteQuestRules());

        $prerequisiteQuest = QuestPrerequisiteQuest::create($validatedData);
        $prerequisiteQuest->quest_id = $this->editing->id;
        $prerequisiteQuest->save();

        $this->prerequisite_quest_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function questItemRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingItems = QuestItem::where('quest_id', $this->editing->id)
                ->pluck('item_id')->toArray();
        else
            $existingItems = [];
        return [
            'quest_id' => ['required', 'numeric'],
            'quest_item_amount' => ['required', 'numeric', 'integer'],
            'quest_item_id' => ['required', 'in:'.Item::whereNotIn('id', $existingItems)->pluck('id')->implode(',')],
            'answer_bool' => ['required', 'boolean'],
            'item_answer_string' => ['nullable'],
        ];
    }

    public function addQuestItem()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->questItemRules());
        // I'm not sure if this is the tidiest way to do this...
        $validatedData['quest_id'] = $this->editing->id;
        $validatedData['item_id'] = $this->quest_item_id;
        $validatedData['item_amount'] = $this->quest_item_amount;
        $validatedData['answer_string'] = $this->item_answer_string;
        $validatedData['answer_seq'] = 1;
        $maxId = QuestItem::where('quest_id', $this->quest_id)->max('answer_seq');
        if (isset($maxId)) $validatedData['answer_seq'] = ($maxId+1);
        // This appears redundant but sometimes I was getting a truncation error,
        // so I'm trying casting to an int.
        $validatedData['answer_bool'] = (int)$this->answer_bool;
        //dd($validatedData);

        $questItem = QuestItem::create($validatedData);

        $questItem->save();

        $this->quest_item_id = null;
        $this->answer_bool = 1;
        $this->answer_string = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function questAnswerRules(): array
    {
        // Make sure we don't allow these names to be selected
        if (isset($this->quest_id))
            $existingNames = QuestAnswer::where('quest_id', $this->editing->id)
                ->pluck('name')->toArray();
        else
            $existingNames = [];

        return [
            'quest_id' => ['required', 'numeric'],
            'answer_name' => ['required', Rule::notIn($existingNames)], // Should be unique within the quest
            'answer_string' => ['nullable'], // Doesn't matter if this is blank
            'correct_bool' => ['boolean'],
            'question' => ['nullable'],
        ];
    }

    public function addQuestAnswer()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->questAnswerRules());
        $validatedData['name'] = $this->answer_name;
        // So dumb that I have to do this
        $validatedData['correct_bool'] = (int)$this->correct_bool;
        $questAnswer = QuestAnswer::create($validatedData);

        $questAnswer->save();

        $this->setAnswerName();
        $this->answer_string = null;
        $this->question = '';
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function save()
    {
        $this->validate();

        // Conversation type quests store the URL as a question
        if ($this->editing->quest_result_type_id == 16) {
            $attributes = [
                'quest_id' => $this->editing->id,
                'name' => 'A',
            ];
            $values = [
                'question' => $this->question,
                'answer_string' => '',
                'correct_bool' => (int)$this->correct_bool
            ];
            QuestAnswer::updateOrCreate($attributes, $values);
            $this->answer_string = null;
            $this->question = '';
        }

        $this->editing->save();
        $this->showModal = false;
        $this->emit('rerenderParent');
        // Ensure the object is blank - if this isn't done
        // and this object is deleted, it will break the editor and show 404
        $this->cleanup();
    }

    public function questVariableRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingNames = QuestVariable::where('quest_id', $this->editing->id)
                ->pluck('var_name')->toArray();
        else
            $existingNames = [];

        return [
            'quest_id' => ['required', 'numeric'],
            'var_name' => ['required', Rule::notIn($existingNames)], // Should be unique within the quest
            'min_value' => ['numeric'],
            'max_value' => ['numeric'],
        ];
    }

    public function addQuestVariable()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->questVariableRules());
        $questVariable = QuestVariable::create($validatedData);

        $questVariable->save();

        $this->setVariableName();
        $this->min_value = null;
        $this->max_value = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function questToolRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingItems = QuestTool::where('quest_id', $this->editing->id)
                ->pluck('item_id')->toArray();
        else
            $existingItems = [];
        return [
            'quest_id' => ['required', 'numeric'],
            'quest_tool_amount' => ['required', 'numeric', 'integer'],
            'quest_tool_id' => ['required', 'in:'.Item::whereNotIn('id', $existingItems)->pluck('id')->implode(',')],
        ];
    }

    public function addQuestTool()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->questToolRules());
        // I'm not sure if this is the tidiest way to do this...
        $validatedData['quest_id'] = $this->editing->id;
        $validatedData['item_id'] = $this->quest_tool_id;
        $validatedData['item_amount'] = $this->quest_tool_amount;

        $questTool = QuestTool::create($validatedData);

        $questTool->save();

        $this->quest_item_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function npcItemRules(): array
    {
        return [
            'quest_id' => ['required', 'numeric'],
            'npc_item_npc_id' => ['required', 'in:'.Npc::pluck('id')->implode(',')],
            'npc_item_id' => ['required', 'in:'.Item::pluck('id')->implode(',')],
        ];
    }

    public function addNpcItem()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->npcItemRules());
        // I'm not sure if this is the tidiest way to do this...
        $validatedData['quest_id'] = $this->editing->id;
        $validatedData['npc_id'] = $this->npc_item_npc_id;
        $validatedData['item_id'] = $this->npc_item_id;

        $npcItem = NpcItem::create($validatedData);

        $npcItem->save();

        $this->npc_item_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function rewardItemRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingItems = QuestRewardItem::where('quest_id', $this->editing->id)
                ->pluck('item_id')->toArray();
        else
            $existingItems = [];
        return [
            'quest_id' => ['required', 'numeric'],
            'reward_item_amount' => ['required', 'numeric', 'integer'],
            'reward_item_id' => ['required', 'in:'.Item::whereNotIn('id', $existingItems)->pluck('id')->implode(',')],
        ];
    }

    public function addRewardItem()
    {
        $this->quest_id = $this->editing->id;
        $validatedData = $this->validate($this->rewardItemRules());
        // I'm not sure if this is the tidiest way to do this...
        $validatedData['quest_id'] = $this->editing->id;
        $validatedData['item_id'] = $this->reward_item_id;
        $validatedData['item_amount'] = $this->reward_item_amount;

        $rewardItem = QuestRewardItem::create($validatedData);

        $rewardItem->save();

        $this->reward_item_id = null;
        $this->reward_item_amount = 1;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function updateQuestItemOrder($questItems)
    {
        foreach($questItems as $questItem) {
            QuestItem::where('id', $questItem['value'])->update(['answer_seq' => $questItem['order']]);
        }

    }

    /**
     * Updates the amount quest item when changed
     * @param $questItemId int
     * @param $amount int
     */
    public function setQuestItemAmount($questItemId, $amount)
    {
        $questItem = QuestItem::find($questItemId);
        $questItem->item_amount = $amount;
        $questItem->save();
    }

    /**
     * Updates the boolean value of a quest item when a radio button is clicked
     * @param $questItemId int
     * @param $bool bool
     */
    public function setQuestItemBool($questItemId, $bool)
    {
        $questItem = QuestItem::find($questItemId);
        $questItem->answer_bool = $bool;
        $questItem->save();
    }

    /**
     * Updates the boolean value of a quest item when a radio button is clicked
     * @param $questAnswerId int
     * @param $bool bool
     */
    public function setQuestAnswerBool($questAnswerId, $bool)
    {
        $questAnswer = QuestAnswer::find($questAnswerId);
        $questAnswer->correct_bool = $bool;
        $questAnswer->save();
    }

    /**
     * Updates the string value of a quest item when it is changed
     * @param $questItemId int
     * @param $string int
     */
    public function setQuestItemString($questItemId, $string)
    {
        $questItem = QuestItem::find($questItemId);
        $questItem->answer_string = $string;
        $questItem->save();
    }

    /**
     * Updates a question for a quest answer when changed
     * @param $questAnswerId int
     * @param $value int
     */
    public function setQuestAnswerQuestion($questAnswerId, $value)
    {
        $questAnswer = QuestAnswer::find($questAnswerId);
        $questAnswer->question = $value;
        $questAnswer->save();
    }

    /**
     * Updates an answer for a quest when changed
     * @param $questAnswerId int
     * @param $value int
     */
    public function setQuestAnswer($questAnswerId, $value)
    {
        $questAnswer = QuestAnswer::find($questAnswerId);
        $questAnswer->answer_string = $value;
        $questAnswer->save();
    }

}
