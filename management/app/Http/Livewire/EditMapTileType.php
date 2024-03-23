<?php

namespace App\Http\Livewire;

use App\Models\Map;
use App\Models\MapTileType;
use App\Models\MapType;
use App\Models\Skill;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\WithFileUploads;

class EditMapTileType extends EditObject
{
    use WithFileUploads;
    public $objectClass = 'MapTileType';
    public $newImage;
    public $new3DModel;
    public $iteration = 0;

    /**
     * Set all the rules so we can validate a map type.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:200'],
            'editing.movement_req' => ['required', 'numeric', 'integer'],
            'editing.skill_id_req' => ['nullable', 'integer', 'in:' . Skill::pluck('id')->implode(',')],
            'editing.map_tile_type_id_image' => ['nullable', 'in:' . MapTileType::pluck('id')->implode(',')],
            'editing.author' => '',
        ];
    }

    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
        ]);
    }

    public function updatedNewImage()
    {
        $this->validate(['newImage' => 'image|max:1000']);
    }

    public function setEditing($objectId)
    {
        $this->newImage = null;
        $this->new3DModel = null;
        $this->iteration++;
        // A bit less elegant when you don't know in advance what the class will be
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

    /**
     * Custom save function to handle the file upload, if there was one.
     * I should abstract this to some kind of component or function in editObject because I'm using very similar code three times
     */
    public function save()
    {
        $this->validate();


        if (empty($this->editing->map_tile_type_id_image)) $this->editing->map_tile_type_id_image = null;
        if (empty($this->editing->skill_id_req)) $this->editing->skill_id_req = null;
        $this->editing->save();

        // If an image was uploaded, name it as per the convention for icons.
        // Overwrite the old one if necessary
        if (isset($this->newImage)) {
            // Get the filename without an extension
            $filenameBase = preg_replace('/(.*)(\..*)$/', '$1', $this->editing->imageFilename);
            $filename = Storage::disk('mega')->path($filenameBase . '.png');
            // Store the file as a PNG, regardless of the format it was submitted as.
            $png = Image::make($this->newImage)->save($filename, 100, 'png');
            Image::make($this->newImage)->save();
            // webp filename
            $filename = Storage::disk('mega')->path($filenameBase . '.webp');
            $webp = Image::make($this->newImage)->save($filename, 94, 'webp');
            // Create compact thumbnails
            $tn = Image::make($this->newImage)->resize(
                200,
                null,
                function ($constraint) {
                    // preserve aspect ratio
                    $constraint->aspectRatio();
                    // Do not increase the size if the file was smaller to begin with
                    $constraint->upsize();
                }
            );
            // png thumbnail
            $filename = Storage::disk('mega')->path($filenameBase . '-tn.png');
            $tn->save($filename, 90, 'png');

            // webp thumbnail
            $filename = Storage::disk('mega')->path($filenameBase . '-tn.webp');
            $tn->save($filename, 94, 'webp');

            $this->editing->updated_at = now();
            $this->editing->save();
        }


        // If an image was uploaded, name it as per the convention for icons.
        // Overwrite the old one if necessary
        if (isset($this->new3DModel)) {

            $model = Storage::disk('mega')->putFileAs(dirname($this->editing->modelFileName), $this->new3DModel, basename($this->editing->modelFileName));
            // // Get the filename without an extension
            // $filenameBase = preg_replace('/(.*)(\..*)$/', '$1', $this->editing->modelFileName);
            // $filename = Storage::disk('mega')->path($filenameBase . '.gltf');
            // // Store the file as a PNG, regardless of the format it was submitted as.
            // $png = Image::make($this->newImage)->save($filename, 100, 'png');
            // Image::make($this->newImage)->save();
            // // webp filename

            $this->editing->updated_at = now();
            $this->editing->save();
        }



        $this->newImage = null;
        $this->new3DModel = null;
        $this->iteration++;

        $this->showModal = false;
        $this->emit('rerenderParent');
    }
}
