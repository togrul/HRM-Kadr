<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\Personnel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
 */
class RestKpiConnector
{
    public const AUTH_TYPES = ['none', 'bearer', 'basic'];

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
            $response = $this->request($config)->get($url);
        } catch (Throwable $exception) {
            throw new RuntimeException(__('performance_evaluation::kpi.connector.errors.unreachable', ['error' => mb_strimwidth($exception->getMessage(), 0, 160, '…')]));
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
