<?php

namespace App\Services\Orders\Document;

use App\Models\OrderLog;
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
     * @param  array{template_code:string,label?:string,personnel_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string,given_by?:string,given_by_rank?:string}  $data
     */
    public function issueWord(array $data): OrderLog
    {
        return DB::transaction(function () use ($data) {
            $orderLog = OrderLog::create([
                'order_no' => $data['order_number'],
                'given_date' => Carbon::now(),
                'given_by' => $data['given_by'] ?? (auth()->user()?->name ?? 'Sistem'),
                'given_by_rank' => $data['given_by_rank'] ?? '',
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

            $this->syncPersonnel($orderLog, $data['personnel_id'] ?? null, attach: true);

            return $orderLog;
        });
    }

    /**
     * Re-freeze a still-pending docx order with corrected fields/personnel. The caller
     * regenerates and re-attaches the .docx, so the stale path is dropped here.
     *
     * @param  array{template_code?:string,label?:string,personnel_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string}  $data
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

            $this->syncPersonnel($orderLog, $data['personnel_id'] ?? null, attach: false);

            return $orderLog;
        });
    }

    private function syncPersonnel(OrderLog $orderLog, ?int $personnelId, bool $attach): void
    {
        $personnel = ! empty($personnelId) ? Personnel::find($personnelId) : null;
        $tabel = $personnel && $personnel->tabel_no ? [$personnel->tabel_no] : [];

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
