<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderParticipant;
use App\Models\Personnel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists a Word-engine order into the shared order_logs list.
 *
 * The order is created pending approval; its filled .docx is the authoritative
 * document (stored separately and referenced via template_snapshot.docx_path). The
 * snapshot freezes the manual field inputs + the picked personnel so a pending order
 * can be re-opened and regenerated. It links to no legacy order/order_type row.
 */
class OrderIssueService
{
    /** Word-upload engine: the order is a filled .docx. */
    public const RENDER_MODE_DOCX = 'docx_v1';

    public const STATUS_PENDING = 10;

    /**
     * Issue a Word-upload (docx engine) order. The frozen snapshot records the manual
     * field inputs + the picked personnel; the filled .docx is attached separately via
     * attachUploadedDocx(). No HTML is stored — the .docx is the document.
     *
     * @param  array{template_code:string,label?:string,personnel_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string,given_by?:string,given_by_rank?:string,signatory?:array<string,mixed>,participants?:list<array{personnel_id:int,fields?:array<string,mixed>}>|null}  $data
     */
    public function issueWord(array $data): OrderLog
    {
        return DB::transaction(function () use ($data) {
            $orderLog = OrderLog::create([
                'order_no' => $data['order_number'],
                'given_date' => Carbon::now(),
                'given_by' => $data['given_by'] ?? (auth()->user()?->name ?? 'Sistem'),
                'given_by_rank' => $data['given_by_rank'] ?? '',
                'signatory_personnel_id' => data_get($data, 'signatory.personnel_id'),
                'signatory_snapshot' => $data['signatory'] ?? null,
                'status_id' => self::STATUS_PENDING,
                'creator_id' => auth()->id(),
                'template_render_mode' => self::RENDER_MODE_DOCX,
                'template_snapshot' => [
                    'engine' => self::RENDER_MODE_DOCX,
                    'template_code' => $data['template_code'],
                    'label' => $data['label'] ?? $data['template_code'],
                    'fields' => $data['fields'] ?? [],
                    'personnel_id' => $data['personnel_id'] ?? null,
                    // Hire orders reference a candidate + the structure/position they are
                    // hired into (resolved into an employee on approval).
                    'candidate_id' => $data['candidate_id'] ?? null,
                    'hire_structure_id' => $data['hire_structure_id'] ?? null,
                    'hire_position_id' => $data['hire_position_id'] ?? null,
                    'order_date_text' => $data['order_date'] ?? '',
                    'docx_path' => null,
                ],
            ]);

            $this->syncParticipants($orderLog, $data['participants'] ?? null);
            $this->syncPersonnel($orderLog, $this->subjectIds($data), attach: true);

            return $orderLog;
        });
    }

    /**
     * Re-freeze a still-pending docx order with corrected fields/personnel. The caller
     * regenerates and re-attaches the .docx, so the stale path is dropped here.
     *
     * @param  array{template_code?:string,label?:string,personnel_id?:?int,candidate_id?:?int,hire_structure_id?:?int,hire_position_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string,signatory?:array<string,mixed>,participants?:list<array{personnel_id:int,fields?:array<string,mixed>}>|null}  $data
     */
    public function updateWord(OrderLog $orderLog, array $data): OrderLog
    {
        if ((string) $orderLog->template_render_mode !== self::RENDER_MODE_DOCX) {
            throw new RuntimeException('Only docx-engine orders can be edited here.');
        }

        if ((int) $orderLog->status_id !== self::STATUS_PENDING) {
            throw new RuntimeException('Only pending orders can be edited.');
        }

        return DB::transaction(function () use ($orderLog, $data) {
            $snapshot = $orderLog->template_snapshot ?? [];

            $orderLog->update([
                'order_no' => $data['order_number'],
                'signatory_personnel_id' => data_get($data, 'signatory.personnel_id'),
                'signatory_snapshot' => $data['signatory'] ?? $orderLog->signatory_snapshot,
                'template_snapshot' => array_merge($snapshot, [
                    'engine' => self::RENDER_MODE_DOCX,
                    'template_code' => $data['template_code'] ?? ($snapshot['template_code'] ?? ''),
                    'label' => $data['label'] ?? ($snapshot['label'] ?? ($data['template_code'] ?? '')),
                    'fields' => $data['fields'] ?? [],
                    'personnel_id' => $data['personnel_id'] ?? null,
                    'candidate_id' => $data['candidate_id'] ?? null,
                    'hire_structure_id' => $data['hire_structure_id'] ?? null,
                    'hire_position_id' => $data['hire_position_id'] ?? null,
                    'order_date_text' => $data['order_date'] ?? ($snapshot['order_date_text'] ?? ''),
                    'docx_path' => null,
                ]),
            ]);

            $this->syncParticipants($orderLog, $data['participants'] ?? null);
            $this->syncPersonnel($orderLog, $this->subjectIds($data), attach: false);

            return $orderLog;
        });
    }

    /**
     * Copy a Word-engine order as a new draft: same type, subject and manual fields, a
     * fresh "-kopya" number, pending status and no document yet — the author opens it in
     * the composer ("Davam et"), adjusts it and saves, which renders its own .docx.
     */
    public function duplicateWord(OrderLog $source): OrderLog
    {
        if ((string) $source->template_render_mode !== self::RENDER_MODE_DOCX) {
            throw new RuntimeException('Only docx-engine orders can be duplicated.');
        }

        $snapshot = $source->template_snapshot ?? [];

        return $this->issueWord([
            'template_code' => (string) ($snapshot['template_code'] ?? ''),
            'label' => $snapshot['label'] ?? null,
            'personnel_id' => $snapshot['personnel_id'] ?? null,
            'candidate_id' => $snapshot['candidate_id'] ?? null,
            'hire_structure_id' => $snapshot['hire_structure_id'] ?? null,
            'hire_position_id' => $snapshot['hire_position_id'] ?? null,
            'fields' => $snapshot['fields'] ?? [],
            'order_number' => $this->copyNumber((string) $source->order_no),
            'order_date' => $snapshot['order_date_text'] ?? '',
            'signatory' => $source->signatory_snapshot,
            'participants' => $source->participants()->exists()
                ? $source->participants()->get(['personnel_id', 'fields'])
                    ->map(fn (OrderParticipant $participant): array => ['personnel_id' => (int) $participant->personnel_id, 'fields' => (array) $participant->fields])
                    ->all()
                : null,
        ]);
    }

    /** A pending order without a stored document is still a draft (e.g. a fresh copy). */
    public static function isDraft(OrderLog $order): bool
    {
        return (int) $order->status_id === self::STATUS_PENDING
            && empty(data_get($order->template_snapshot, 'docx_path'));
    }

    /** "214-M" → "214-M-kopya", then "214-M-kopya-2", … (order_no is unique). */
    private function copyNumber(string $orderNo): string
    {
        $base = $orderNo.'-kopya';
        $candidate = $base;

        for ($n = 2; OrderLog::withTrashed()->where('order_no', $candidate)->exists(); $n++) {
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }

    /**
     * Who the order is about: every participant of a multi-participant order, else the one
     * picked employee.
     *
     * @param  array<string,mixed>  $data
     * @return list<int>
     */
    private function subjectIds(array $data): array
    {
        if (is_array($data['participants'] ?? null)) {
            return array_map('intval', array_column($data['participants'], 'personnel_id'));
        }

        return empty($data['personnel_id']) ? [] : [(int) $data['personnel_id']];
    }

    /**
     * Store a multi-participant order's people in document order with their own field
     * values (replacing the previous list on edit). Null = a single-person order: nothing
     * is stored and any earlier rows go.
     *
     * @param  list<array{personnel_id:int,fields?:array<string,mixed>}>|null  $participants
     */
    private function syncParticipants(OrderLog $orderLog, ?array $participants): void
    {
        $orderLog->participants()->delete();

        foreach ($participants ?? [] as $index => $participant) {
            $orderLog->participants()->create([
                'personnel_id' => (int) $participant['personnel_id'],
                'position' => $index + 1,
                'fields' => (array) ($participant['fields'] ?? []),
            ]);
        }
    }

    /**
     * Link the order's employees by tabel number (order_log_personnels) — the link the order
     * list's visibility scope and the employee card's «Əmrlər» feed read.
     *
     * @param  list<int>  $personnelIds
     */
    private function syncPersonnel(OrderLog $orderLog, array $personnelIds, bool $attach): void
    {
        $tabel = $personnelIds === []
            ? []
            : Personnel::query()->whereKey($personnelIds)->whereNotNull('tabel_no')->pluck('tabel_no')->unique()->values()->all();

        if ($attach) {
            foreach ($tabel as $t) {
                $orderLog->personnels()->attach($t);
            }

            return;
        }

        $orderLog->personnels()->sync($tabel);
    }

    /**
     * Record a user-uploaded, externally-corrected .docx as the authoritative
     * document for a pending order; printing then serves this file verbatim
     * instead of re-rendering from the HTML snapshot.
     */
    public function attachUploadedDocx(OrderLog $orderLog, string $storedPath): OrderLog
    {
        if ((int) $orderLog->status_id !== self::STATUS_PENDING) {
            throw new RuntimeException('Only pending orders can have their document replaced.');
        }

        $snapshot = $orderLog->template_snapshot ?? [];
        $snapshot['docx_path'] = $storedPath;
        $orderLog->update(['template_snapshot' => $snapshot]);

        return $orderLog;
    }
}
