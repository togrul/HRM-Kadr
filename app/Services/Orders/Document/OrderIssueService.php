<?php

namespace App\Services\Orders\Document;

use App\Models\OrderLog;
use App\Models\Personnel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
}
