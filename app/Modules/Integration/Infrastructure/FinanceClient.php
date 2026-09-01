<?php

namespace App\Modules\Integration\Infrastructure;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads from the finance system.
 *
 * The mirror image of the feeds this side serves: the finance system is the
 * authority on payslips, accounting periods and the production calendar, and it
 * owns business trips. This pulls them rather than waiting to be pushed, because
 * in some installations only one side can reach the other and the protocol has
 * to work either way round.
 *
 * ## Not configured is not an error
 *
 * A standalone installation has no finance system. {@see self::isConfigured()}
 * answers that, and callers skip quietly rather than logging a failure every
 * time the scheduler runs.
 */
class FinanceClient
{
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->token() !== '';
    }

    /**
     * One page of a cursor feed.
     *
     * @param  array<string, mixed>  $query
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function page(string $path, array $query = []): array
    {
        $body = $this->get($path, $query);

        $items = $body['items'] ?? [];

        return [
            'items' => is_array($items) ? array_values(array_filter($items, 'is_array')) : [],
            'last_sequence' => (int) ($body['last_sequence'] ?? 0),
            'has_more' => (bool) ($body['has_more'] ?? false),
        ];
    }

    /**
     * Walk every page of a cursor feed.
     *
     * Two guards against spinning: the cursor must advance, and the page count
     * is bounded. A counterpart that keeps returning the same page stops the
     * run with a message rather than looping silently.
     *
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    public function all(string $path, array $query = [], int $maxPages = 200): array
    {
        $items = [];
        $after = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            $chunk = $this->page($path, array_merge($query, ['after' => $after, 'limit' => 500]));
            $items = array_merge($items, $chunk['items']);

            if (! $chunk['has_more']) {
                return $items;
            }

            if ($chunk['last_sequence'] <= $after) {
                throw new RuntimeException("Finance feed [{$path}] stopped advancing at cursor {$after}.");
            }

            $after = $chunk['last_sequence'];
        }

        throw new RuntimeException("Finance feed [{$path}] did not finish within {$maxPages} pages.");
    }

    /**
     * A plain (non-paged) read — period state, calendar.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The finance connection is not configured.');
        }

        try {
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->timeout((int) config('integration.finance.timeout', 30))
                ->get(rtrim($this->baseUrl(), '/').$path, $query);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Could not reach the finance system at '.$this->baseUrl().'. Check the address and the network.',
                previous: $e,
            );
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException(
                'The finance system refused our token. Issue a new one there with the "hr" ability.'
            );
        }

        if ($response->failed()) {
            throw new RuntimeException('The finance system returned '.$response->status().'.');
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw new RuntimeException('The finance system did not return JSON — check the address.');
        }

        // The finance API wraps every payload in `data`.
        $payload = $body['data'] ?? $body;

        return is_array($payload) ? $payload : [];
    }

    private function baseUrl(): string
    {
        return trim((string) config('integration.finance.base_url', ''));
    }

    private function token(): string
    {
        return trim((string) config('integration.finance.token', ''));
    }
}
