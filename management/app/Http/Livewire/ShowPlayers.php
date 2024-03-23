<?php

namespace App\Http\Livewire;
use App\Models\Player;
use App\Models\Birthplace;

class ShowPlayers extends DataTable
{
    public $objectClass = "Player";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'birthplace_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.birthplace_id' => 'numeric',
    ]);}

    // Functions for manipulating multiple selected players
    public function addMovementToSelected($amount=500)
    {
        $this->selectedRowsQuery->increment('movement', $amount);
    }

    public function addMoneyToSelected($amount=20)
    {
        $this->selectedRowsQuery->increment('money', $amount);
    }

    public function healSelected($amount=100)
    {
        // Heals any players with health less than amount to that amount
        $this->selectedRowsQuery->where('health', '<', $amount)->update(['health' => $amount]);
    }

    /**
     * To improve efficiency, we'll redefine the query to load professions so we don't have to do
     * repeated queries to get professions for each user
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with(['race', 'professions', 'birthplace', 'map'])->withCount('professions', 'playerItems'), $this->filters);
        return $this->applySorting($query);
    }

}
