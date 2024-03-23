<?php

namespace App\Http\Livewire;


class ShowQuestTypes extends DataTable
{
    public $objectClass = "QuestType";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    /**
     * I may need to get the count of players who have this birthplace. I cannot delete a birthplace unless
     * no players are using it. I am definitely not doing a cascade delete on the players associated with a birthplace.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('quests')->withCount('questTypeResultTypes'), $this->filters);
        return $this->applySorting($query);
    }

}
