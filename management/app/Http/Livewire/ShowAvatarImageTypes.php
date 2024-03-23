<?php

namespace App\Http\Livewire;


class ShowAvatarImageTypes extends DataTable
{
    public $objectClass = "AvatarImageType";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.name' => 'required',
    ]);}

    /**
     * I may need to get the count of players who have this birthplace. I cannot delete a birthplace unless
     * no players are using it. I am definitely not doing a cascade delete on the players associated with a birthplace.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount(['avatarImages','avatarImageTypeColors']), $this->filters);
        return $this->applySorting($query);
    }

}
