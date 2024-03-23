<?php

/**
 *
 * This class is dead and should be deleted
 */

namespace App\Http\Livewire;
use App\Models\QuestTypeResultType;
use App\Models\QuestResultType;
use Illuminate\Support\Str;

class EditQuestType extends EditObject
{
    public $objectClass = 'QuestType';
    public $quest_result_type_id;
    public $quest_type_id;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.is_quest_chain' => ['required', 'boolean'],
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
            'is_quest_chain' => 0,
        ]);
    }


    public function resultTypeRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->npc_id))
            $existingResultTypes = QuestTypeResultType::where('quest_type_id', $this->editing->id)
                ->pluck('quest_result_type_id')->toArray();
        else
            $existingResultTypes = [];
        return [
            'quest_type_id' => ['required', 'numeric'],
            'quest_result_type_id' => ['required', 'in:'.QuestResultType::whereNotIn('id', $existingResultTypes)->pluck('id')->implode(',')],
        ];
    }

    public function addQuestResultType()
    {
        $this->quest_type_id = $this->editing->id;
        $validatedData = $this->validate($this->resultTypeRules());

        $questTypeResultType = QuestTypeResultType::create($validatedData);
        $questTypeResultType->quest_type_id = $this->editing->id;
        $questTypeResultType->save();

        $this->quest_result_type_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


}
