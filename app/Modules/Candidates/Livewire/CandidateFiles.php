<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CandidateFiles extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $title = '';

    public ?int $candidateModel = null;

    public ?Candidate $candidate = null;

    public array $files = [];

    public array $draft = [
        'display_name' => null,
        'category' => 'other',
        'notes' => null,
    ];

    public array $pendingDeleteIds = [];

    public array $ui = [
        'category_filter' => null,
    ];

    public $uploadedFile = null;

    public function rules(): array
    {
        return $this->addFileRules();
    }

    protected function addFileRules(): array
    {
        return [
            'uploadedFile' => [
                'required',
                'file',
                'max:'.max(1, (int) config('candidates.documents.max_upload_kb', 10240)),
                // svg excluded: inline <script> → stored XSS risk.
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif,webp,bmp',
            ],
            'draft.display_name' => ['required', 'string', 'min:1', 'max:255'],
            'draft.category' => ['required', 'string', 'in:'.implode(',', (array) config('candidates.documents.categories', ['other']))],
            'draft.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function fileMetadataRules(): array
    {
        return [
            'files.*.display_name' => ['required', 'string', 'min:1', 'max:255'],
            'files.*.category' => ['required', 'string', Rule::in((array) config('candidates.documents.categories', ['other']))],
            'files.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'uploadedFile' => __('candidates::files.labels.file'),
            'draft.display_name' => __('candidates::files.labels.display_name'),
            'draft.category' => __('candidates::files.labels.category'),
            'draft.notes' => __('candidates::files.labels.notes'),
        ];
    }

    public function mount(): void
    {
        $this->candidate = Candidate::query()
            ->with(['documents' => fn ($query) => $query->select([
                'id',
                'candidate_id',
                'display_name',
                'original_name',
                'disk',
                'file_path',
                'mime_type',
                'extension',
                'size_bytes',
                'category',
                'notes',
                'created_at',
            ])])
            ->findOrFail($this->candidateModel);

        $this->authorize('update', $this->candidate);

        $this->title = __('candidates::files.titles.files_for', [
            'name' => $this->candidate->fullname,
        ]);

        $this->files = $this->candidate->documents
            ->map(fn (CandidateDocument $document) => $this->mapStoredDocument($document))
            ->all();
    }

    public function addFile(): void
    {
        $this->validate($this->addFileRules());

        /** @var TemporaryUploadedFile $file */
        $file = $this->uploadedFile;

        $this->files[] = [
            'id' => null,
            'display_name' => (string) $this->draft['display_name'],
            'category' => (string) $this->draft['category'],
            'notes' => filled($this->draft['notes']) ? (string) $this->draft['notes'] : null,
            'original_name' => $file->getClientOriginalName(),
            'extension' => Str::upper((string) ($file->getClientOriginalExtension() ?: 'FILE')),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'created_at_label' => __('candidates::files.labels.pending_upload'),
            'download_url' => null,
            'preview_url' => $this->isPreviewable($file->getMimeType(), (string) $file->getClientOriginalExtension())
                ? $file->temporaryUrl()
                : null,
            'is_previewable' => $this->isPreviewable($file->getMimeType(), (string) $file->getClientOriginalExtension()),
            'file' => $file,
        ];

        $this->uploadedFile = null;
        $this->draft = [
            'display_name' => null,
            'category' => 'other',
            'notes' => null,
        ];
    }

    public function removeFile(int $index): void
    {
        $file = $this->files[$index] ?? null;

        if (! is_array($file)) {
            return;
        }

        $documentId = (int) ($file['id'] ?? 0);
        if ($documentId > 0) {
            $this->pendingDeleteIds[] = $documentId;
            $this->pendingDeleteIds = array_values(array_unique($this->pendingDeleteIds));
        }

        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function updatedUiCategoryFilter($value): void
    {
        if ($value === null || $value === '') {
            $this->ui['category_filter'] = null;
        }
    }

    public function store(): void
    {
        $this->authorize('update', $this->candidate);
        $this->validate($this->fileMetadataRules());

        DB::transaction(function (): void {
            if ($this->pendingDeleteIds !== []) {
                $documents = CandidateDocument::query()
                    ->where('candidate_id', $this->candidate->id)
                    ->whereIn('id', $this->pendingDeleteIds)
                    ->get();

                foreach ($documents as $document) {
                    Storage::disk($document->disk)->delete($document->file_path);
                    $document->delete();
                }
            }

            $sortOrder = 1;

            foreach ($this->files as $file) {
                if (! empty($file['id'])) {
                    CandidateDocument::query()
                        ->whereKey((int) $file['id'])
                        ->where('candidate_id', $this->candidate->id)
                        ->update([
                            'display_name' => (string) $file['display_name'],
                            'category' => (string) $file['category'],
                            'notes' => filled($file['notes'] ?? null) ? (string) $file['notes'] : null,
                            'sort_order' => $sortOrder++,
                        ]);

                    continue;
                }

                /** @var TemporaryUploadedFile|null $upload */
                $upload = $file['file'] ?? null;
                if (! $upload instanceof TemporaryUploadedFile) {
                    continue;
                }

                $disk = (string) config('candidates.documents.disk', 'local');
                $directory = trim((string) config('candidates.documents.directory', 'candidates'), '/');
                $path = $upload->store($directory.'/'.$this->candidate->id.'/documents', $disk);

                CandidateDocument::create([
                    'candidate_id' => $this->candidate->id,
                    'display_name' => (string) $file['display_name'],
                    'original_name' => (string) $file['original_name'],
                    'file_path' => $path,
                    'disk' => $disk,
                    'mime_type' => $upload->getMimeType(),
                    'extension' => Str::lower((string) ($upload->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION))),
                    'size_bytes' => (int) ($file['size_bytes'] ?? $upload->getSize() ?? 0),
                    'category' => (string) $file['category'],
                    'notes' => filled($file['notes'] ?? null) ? (string) $file['notes'] : null,
                    'uploaded_by' => auth()->id(),
                    'sort_order' => $sortOrder++,
                ]);
            }
        });

        $this->dispatch('candidateAdded', __('candidates::files.messages.saved'));
        $this->dispatch('closeSideMenu');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.candidate-files', [
            'categoryOptions' => collect((array) config('candidates.documents.categories', ['other']))
                ->map(fn (string $category) => [
                    'id' => $category,
                    'label' => __('candidates::files.categories.'.$category),
                ])
                ->values()
                ->all(),
            'visibleFiles' => $this->visibleFiles,
        ]);
    }

    public function categoryTheme(string $category): array
    {
        return match ($category) {
            'cv' => [
                'surface' => 'from-sky-50 to-blue-50',
                'border' => 'border-sky-200',
                'badge' => 'bg-sky-100 text-sky-700',
                'icon' => 'bg-sky-600 text-white',
            ],
            'passport' => [
                'surface' => 'from-amber-50 to-yellow-50',
                'border' => 'border-amber-200',
                'badge' => 'bg-amber-100 text-amber-700',
                'icon' => 'bg-amber-600 text-white',
            ],
            'diploma' => [
                'surface' => 'from-violet-50 to-fuchsia-50',
                'border' => 'border-violet-200',
                'badge' => 'bg-violet-100 text-violet-700',
                'icon' => 'bg-violet-600 text-white',
            ],
            'medical' => [
                'surface' => 'from-emerald-50 to-green-50',
                'border' => 'border-emerald-200',
                'badge' => 'bg-emerald-100 text-emerald-700',
                'icon' => 'bg-emerald-600 text-white',
            ],
            'test_result' => [
                'surface' => 'from-rose-50 to-pink-50',
                'border' => 'border-rose-200',
                'badge' => 'bg-rose-100 text-rose-700',
                'icon' => 'bg-rose-600 text-white',
            ],
            default => [
                'surface' => 'from-slate-50 to-zinc-50',
                'border' => 'border-slate-200',
                'badge' => 'bg-slate-100 text-slate-700',
                'icon' => 'bg-slate-700 text-white',
            ],
        };
    }

    public function categoryIcon(string $category): string
    {
        return match ($category) {
            'cv' => 'icons.cv-outline',
            'passport' => 'icons.profile-outline-icon',
            'diploma' => 'icons.book-icon',
            'medical' => 'icons.shield-icon',
            'test_result' => 'icons.search-file',
            default => 'icons.document-icon',
        };
    }

    #[Computed]
    public function visibleFiles(): array
    {
        $categoryFilter = data_get($this->ui, 'category_filter');

        if ($categoryFilter === null || $categoryFilter === '' || $categoryFilter === 'all') {
            return $this->files;
        }

        return collect($this->files)
            ->filter(fn (array $file) => ($file['category'] ?? 'other') === $categoryFilter)
            ->all();
    }

    private function mapStoredDocument(CandidateDocument $document): array
    {
        $extension = Str::upper((string) ($document->extension ?: pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE'));
        $mimeType = $document->mime_type;

        return [
            'id' => (int) $document->id,
            'display_name' => (string) $document->display_name,
            'category' => (string) $document->category,
            'notes' => $document->notes,
            'original_name' => (string) $document->original_name,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size_bytes' => (int) $document->size_bytes,
            'created_at_label' => optional($document->created_at)->format('d.m.Y H:i'),
            'download_url' => route('candidates.documents.download', $document),
            'preview_url' => $this->isPreviewable($mimeType, $extension)
                ? route('candidates.documents.download', ['document' => $document, 'inline' => 1])
                : null,
            'is_previewable' => $this->isPreviewable($mimeType, $extension),
            'file' => null,
        ];
    }

    private function isPreviewable(?string $mimeType, ?string $extension): bool
    {
        if (filled($mimeType) && Str::startsWith((string) $mimeType, 'image/')) {
            return true;
        }

        $normalizedExtension = Str::lower((string) $extension);

        // svg intentionally excluded: never render uploaded SVG inline (XSS).
        return in_array($normalizedExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}
