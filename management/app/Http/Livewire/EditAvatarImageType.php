<?php

namespace App\Http\Livewire;
use App\Models\AvatarImageType;
use App\Models\AvatarImageTypeColor;
use Livewire\Component;
use Illuminate\Support\Str;

class EditAvatarImageType extends EditObject
{
    public $objectClass = 'AvatarImageType';
    public $newColor = null;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.layer_index' => ['required', 'numeric'],
            'editing.display_seq' => ['numeric'],
            'editing.disabled' => ['boolean'],
            'newColor' => ['nullable', 'max:35', 'not_in:'.AvatarImageTypeColor::where('avatar_image_type_id', $this->editing->id)->pluck('css_color')->implode(','),
            ],
        ];
    }

    public function updateColorOrder($colors)
    {
        foreach($colors as $color) {
            AvatarImageTypeColor::where('id', $color['value'])->update(['display_seq' => $color['order']]);
        }
    }

    public function addColor()
    {
        if (!is_null($this->newColor)) {
            $this->validate();
            // If the color is a hex color, ensure a # is prepended
            $this->newColor = preg_replace('/^([a-f|0-9]{3,8})$/i', '#\1', trim($this->newColor));

            AvatarImageTypeColor::make([
                'avatar_image_type_id' => $this->editing->id,
                'css_color' => $this->newColor,
            ])->save();

            $this->newColor = null;
            $this->emit('rerenderParent');
            $this->emit('editorRefresh');

        }
    }


}
