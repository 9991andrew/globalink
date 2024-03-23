<?php

namespace App\Http\Livewire;
use App\Models\ItemIcon;
use App\Models\ItemCategory;
use App\Models\ItemEffect;
use App\Models\ItemVariable;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class EditItem extends EditObject
{
    public $objectClass = 'Item';
    public $item_id;
    public $var_name='a';
    public $min_value;
    public $max_value;
    public $previewCount = 0;

    public function updatePickerItem($selectedId, $field)
    {
        if ($field == 'item_icon_id') {
            $this->editing->item_icon_id = $selectedId;
        }
    }

    public function refreshPreview()
    {
        $this->previewCount++;
    }

    /**
     * Update the item effect parameters description
     */
    public function updatedEditing() {
        $this->editing->itemEffect=ItemEffect::find($this->editing->item_effect_id);
    }

    /**
     * Set all the rules so we can validate an item
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.description' => '',
            'editing.item_category_id' => ['nullable', 'in:'.ItemCategory::pluck('id')->implode(',')],
            'editing.item_icon_id' => ['required', 'in:'.ItemIcon::pluck('id')->implode(',')],
            'editing.item_effect_id' => ['nullable', 'in:'.ItemEffect::pluck('id')->implode(',')],
            'editing.effect_parameters' => [], // probably numeric in practice, theoretically could be anything
            'editing.weight' => ['numeric', 'required'],
            'editing.required_level' => ['numeric', 'required'],
            'editing.level' => ['numeric', 'required'],
            'editing.price' => ['numeric', 'required'],
            'editing.amount' => ['numeric', 'required'],
            'editing.max_amount' => ['numeric', 'required'],
            'editing.disabled' => ['nullable', 'boolean'],
        ];
    }


    /**
     * This function simply sets the next variable name in the sequence
     */
    protected function setVariableName() {
        $lastVar = ItemVariable::where('item_id', $this->editing->id)->orderBy('var_name', 'desc')->first();

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
            $this->setVariableName();
        } else {
            $this->editing = $this->makeBlankObject();
            $this->var_name = 'a';
        }

        $this->showModal = true;
    }

    /**
     * On any updates to content, run the JS that typesets MathJax.
     * I'm worried this might slow down the client, but this ensures that MathJax is always rendering when the user updates content.
     */
    public function dehydrate()
    {
        $this->emit('typesetMathJax');
    }

    public function questVariableRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->quest_id))
            $existingNames = ItemVariable::where('quest_id', $this->editing->id)
                ->pluck('var_name')->toArray();
        else
            $existingNames = [];

        return [
            'item_id' => ['required', 'numeric'],
            'var_name' => ['required', Rule::notIn($existingNames)], // Should be unique within the quest
            'min_value' => ['required', 'numeric'],
            'max_value' => ['required', 'numeric'],
        ];
    }

    public function addItemVariable()
    {
        $this->item_id = $this->editing->id;
        $validatedData = $this->validate($this->questVariableRules());
        $itemVariable = ItemVariable::create($validatedData);

        $itemVariable->save();

        $this->setVariableName();
        $this->min_value = null;
        $this->max_value = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'amount' => 1,
            'max_amount' => 1,
            'level' => 1,
            'required_level' => 0,
            'disabled' => 0,
            'price' => 0,
            'weight' => '100',
        ]);
    }

    /**
     * Overriding this to nullify category_id and item_effect_id if they are empty
     * Not sure why this doesn't automatically work...
     */
    public function save()
    {
        $this->validate();
        if ($this->editing->item_category_id == '') $this->editing->item_category_id = null;
        if ($this->editing->item_effect_id == '') $this->editing->item_effect_id = null;
        if (!isset($this->editing->disabled)) $this->editing->disabled = 0;
        $this->editing->save();
        $this->showModal = false;
        $this->emit('rerenderParent');
        // Ensure the object is blank - if this isn't done
        // and this object is deleted, it will break the editor and show 404
        $this->cleanup();
    }

}
