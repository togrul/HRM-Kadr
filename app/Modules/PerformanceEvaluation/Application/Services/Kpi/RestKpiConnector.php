<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\Personnel;
use App\Support\Http\SafeUrl;
use App\Support\Http\UnsafeUrlException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Generic REST source (spec §8, "Generic REST"): covers 1C OData, CRM and helpdesk APIs
 * that answer a GET with JSON. The URL names the person and the period through
 * placeholders — {tabel_no}, {email}, {pin}, {from}, {to} — and `value_path` points at
 * the number in the answer (dot notation, e.g. `data.0.total`). Failed calls retry
 * three times with a growing pause.
 *
 * Config: url, auth (none|bearer|basic), token, username, password, value_path.
 *
 * Təhlükəsizlik: URL {@see SafeUrl} ilə yoxlanır (daxili/özəl ünvanlar, metadata xidməti
 * bloklanır), sorğu yoxlanmış IP-yə bağlanır, yönləndirmə izlənmir. Xarici sistemin cavab
 * mətni və istisna mesajı istifadəçiyə heç vaxt göstərilmir — yalnız jurnala yazılır.
 * `{pin}` (FİN) yer tutucusu yalnız istifadəçi idarəçisi tərəfindən qurula bilər.
 */
class RestKpiConnector
{
    public const AUTH_TYPES = ['none', 'bearer', 'basic'];

    /** FİN kimi həssas şəxsi məlumatı xarici sistemə ötürən yer tutucular. */
    public const SENSITIVE_PLACEHOLDERS = ['{pin}'];

    public function __construct(private readonly SafeUrl $safeUrl) {}

    /**
     * URL-də həssas yer tutucu ({pin}) varmı.
     */
    public static function usesSensitivePlaceholder(string $url): bool
    {
        foreach (self::SENSITIVE_PLACEHOLDERS as $placeholder) {
            if (str_contains(strtolower($url), $placeholder)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws RuntimeException with a message HR can act on
     */
    public function fetch(array $config, Personnel $personnel, Carbon $from, Carbon $to): float
    {
        $url = strtr((string) ($config['url'] ?? ''), [
            '{tabel_no}' => rawurlencode((string) $personnel->tabel_no),
            '{email}' => rawurlencode((string) $personnel->email),
            '{pin}' => rawurlencode((string) $personnel->pin),
            '{from}' => $from->toDateString(),
            '{to}' => $to->toDateString(),
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.bad_url'));
        }

        try {
            $target = $this->safeUrl->assert($url);
        } catch (UnsafeUrlException $exception) {
            Log::warning('KPI REST konnektoru: URL bloklandı.', ['reason' => $exception->getMessage()]);

            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.blocked_url'));
        }

        try {
            $response = $this->safeUrl->pin($this->request($config), $target)->get($url);
        } catch (RequestException $exception) {
            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.http_status', ['status' => $exception->response->status()]));
        } catch (Throwable $exception) {
            Log::warning('KPI REST konnektoru: xarici sistem cavab vermədi.', ['error' => $exception->getMessage()]);

            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.unreachable'));
        }

        if (! $response->successful()) {
            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.http_status', ['status' => $response->status()]));
        }

        $value = data_get($response->json(), (string) ($config['value_path'] ?? ''));

        if (! is_numeric($value)) {
            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.no_number', ['path' => (string) ($config['value_path'] ?? '')]));
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function request(array $config): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout(15)
            ->retry(3, fn (int $attempt): int => 200 * 2 ** ($attempt - 1));

        return match ($config['auth'] ?? 'none') {
            'bearer' => $request->withToken((string) ($config['token'] ?? '')),
            'basic' => $request->withBasicAuth((string) ($config['username'] ?? ''), (string) ($config['password'] ?? '')),
            default => $request,
        };
    }
}
