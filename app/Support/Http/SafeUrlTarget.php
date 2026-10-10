<?php

namespace App\Support\Http;

/**
 * Yoxlanmış URL hədəfi: host, port və sorğunun bağlanacağı (pin) IP ünvanı.
 * `ip` null-dursa (allow-list host-u) DNS-ə toxunulmur.
 */
final class SafeUrlTarget
{
    public function __construct(
        public readonly string $url,
        public readonly string $host,
        public readonly int $port,
        public readonly ?string $ip,
    ) {}
}
