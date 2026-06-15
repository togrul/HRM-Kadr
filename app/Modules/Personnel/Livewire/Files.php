<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Support\Traits\DispatchesPersonnelUiEvents;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Files extends Component
{
    use AuthorizesRequests, WithFileUploads;
    use DispatchesPersonnelUiEvents;

    public $title;

    public $files = [];

    public $uploadedFile = null;

    public $file_list = [];

    public $personnelModel;

    public $personnelFiles;

    public function rules()
    {
        return [
            // svg excluded: it can carry inline <script> (stored XSS when served
            // from the public disk). max caps upload size (KB).
            'uploadedFile' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif,webp,bmp',
            'files.filename' => 'required|string|min:1|max:255',
        ];
    }

    public function addFile()
    {
        $this->validate();

        $this->file_list[] = [
            'file' => $this->uploadedFile,
            'filename' => data_get($this->files, 'filename'),
        ];

        $this->uploadedFile = null;
        $this->files = ['filename' => null];
    }

    public function deleteFile($key)
    {
        $path = $this->file_list[$key]['file'];

        if (is_string($path)) {
            Storage::disk('public')->delete($path);
        }

        unset($this->file_list[$key]);
        $this->file_list = array_values($this->file_list);
    }

    public function store()
    {
        if (! empty($this->file_list)) {
            foreach ($this->file_list as $fileList) {
                $_existingFile = $this->personnelFiles->files()->where('filename', $fileList['filename'])->firstOrNew();
                if (empty($_existingFile->file)) {
                    $fileList['file'] = $fileList['file']->store('files', 'public');
                }
                $_existingFile->fill($fileList);
                $_existingFile->save();
            }
            $fileNameList = collect($this->file_list)->pluck('filename');
            $this->personnelFiles->files()->whereNotIn('filename', $fileNameList)->delete();
        } else {
            $this->personnelFiles->files()->delete();
        }
        $this->dispatch('fileAdded', __('personnel::files.messages.saved'));
        $this->dispatchModalCloseEvent();
    }

    public function mount()
    {
        $this->personnelFiles = Personnel::with('files:tabel_no,file,filename')
            ->where('tabel_no', $this->personnelModel)
            ->withTrashed()
            ->first();

       $this->authorize('update', $this->personnelFiles);

        $this->title = __('personnel::files.titles.files_for', [
            'name' => $this->personnelFiles->fullname,
        ]);

        $this->file_list = $this->personnelFiles->files->toArray();
        $this->files = ['filename' => null];
        $this->uploadedFile = null;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.files');
    }

    public function fileRoute(array $file): string
    {
        $raw = data_get($file, 'file');

        if (is_string($raw)) {
            return Storage::url($raw);
        }

        return method_exists($raw, 'temporaryUrl')
            ? $raw->temporaryUrl()
            : '#';
    }

    public function fileExtension(array $file): string
    {
        $raw = data_get($file, 'file');
        $name = (string) data_get($file, 'filename', '');

        if (is_string($raw)) {
            $extension = pathinfo($raw, PATHINFO_EXTENSION) ?: pathinfo($name, PATHINFO_EXTENSION);

            return Str::upper((string) $extension ?: 'FILE');
        }

        if (method_exists($raw, 'getClientOriginalExtension')) {
            return Str::upper((string) $raw->getClientOriginalExtension() ?: 'FILE');
        }

        return 'FILE';
    }

    public function fileSizeLabel(array $file): string
    {
        $raw = data_get($file, 'file');

        if (is_string($raw) && Storage::disk('public')->exists($raw)) {
            return $this->formatBytes((int) Storage::disk('public')->size($raw));
        }

        if (method_exists($raw, 'getSize')) {
            return $this->formatBytes((int) $raw->getSize());
        }

        return '---';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
