<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Files extends Component
{
    use AuthorizesRequests,WithFileUploads;
    public $title;
    public $files = [];
    public $file_list = [];
    public $personnelModel;
    public $personnelFiles;

    public function rules()
    {
        return [
            'files.file' => 'required|file',
            'files.filename' => 'required|string|min:1'
        ];
    }

    public function addFile()
    {
        $this->validate();

        $this->file_list[] = $this->files;

        $this->files = [];
    }

    public function deleteFile($key)
    {
        $path = $this->file_list[$key]['file'];
        Storage::disk('public')->delete($path);
        unset($this->file_list[$key]);
    }

    public function store()
    {
        if(!empty($this->file_list))
        {
            foreach ($this->file_list as $fileList)
            {
                $_existingFile = $this->personnelFiles->files()->where('filename',$fileList['filename'])->firstOrNew();
                if(empty($_existingFile->file))
                {
                    $fileList['file'] = $fileList['file']->store('files','public');
                }
                $_existingFile->fill($fileList);
                $_existingFile->save();
            }
            $fileNameList = collect($this->file_list)->pluck('filename');
            $this->personnelFiles->files()->whereNotIn('filename', $fileNameList)->delete();
        }
        else
        {
            $this->personnelFiles->files()->delete();
        }
        $this->dispatch('fileAdded', __('File has added successfully!'));
    }

    public function mount()
    {
        $this->personnelFiles = Personnel::with('files:tabel_no,file,filename')
                            ->where('tabel_no',$this->personnelModel)
                            ->first();

        $this->title = __('Files') . "( {$this->personnelFiles->fullname} )";

        $this->file_list = $this->personnelFiles->files->toArray();
    }

    public function render()
    {
        return view('livewire.personnel.files');
    }
}
