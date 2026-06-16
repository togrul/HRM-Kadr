<?php

namespace App\Services\Orders\Document;

use App\Models\OrderLog;
use App\Models\Personnel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists a block-engine order into the shared order_logs list.
 *
 * The frozen content (approved HTML + the data used) is stored in
 * template_snapshot with render mode "block_v2"; the order is created pending
 * approval so it carries NO business side-effects yet (vacation/employment records
 * are still produced only by the legacy approval path — wiring those per type is
 * the next, separately-validated step). It links to no legacy order/order_type row.
 */
class OrderIssueService
{
    public const RENDER_MODE = 'block_v2';

    public const STATUS_PENDING = 10;

    /**
     * @param  array{template_code:string,label?:string,personnel_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string,snapshot_html:string,given_by?:string}  $data
     */
    public function issue(array $data): OrderLog
    {
        return DB::transaction(function () use ($data) {
            $orderLog = OrderLog::create([
                'order_no' => $data['order_number'],
                'given_date' => Carbon::now(),
                'given_by' => $data['given_by'] ?? (auth()->user()?->name ?? 'Sistem'),
                'given_by_rank' => $data['given_by_rank'] ?? '',
                'status_id' => self::STATUS_PENDING,
                'creator_id' => auth()->id(),
                'template_render_mode' => self::RENDER_MODE,
                'template_snapshot' => [
                    'engine' => self::RENDER_MODE,
                    'template_code' => $data['template_code'],
                    'label' => $data['label'] ?? $data['template_code'],
                    'html' => $data['snapshot_html'],
                    'fields' => $data['fields'] ?? [],
                    'personnel_id' => $data['personnel_id'] ?? null,
                    'order_date_text' => $data['order_date'] ?? '',
                ],
            ]);

            if (! empty($data['personnel_id'])) {
                $personnel = Personnel::find($data['personnel_id']);
                if ($personnel && $personnel->tabel_no) {
                    // component_id is null — block-engine orders have no components.
                    $orderLog->personnels()->attach($personnel->tabel_no);
                }
            }

            return $orderLog;
        });
    }

    /**
     * Re-freeze a still-pending block order with corrected content/data. Only the
     * snapshot, order number/date and attached personnel change; the order stays
     * pending so no side-effects have run yet — editing an APPROVED order is
     * rejected because its HR records are already in place.
     *
     * @param  array{template_code?:string,label?:string,personnel_id?:?int,fields?:array<string,mixed>,order_number:string,order_date?:string,snapshot_html:string}  $data
     */
    public function update(OrderLog $orderLog, array $data): OrderLog
    {
        if ((string) $orderLog->template_render_mode !== self::RENDER_MODE) {
            throw new RuntimeException('Only block-engine orders can be edited here.');
        }

        if ((int) $orderLog->status_id !== self::STATUS_PENDING) {
            throw new RuntimeException('Only pending orders can be edited.');
        }

        return DB::transaction(function () use ($orderLog, $data) {
            $snapshot = $orderLog->template_snapshot ?? [];

            $orderLog->update([
                'order_no' => $data['order_number'],
                'template_snapshot' => array_merge($snapshot, [
                    'engine' => self::RENDER_MODE,
                    'template_code' => $data['template_code'] ?? ($snapshot['template_code'] ?? ''),
                    'label' => $data['label'] ?? ($snapshot['label'] ?? ($data['template_code'] ?? '')),
                    'html' => $data['snapshot_html'],
                    'fields' => $data['fields'] ?? [],
                    'personnel_id' => $data['personnel_id'] ?? null,
                    'order_date_text' => $data['order_date'] ?? ($snapshot['order_date_text'] ?? ''),
                    // Inline-editing re-generates from HTML, so any previously uploaded
                    // Word file is now stale — drop it and fall back to the generated doc.
                    'docx_path' => null,
                ]),
            ]);

            $personnel = ! empty($data['personnel_id']) ? Personnel::find($data['personnel_id']) : null;
            $orderLog->personnels()->sync($personnel && $personnel->tabel_no ? [$personnel->tabel_no] : []);

            return $orderLog;
        });
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
