<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use App\Models\QuestPrerequisiteProfession;
use Livewire\Component;

class ShowQuests extends DataTable
{
    public $objectClass = "Quest";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'profession' => '',
        'quest_result_type_id' => '',
        'old_result_type_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.name' => '',
            'filters.id' => 'numeric',
            'filters.quest_profession_id' => 'in:'.Profession::pluck('id')->implode(','),
        ]);
    }

    /**
     * I may need to get the count of players who have this birthplace. I cannot delete a birthplace unless
     * no players are using it. I am definitely not doing a cascade delete on the players associated with a birthplace.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('questTools')->withCount('questItems'), $this->filters);
        return $this->applySorting($query);
    }

}
