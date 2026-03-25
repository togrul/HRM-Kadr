<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrDocumentsReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrDocuments extends Component
{
    public int $personnelId;

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_unless($access->canAccess(Auth::user(), 'view-own-personnel-documents'), 403);
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    public function openDocument(int $documentId)
    {
        $document = collect($this->payload['documents'])->firstWhere('id', $documentId);

        if (! $document || empty($document['url'])) {
            $this->dispatch('notify', type: 'error', message: __('personnel::my_hr.documents.messages.file_not_available'));

            return;
        }

        return $this->redirect((string) $document['url'], navigate: false);
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrDocumentsReadService::class)->build($this->personnel());
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()
            ->select(['id', 'tabel_no', 'surname', 'name', 'patronymic'])
            ->findOrFail($this->personnelId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.documents');
    }
}
