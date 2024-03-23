<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use App\Models\ProfessionPrerequisite;

class EditProfession extends EditObject
{
    public $objectClass = 'Profession';
    public $profession_id;
    public $profession_id_req;
    public $profession_xp_req; // Needed to help create new prerequisite professions

    /**
     * Set all the rules so we can validate an item
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:170'],
            'editing.require_all_prerequisites' => ['required', 'boolean'],
            // Can I validate that it's unique for this profession here? That will cause DB constraint error

        ];
    }

    /**
     * If you want to specify defaults for new objects to be created, override this method.
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'require_all_prerequisites' => 0,
        ]);
    }

    public function prereqRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->profession_id))
            $existingReqs = ProfessionPrerequisite::where('profession_id', $this->profession_id)->pluck('profession_id_req')->toArray();
        else
            $existingReqs = [];
        return [
            'profession_id' => ['required', 'numeric'],
            'profession_id_req' => ['required', 'in:'.Profession::whereNotIn('id', $existingReqs)
                    ->pluck('id')->implode(',')],
            'profession_xp_req' => ['required', 'numeric'],
        ];
    }

    public function addPrerequisiteProfession()
    {
        $this->profession_id = $this->editing->id;

        $validatedData = $this->validate($this->prereqRules());

        //dd($this->existingReqs);

        $prereq = ProfessionPrerequisite::create($validatedData);
        $prereq->save();

        $this->newProfessionId = null;
        $this->newProfessionXP = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');

    }

}
