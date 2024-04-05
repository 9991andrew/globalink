<?php

namespace App\Http\Livewire;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\WithFileUploads;
use App\Models\Player;

class EditArmors extends EditObject
{
    use WithFileUploads;
    public $objectClass = 'App\Models\Armors';
    public $newImage;
    public $iteration;
    public $player_id;
    public $players;
    public function updatedNewImage()
    {
        $this->validate(['newImage' => 'nullable|image|max:1000']);
    }
    public function mount() {
        $this->players = Player::all();
    }

    public function rules(): array
    {
        return [
            
            'editing.req_lv' => ['required', 'max:255'],
            'editing.player_id' => ['required', 'max:255'],
            'editing.min_hp' => ['numeric', 'required'],
            'editing.max_hp' => ['numeric', 'required'],
            'editing.min_atk' => ['required', 'numeric'],
            'editing.max_atk' => ['required', 'numeric'],
            'editing.min_def' => ['required', 'numeric'],
            'editing.max_def' => ['required', 'numeric'],
            'editing.min_dex' => ['required', 'numeric'],
            'editing.max_dex' => ['required', 'numeric'],
            'editing.armor_type' => ['required', 'numeric'],
            'newImage' => ['nullable', 'image', 'max:1000'],
        ];
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id'=>'',
            'player_id'=> 0,
            'req_lv' => 0,
            'min_hp' => 0,
            'max_hp' => 0,
            'min_mp_consumtion' => 0,
            'max_mp_consumtion' => 0,
            'min_atk' => 0,
            'max_atk' => 0,
            'min_def' => 0,
            'max_def' => 0,
            'min_dex' => 0,
            'max_dex' => 0,
            'armor_type'=> 0,
            
        ]);
    }


    public function save()
    {
        $this->validate();
        $this->editing->save();

        // If an image was uploaded, name it as per the convention for icons.
        // Overwrite the old one if necessary
        if (isset($this->newImage)) {
            // Get the filename without an extension
            $filenameBase = preg_replace('/(.*)(\..*)$/', '$1', $this->editing->imageFilename);
            $filename = Storage::disk('mega')->path($filenameBase.'.png');
            // Store the file as a PNG, regardless of the format it was submitted as.
            $png = Image::make($this->newImage)->save($filename, 100, 'png');
            Image::make($this->newImage)->save();
            // webp filename
            $filename = Storage::disk('mega')->path($filenameBase.'.webp');
            $webp = Image::make($this->newImage)->save($filename, 94, 'webp');
            // Create compact thumbnails
            $tn = Image::make($this->newImage)->resize(
                128,
                128,
                function($constraint) {
                    // preserve aspect ratio
                    $constraint->aspectRatio();
                    // Do not increase the size if the file was smaller to begin with
                    $constraint->upsize();

                });
            // png thumbnail
            $filename = Storage::disk('mega')->path($filenameBase.'-tn.png');
            $tn->save($filename, 90, 'png');

            // webp thumbnail
            $filename = Storage::disk('mega')->path($filenameBase.'-tn.webp');
            $tn->save($filename, 94, 'webp');

            $this->editing->updated_at = now();
            $this->editing->save();
            $this->newImage = null;
        }

        $this->iteration++;
        $this->showModal = false;
        $this->emit('rerenderParent');
    }


}
