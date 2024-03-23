<?php

namespace App\Http\Livewire;

use App\Models\Quest;
use Livewire\Component;

class ShowQuestTools extends DataTable
{
    public $objectClass = "QuestTool";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'quest_name' => '',
        // Filter on quests
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.quest_name' => '',
            'filters.id' => 'int',
        ]);
    }

    /**
     * This query is different from other classes as we are searching by a related model, not a field in this model
     */
    public function getRowsQueryProperty()
    {
        $questIds = array();
        if (isset($this->filters['quest_name'])) {
            $questIds = Quest::where('id', 'like', "%".$this->filters['quest_name']."%")
                ->orWhere('name', 'like', "%".$this->filters['quest_name']."%")->pluck('id');
        }

        $query = $this->objectClass::withCount('questToolLocations')->whereIn('quest_id', $questIds)->orWhere('id', $this->filters['id']);
        return $this->applySorting($query);
    }

}
