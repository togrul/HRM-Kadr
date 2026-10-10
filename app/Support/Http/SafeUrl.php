<?php

namespace App\Support\Http;

use Closure;
use Illuminate\Http\Client\PendingRequest;

/**
 * Server tərəfli HTTP sorğuları üçün SSRF qoruması.
 *
 * - Yalnız http/https; production-da (konfiqurasiya ilə) yalnız https.
 * - URL-də istifadəçi adı/şifrə (`user:pass@host`) qəbul edilmir.
 * - Host DNS ilə həll edilir; hər hansı ünvan loopback, özəl (RFC1918, fc00::/7),
 *   link-local (169.254/16 — bulud metadata xidməti, fe80::/10), CGNAT, multicast və ya
 *   rezerv diapazondadırsa rədd edilir. IPv4-mapped IPv6 (::ffff:127.0.0.1) açılıb yoxlanır.
 * - `config('security.outbound.allowed_hosts')` siyahısındakı host-lar bu yoxlamadan azaddır
 *   (məs. daxili şəbəkədəki 1C / KPI sistemi).
 * - {@see pin()} sorğunu yoxlanmış IP-yə bağlayır (DNS rebinding-ə qarşı) və yönləndirmələri bağlayır.
 *
 * Testlərdə DNS həlledicisi konstruktor vasitəsilə əvəz olunur.
 */
class SafeUrl
{
    /** @var list<string> */
    private const BLOCKED_RANGES = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
        '::/128',
        '::1/128',
        '64:ff9b::/96',
        '100::/64',
        '2001:db8::/32',
        'fc00::/7',
        'fe80::/10',
        'fec0::/10',
        'ff00::/8',
    ];

    /** @var Closure(string): list<string> */
    private Closure $resolver;

    /**
     * @param  (Closure(string): list<string>)|null  $resolver  host => IP siyahısı
     */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? self::systemResolver(...);
    }

    public function isAllowed(string $url): bool
    {
        try {
            $this->assert($url);

            return true;
        } catch (UnsafeUrlException) {
            return false;
        }
    }

    /**
     * @throws UnsafeUrlException
     */
    public function assert(string $url): SafeUrlTarget
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new UnsafeUrlException('URL parse edilmədi.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException("İcazəsiz sxem: {$scheme}");
        }

        if ($scheme !== 'https' && app()->environment('production') && (bool) config('security.outbound.require_https_in_production', true)) {
            throw new UnsafeUrlException('Production-da yalnız https.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeUrlException('URL-də giriş məlumatı ola bilməz.');
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if ($host === '') {
            throw new UnsafeUrlException('Host boşdur.');
        }

        if ($this->isAllowListed($host)) {
            return new SafeUrlTarget($url, $host, $port, null);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : ($this->resolver)($host);

        if ($ips === []) {
            throw new UnsafeUrlException("Host həll edilmədi: {$host}");
        }

        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                throw new UnsafeUrlException("Daxili ünvan bloklandı: {$host} → {$ip}");
            }
        }

        return new SafeUrlTarget($url, $host, $port, $ips[0]);
    }

    /**
     * Sorğunu yoxlanmış IP-yə bağlayır və yönləndirmələri söndürür.
     */
    public function pin(PendingRequest $request, SafeUrlTarget $target): PendingRequest
    {
        $request = $request->withoutRedirecting();

        if ($target->ip === null || filter_var($target->host, FILTER_VALIDATE_IP) !== false || ! defined('CURLOPT_RESOLVE')) {
            return $request;
        }

        $address = str_contains($target->ip, ':') ? '['.$target->ip.']' : $target->ip;

        return $request->withOptions([
            'curl' => [CURLOPT_RESOLVE => [$target->host.':'.$target->port.':'.$address]],
        ]);
    }

    public static function isBlockedIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true;
        }

        // IPv4-mapped / -compatible IPv6 (::ffff:a.b.c.d) — içindəki IPv4 yoxlanır.
        if (strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
            return self::isBlockedIp((string) inet_ntop(substr($packed, 12)));
        }

        foreach (self::BLOCKED_RANGES as $range) {
            if (self::inRange($packed, $range)) {
                return true;
            }
        }

        return false;
    }

    private static function inRange(string $packedIp, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $packedSubnet = inet_pton($subnet);

        if ($packedSubnet === false || strlen($packedSubnet) !== strlen($packedIp)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if (strncmp($packedIp, $packedSubnet, $bytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($packedIp[$bytes]) & $mask) === (ord($packedSubnet[$bytes]) & $mask);
    }

    private function isAllowListed(string $host): bool
    {
        $allowed = array_filter(array_map(
            fn (string $item): string => strtolower(trim($item)),
            explode(',', (string) config('security.outbound.allowed_hosts', '')),
        ));

        return in_array($host, $allowed, true);
    }

    /**
     * @return list<string>
     */
    private static function systemResolver(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];

        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = (string) $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
