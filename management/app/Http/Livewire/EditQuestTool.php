<?php

namespace App\Http\Livewire;
use App\Models\Item;
use App\Models\Map;
use App\Models\Quest;
use App\Models\QuestItem;
use App\Models\QuestTool;
use App\Models\QuestToolLocation;
use Illuminate\Support\Str;

class EditQuestTool extends EditObject
{
    public $objectClass = 'QuestTool';
    public $quest_tool_id;
    public $location_item_id;
    public $location_map_id;
    public $location_item_amount = 1;
    public $location_success_message;
    public $location_x;
    public $location_y;
    public $location_quest_complete = 0;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.quest_id' => ['required', 'in:'.Quest::pluck('id')->implode(',')],
            'editing.item_id' => ['required', 'in:'.Item::pluck('id')->implode(',')],
            'editing.item_amount' => ['numeric', 'integer'],
        ];
    }

    public function updatePickerItem($selectedId, $field)
    {
        if ($field == 'location_item_id') {
            $this->location_item_id = $selectedId;
        } else if ($field == 'item_id') {
            $this->editing->item_id = $selectedId;
            $this->editing->item=Item::find($this->editing->item_id);
        }
    }

    public function questToolLocationRules(): array
    {
        return [
            'quest_tool_id' => ['required', 'in:'.QuestTool::pluck('id')->implode(',')],
            'location_item_id' => ['required', 'in:'.Item::pluck('id')->implode(',')],
            'location_map_id' => ['required', 'in:'.Map::pluck('id')->implode(',')],
            'location_x' => ['required'],
            'location_y' => ['required'],
            'location_item_amount' => ['required', 'numeric', 'integer'],
            'location_success_message' => ['nullable'],
            'location_quest_complete' => ['boolean'],

        ];
    }

    public function addQuestToolLocation()
    {
        $this->quest_tool_id = $this->editing->id;
        $validatedData = $this->validate($this->questToolLocationRules());
        // I'm not sure if this is the tidiest way to do this...
        $validatedData['item_id'] = $this->location_item_id;
        $validatedData['map_id'] = $this->location_map_id;
        $validatedData['x'] = $this->location_x;
        $validatedData['y'] = $this->location_y;
        $validatedData['success_message'] = $this->location_success_message;
        $validatedData['quest_complete'] = $this->location_quest_complete;
        $validatedData['item_amount'] = $this->location_item_amount;

        $questToolLocation = QuestToolLocation::create($validatedData);

        $questToolLocation->save();

        $this->location_item_id = null;
        $this->location_map_id = null;
        $this->location_item_amount = 1;
        $this->location_success_message = null;
        $this->location_x = null;
        $this->location_y = null;
        $this->location_quest_complete = 0;

        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    /**
     * Updates success message of a questToolLocation when changed.
     * @param $questToolLocationId int
     * @param $string int
     */
    public function setLocationMessage($questToolLocationId, $string)
    {
        $questToolLocation = QuestToolLocation::find($questToolLocationId);
        $questToolLocation->success_message = $string;
        $questToolLocation->save();
    }

}
