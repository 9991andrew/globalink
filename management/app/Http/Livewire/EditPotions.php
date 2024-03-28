<?php

namespace App\Http\Livewire;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\WithFileUploads;

class EditPotions extends EditObject
{
    use WithFileUploads;
    public $objectClass = 'App\Models\Potions';
    public $newImage;
    public $iteration;

    public function updatedNewImage()
    {
        $this->validate(['newImage' => 'nullable|image|max:1000']);
    }

    public function rules(): array
    {
        return [
            'editing.req_lv' => ['required', 'numeric', 'gt:0'],
            'editing.hp' => ['numeric', 'max:255'],
            'editing.mp' => ['numeric', 'max:255'],
            'editing.atk' => ['numeric', 'max:255'],
            'editing.def' => ['numeric', 'max:255'],
            'editing.dex' => ['numeric', 'max:255'],
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
            'req_lv' => 0,
            'hp' => 0,
            'mp' => 0,
            'atk' => 0,
            'def' => 0,
            'dex' => 0,
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
