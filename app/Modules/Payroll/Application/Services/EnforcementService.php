<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\EnforcementDeduction;
use App\Models\EnforcementDocument;
use App\Models\PayrollRun;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * İcra sənədləri üzrə əmək haqqından tutulma (docs/payroll-legal-basis.md §10).
 *
 *  - Baza — «verilməli olan əmək haqqı»: hesablanmış məbləğ çıxılsın vergi və məcburi sosial ödənişlər
 *    («İcra haqqında» m.64 — «vergilər tutulduqdan sonra qalan məbləğ»; ƏM m.176.1 ilə eyni yanaşma).
 *  - Hədd: icra sənədləri üzrə tutulmalar birlikdə bazanın 50 %-ni keçmir (ƏM m.176.1–176.2, «İcra haqqında»
 *    m.65.1); aliment, sağlamlığa vurulmuş ziyan, ailə başçısının itirilməsi və cinayətlə vurulmuş ziyan üzrə —
 *    70 % (ƏM m.176.3, m.65.3). Qarışıq halda 70 % ümumi həddin içində 50 %-lik tələblər öz 50 %-ni keçmir ⚠️.
 *  - Növbəlilik («İcra haqqında» m.75): əvvəlki növbə tam ödənilmədən sonrakına keçilmir (75.7); 1–4-cü
 *    növbələrdə yer çatmırsa tələblərə mütənasib bölünür (75.8); 5-ci növbədə — sənədlərin daxil olma
 *    ardıcıllığı ilə (75.6).
 *  - Qorunan gəlir: ağır və zərərli əmək şəraitinə görə ödəniş (m.68.1.4) bazadan çıxılır.
 *  - Sabit aylıq məbləğ yalnız adi və son haqq-hesab run-unda tutulur; faizlə olan sənəd hər ödənişdə (off-cycle da)
 *    həmin ödənişin bazasından tutulur.
 *  - Ümumi borc verilibsə tutulma qalıqdan çox olmur; qalıq kilid zamanı azalır, run yenidən açılanda bərpa olunur.
 */
class EnforcementService
{
    public const LINE_CODE = 'enforcement';

    /** Sonuncu növbə: tələblər daxil olma ardıcıllığı ilə ödənilir («İcra haqqında» m.75.6). */
    public const LAST_PRIORITY = 5;

    /**
     * Ağır və zərərli əmək şəraitində işə görə ödənişlərə tutma yönəldilə bilməz («İcra haqqında» m.68.1.4) —
     * bu kodlu qazanc sətirləri tutulma bazasından çıxılır.
     */
    public const PROTECTED_EARNINGS = ['hazard'];

    /** Növə görə növbə («İcra haqqında» Qanun m.75.2–75.6, §10.2). */
    public static function defaultPriority(string $kind): int
    {
        return match ($kind) {
            'alimony', 'health_damage', 'breadwinner_loss' => 1,
            'labour_claim' => 2,
            'social_insurance' => 3,
            'state_budget' => 4,
            default => self::LAST_PRIORITY,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(string $tabelNo, array $data): EnforcementDocument
    {
        $method = (string) ($data['method'] ?? 'percent');

        if ($method === 'fixed' && (float) ($data['fixed_amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['documentForm.fixed_amount' => __('payroll::enforcement.messages.amount_required')]);
        }

        if ($method === 'percent' && ((float) ($data['percent'] ?? 0) <= 0 || (float) $data['percent'] > 70)) {
            throw ValidationException::withMessages(['documentForm.percent' => __('payroll::enforcement.messages.percent_range')]);
        }

        $kind = (string) $data['kind'];
        $totalDebt = filled($data['total_debt'] ?? null) ? round((float) $data['total_debt'], 2) : null;

        return EnforcementDocument::query()->create([
            'tabel_no' => $tabelNo,
            'kind' => $kind,
            'priority' => filled($data['priority'] ?? null) ? (int) $data['priority'] : self::defaultPriority($kind),
            'issuer_type' => (string) ($data['issuer_type'] ?? 'enforcement_officer'),
            'issuer_name' => (string) $data['issuer_name'],
            'officer_name' => $data['officer_name'] ?? null,
            'document_no' => (string) $data['document_no'],
            'document_date' => filled($data['document_date'] ?? null) ? $data['document_date'] : null,
            'received_on' => (string) $data['received_on'],
            'method' => $method,
            'fixed_amount' => $method === 'fixed' ? round((float) $data['fixed_amount'], 2) : null,
            'percent' => $method === 'percent' ? round((float) $data['percent'], 2) : null,
            'recipient_name' => (string) $data['recipient_name'],
            'recipient_account' => $data['recipient_account'] ?? null,
            'recipient_details' => $data['recipient_details'] ?? null,
            'total_debt' => $totalDebt,
            'remaining' => $totalDebt,
            'starts_on' => (string) $data['starts_on'],
            'ends_on' => filled($data['ends_on'] ?? null) ? $data['ends_on'] : null,
            'status' => 'active',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /** Sənədin statusu (dayandırma, bitirmə) — səbəb qeydə yazılır, audit LogsActivity ilə. */
    public function setStatus(EnforcementDocument $document, string $status, string $reason): EnforcementDocument
    {
        if (! in_array($status, EnforcementDocument::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => __('payroll::enforcement.messages.invalid_status')]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['statusReason' => __('payroll::enforcement.messages.reason_required')]);
        }

        $document->update([
            'status' => $status,
            'note' => trim(($document->note ? $document->note."\n" : '').now()->format('d.m.Y').' — '.__('payroll::enforcement.statuses.'.$status).': '.trim($reason)),
        ]);

        return $document;
    }

    /** Heç bir kilidli run-da tutulmamış sənəd silinə bilər (səhv daxil edilmiş). */
    public function delete(EnforcementDocument $document): void
    {
        if ($document->deductions()->exists()) {
            throw ValidationException::withMessages(['document' => __('payroll::enforcement.messages.has_deductions')]);
        }

        $document->delete();
    }

    /**
     * Bu ödənişdə hər sənəd üzrə tutulacaq məbləğ.
     *
     * @return array{ratio: float, room: float, lines: list<array{document_id: int, requested: float, amount: float, name: string}>}
     */
    public function plan(string $tabelNo, float $wageDue, string $periodStart, string $periodEnd, bool $includeFixed = true): array
    {
        $documents = $this->activeDocuments($tabelNo, $periodStart, $periodEnd);

        if ($documents->isEmpty() || $wageDue <= 0) {
            return ['ratio' => 0.0, 'room' => 0.0, 'lines' => []];
        }

        $requests = [];

        foreach ($documents as $document) {
            if ($document->method === 'fixed' && ! $includeFixed) {
                continue;
            }

            $requested = $document->method === 'fixed'
                ? (float) $document->fixed_amount
                : floor($wageDue * (float) $document->percent + 1e-6) / 100;

            if ($document->remaining !== null) {
                $requested = min($requested, max(0.0, (float) $document->remaining));
            }

            $requested = round($requested, 2);

            if ($requested >= 0.01) {
                $requests[] = ['document' => $document, 'requested' => $requested];
            }
        }

        $seventy = $documents->contains(fn (EnforcementDocument $d): bool => in_array($d->kind, EnforcementDocument::SEVENTY_PERCENT_KINDS, true));
        $ratio = $seventy ? 0.70 : 0.50;
        $room = DeductionCap::room($wageDue, 0.0, $ratio);
        // Qarışıq halda adi (50 %-lik) tələblərin öz həddi.
        $ordinaryRoom = DeductionCap::room($wageDue, 0.0, 0.50);
        $lines = [];

        foreach (collect($requests)->groupBy(fn (array $r): int => (int) $r['document']->priority)->sortKeys() as $group) {
            $groupRequests = $group->values()->all();
            $wanted = round((float) collect($groupRequests)->sum('requested'), 2);
            $available = $room;

            $ordinaryInGroup = collect($groupRequests)->every(fn (array $r): bool => ! in_array($r['document']->kind, EnforcementDocument::SEVENTY_PERCENT_KINDS, true));
            if ($ordinaryInGroup) {
                $available = min($available, $ordinaryRoom);
            }

            $share = $wanted <= $available ? 1.0 : ($wanted > 0 ? $available / $wanted : 0.0);
            $sequential = (int) $groupRequests[0]['document']->priority >= self::LAST_PRIORITY;
            $left = $available;

            foreach ($groupRequests as $request) {
                $amount = match (true) {
                    $share >= 1.0 => $request['requested'],
                    // 5-ci növbə: daxil olma ardıcıllığı ilə (activeDocuments received_on üzrə sıralayır).
                    $sequential => min($request['requested'], max(0.0, $left)),
                    default => floor($request['requested'] * $share * 100 + 1e-6) / 100,
                };
                $amount = round(max(0.0, $amount), 2);
                $left = round($left - $amount, 2);
                $document = $request['document'];

                $lines[] = [
                    'document_id' => (int) $document->id,
                    'requested' => $request['requested'],
                    'amount' => $amount,
                    'name' => $this->lineName($document, $request['requested'], $amount),
                ];

                $room = round($room - $amount, 2);
                if (! in_array($document->kind, EnforcementDocument::SEVENTY_PERCENT_KINDS, true)) {
                    $ordinaryRoom = round($ordinaryRoom - $amount, 2);
                }
            }
        }

        return ['ratio' => $ratio, 'room' => DeductionCap::room($wageDue, 0.0, $ratio), 'lines' => array_values(array_filter($lines, fn (array $line): bool => $line['amount'] >= 0.01 || $line['requested'] >= 0.01))];
    }

    /**
     * Kilid: payslip-lərdəki `enforcement` sətirləri qədər tutulma yazılır, qalıq azalır. İdempotent.
     */
    public function recordForRun(PayrollRun $run): void
    {
        if (! InstalledTables::has('enforcement_deductions')) {
            return;
        }

        $paidOn = $run->period->ends_on->toDateString();
        $payslips = $run->payslips()->with(['lines' => fn ($q) => $q->where('code', self::LINE_CODE)])->get();

        DB::transaction(function () use ($run, $payslips, $paidOn): void {
            foreach ($payslips as $payslip) {
                $statutory = (float) $payslip->lines()->where('kind', 'deduction')->where('is_statutory', true)->sum('amount');
                $protected = (float) $payslip->lines()->where('kind', 'earning')->whereIn('code', self::PROTECTED_EARNINGS)->sum('amount');
                $wageDue = round(max(0.0, (float) $payslip->getAttribute('gross') - $statutory - $protected), 2);

                foreach ($payslip->lines as $line) {
                    [$documentId, $requested] = self::decodeSource($line->sources);
                    $document = $documentId ? EnforcementDocument::query()->lockForUpdate()->find($documentId) : null;

                    if ($document === null || EnforcementDeduction::query()->where('enforcement_document_id', $document->id)->where('payroll_run_id', $run->id)->exists()) {
                        continue;
                    }

                    $amount = round((float) $line->amount, 2);
                    $remaining = $document->remaining === null ? null : round(max(0.0, (float) $document->remaining - $amount), 2);

                    EnforcementDeduction::query()->create([
                        'enforcement_document_id' => $document->id,
                        'payroll_run_id' => $run->id,
                        'payslip_id' => $payslip->id,
                        'tabel_no' => (string) $payslip->tabel_no,
                        'requested_amount' => max($requested, $amount),
                        'amount' => $amount,
                        'wage_due' => $wageDue,
                        'remaining_after' => $remaining,
                        'paid_on' => $paidOn,
                    ]);

                    if ($remaining !== null) {
                        $document->update(['remaining' => $remaining, 'status' => $remaining <= 0 ? 'closed' : $document->status]);
                    }
                }
            }
        });
    }

    /** Run yenidən açılanda: tutulmalar silinir, qalıq bərpa olunur. */
    public function reverseForRun(PayrollRun $run): void
    {
        if (! InstalledTables::has('enforcement_deductions')) {
            return;
        }

        DB::transaction(function () use ($run): void {
            EnforcementDeduction::query()->where('payroll_run_id', $run->id)->with('document')->get()
                ->each(function (EnforcementDeduction $deduction): void {
                    $document = $deduction->document;

                    if ($document !== null && $document->remaining !== null) {
                        $document->update([
                            'remaining' => round((float) $document->remaining + (float) $deduction->amount, 2),
                            'status' => $document->status === 'closed' ? 'active' : $document->status,
                        ]);
                    }

                    $deduction->delete();
                });
        });
    }

    /**
     * İcra məmuruna göndəriləcək siyahı: run üzrə hər sənədin tutulması.
     *
     * @return list<array<string, mixed>>
     */
    public function reportRows(PayrollRun $run): array
    {
        return EnforcementDeduction::query()
            ->where('payroll_run_id', $run->id)
            ->with(['document'])
            ->orderBy('tabel_no')
            ->get()
            ->map(function (EnforcementDeduction $deduction): array {
                $document = $deduction->document;
                $name = DB::table('personnels')->where('tabel_no', $deduction->tabel_no)->first(['surname', 'name', 'patronymic', 'pin']);

                return [
                    'tabel_no' => $deduction->tabel_no,
                    'full_name' => $name ? trim($name->surname.' '.$name->name.' '.$name->patronymic) : '',
                    'pin' => $name->pin ?? '',
                    'kind' => $document ? __('payroll::enforcement.kinds.'.$document->kind) : '',
                    'issuer' => $document ? trim($document->issuer_name.($document->officer_name ? ' ('.$document->officer_name.')' : '')) : '',
                    'document_no' => $document->document_no ?? '',
                    'document_date' => $document?->document_date?->format('d.m.Y') ?? '',
                    'recipient' => $document->recipient_name ?? '',
                    'recipient_account' => $document->recipient_account ?? '',
                    'wage_due' => number_format((float) $deduction->wage_due, 2, '.', ''),
                    'requested' => number_format((float) $deduction->requested_amount, 2, '.', ''),
                    'amount' => number_format((float) $deduction->amount, 2, '.', ''),
                    'remaining' => $deduction->remaining_after === null ? '' : number_format((float) $deduction->remaining_after, 2, '.', ''),
                    'paid_on' => $deduction->paid_on->format('d.m.Y'),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, EnforcementDocument>
     */
    public function activeDocuments(string $tabelNo, string $periodStart, string $periodEnd): Collection
    {
        if (! InstalledTables::has('enforcement_documents')) {
            return collect();
        }

        return EnforcementDocument::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $periodEnd)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $periodStart))
            ->where(fn ($q) => $q->whereNull('remaining')->orWhere('remaining', '>', 0))
            ->orderBy('priority')
            ->orderBy('received_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * Payslip sətrinin mənbəyi: ["doc:<id>", "requested:<məbləğ>"].
     *
     * @return list<string>
     */
    public static function encodeSource(int $documentId, float $requested): array
    {
        return ['doc:'.$documentId, 'requested:'.number_format($requested, 2, '.', '')];
    }

    /**
     * @param  array<array-key, mixed>|null  $sources
     * @return array{0: int|null, 1: float}
     */
    public static function decodeSource(?array $sources): array
    {
        $id = null;
        $requested = 0.0;

        foreach ((array) $sources as $source) {
            if (is_string($source) && preg_match('/^doc:(\d+)$/', $source, $m)) {
                $id = (int) $m[1];
            }
            if (is_string($source) && preg_match('/^requested:(\d+(?:\.\d+)?)$/', $source, $m)) {
                $requested = (float) $m[1];
            }
        }

        return [$id, $requested];
    }

    private function lineName(EnforcementDocument $document, float $requested, float $amount): string
    {
        $name = __('payroll::enforcement.line', [
            'kind' => __('payroll::enforcement.kinds.'.$document->kind),
            'no' => $document->document_no,
        ]);

        if ($amount + 0.001 < $requested) {
            $name .= ' '.__('payroll::enforcement.line_capped', [
                'requested' => number_format($requested, 2, ',', ' '),
            ]);
        }

        return $name;
    }
}
