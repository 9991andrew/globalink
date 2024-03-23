<?php

namespace App\Http\Livewire;
use App\Models\User;

class ShowUsers extends DataTable
{
    public $objectClass = "User";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'username' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.username' => '',
    ]);}

    /**
     * To improve efficiency, we'll redefine the query to load players so we don't have to do
     * repeated queries to get players for each user
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with('players')->withCount('players'), $this->filters);
        return $this->applySorting($query);
    }

}
