<?php

namespace App\Http\Livewire;
use App\Models\AvatarImageType;
use App\Models\Gender;
use App\Models\Race;
use Livewire\Component;
use Illuminate\Support\Str;

class EditAvatarImage extends EditObject
{
    public $objectClass = 'AvatarImage';

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.filename' => ['max:200'],
            'editing.svg_code' => [''],
            'editing.color_qty' => ['numeric', 'max:3'],
            'editing.avatar_image_type_id' => ['required', 'in:'.AvatarImageType::pluck('id')->implode(',')],
            'editing.gender_id' => ['nullable', 'in:'.Gender::pluck('id')->implode(',')],
            'editing.race_id' => ['nullable', 'in:'.Race::pluck('id')->implode(',')],
        ];
    }

    /**
     * Specify defaults for an avatar image
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'filename' => '',
            'svg_code' => '',

        ]);
    }

    public function updatedEditingSvgCode() {
        // Ensure any SVG tags are removed because we add those back ourselves when we compile an SVG
        $this->editing->svg_code=svgClean($this->editing->svg_code);
    }

    /**
     * Overriding this so I can NULLify gender_id or race_id.
     * Not sure why this doesn't automatically work...
     */
    public function save()
    {
        $this->validate();
        if ($this->editing->gender_id == '') $this->editing->gender_id = null;
        if ($this->editing->race_id == '') $this->editing->race_id = null;
        $this->editing->save();
        $this->showModal = false;
        $this->emit('rerenderParent');
        // Ensure the object is blank - if this isn't done
        // and this object is deleted, it will break the editor and show 404
        $this->cleanup();
    }

}
