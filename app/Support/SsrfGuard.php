<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * SSRF guard for server-side fetches of admin-supplied URLs (iCal import,
 * GRA E-VAT, webhooks).
 *
 * Fixes over the previous check-then-fetch pattern:
 *  - Blocks link-local 169.254.0.0/16 (cloud metadata) — FILTER_FLAG_NO_RES_RANGE
 *    does NOT cover it, so it is checked explicitly.
 *  - Blocks IPv6 loopback/link-local/unique-local (::1, fe80::/10, fc00::/7).
 *  - Validates IPv6 literals (gethostbynamel is IPv4-only and would pass vacuously).
 *  - resolveSafe() returns the vetted IP to PIN with CURLOPT_RESOLVE so the
 *    subsequent fetch cannot re-resolve to a different (internal) address
 *    (DNS-rebinding TOCTOU).
 *  - safeGet()/safePost() disable redirects (a public URL must not 302 internal).
 */
class SsrfGuard
{
    /** @return array{0: string, 1: int, 2: string} host, port, vetted IP to pin */
    public static function resolveSafe(string $url, bool $allowPrivate = false, bool $allowHttp = false): array
    {
        $p = parse_url($url);
        $scheme = strtolower($p['scheme'] ?? '');
        $host = strtolower(trim((string) ($p['host'] ?? ''), '[]'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('URL must be an http(s) address.');
        }
        if ($scheme === 'http' && ! $allowHttp) {
            throw new RuntimeException('Plain http is not allowed for this endpoint.');
        }
        if (! empty($p['user']) || ! empty($p['pass'])) {
            throw new RuntimeException('Do not put credentials in the URL.');
        }
        $port = (int) ($p['port'] ?? ($scheme === 'http' ? 80 : 443));

        // Hostname blocklist (covers names that resolve internally).
        if (! $allowPrivate && ($host === 'localhost' || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local') || str_ends_with($host, '.internal'))) {
            throw new RuntimeException('That address is on a private / internal network.');
        }

        // IP literal: vet directly (covers IPv6 which gethostbynamel misses).
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! $allowPrivate && ! self::isPublicIp($host)) {
                throw new RuntimeException('That address is on a private / internal network.');
            }

            return [$host, $port, $host];
        }

        // Hostname: resolve (IPv4 + IPv6) and vet every answer.
        $ips = self::resolveHost($host);
        if ($ips === []) {
            throw new RuntimeException("DNS: {$host} does not resolve.");
        }
        foreach ($ips as $ip) {
            if (! $allowPrivate && ! self::isPublicIp($ip)) {
                throw new RuntimeException("Blocked: {$host} resolves to a private / internal address ({$ip}).");
            }
        }

        return [$host, $port, $ips[0]];
    }

    /** Validate only (no pinning) — for save-time checks. */
    public static function assertSafeUrl(string $url, bool $allowPrivate = false, bool $allowHttp = false): void
    {
        self::resolveSafe($url, $allowPrivate, $allowHttp);
    }

    public static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 4) {
            $first = ord($packed[0]);
            // Explicitly reject 169.254/16 (cloud metadata/link-local) and
            // 224/4 multicast, neither of which is a valid remote HTTP peer.
            if (($first === 169 && ord($packed[1]) === 254) || $first >= 224) {
                return false;
            }
        } else {
            $first = ord($packed[0]);
            $second = ord($packed[1]);
            // ::ffff:0:0/96 IPv4-mapped addresses: unwrap the final 32 bits.
            if (substr($packed, 0, 12) === str_repeat("\0", 10)."\xff\xff") {
                return self::isPublicIp(inet_ntop(substr($packed, 12)) ?: '');
            }
            // fe80::/10 link-local (fe80 through febf), fc00::/7 ULA, and
            // ff00::/8 multicast are not valid public HTTP destinations.
            if (($first === 0xfe && ($second & 0xc0) === 0x80) || ($first & 0xfe) === 0xfc || $first === 0xff) {
                return false;
            }
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** @return string[] all A + AAAA answers */
    protected static function resolveHost(string $host): array
    {
        $ips = [];
        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $ips = array_merge($ips, $v4);
        }
        if (function_exists('dns_get_record')) {
            $aaaa = @dns_get_record($host, DNS_AAAA);
            if (is_array($aaaa)) {
                foreach ($aaaa as $r) {
                    if (! empty($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Pinned GET with redirects disabled. Returns the HTTP response.
     * $allowPrivate/$allowHttp mirror resolveSafe().
     */
    public static function pinnedGet(string $url, array $headers = [], int $timeout = 20, bool $allowPrivate = false, bool $allowHttp = false): \Illuminate\Http\Client\Response
    {
        [$host, $port, $ip] = self::resolveSafe($url, $allowPrivate, $allowHttp);
        $pin = str_contains($ip, ':') ? "[{$ip}]" : $ip;

        return \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->timeout($timeout)->connectTimeout(5)
            ->withOptions(['allow_redirects' => false, 'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$pin}"]]])
            ->get($url);
    }

    /** Pinned POST with redirects disabled. */
    public static function pinnedPost(string $url, array $data, array $headers = [], int $timeout = 30, bool $allowPrivate = false, bool $allowHttp = false): \Illuminate\Http\Client\Response
    {
        [$host, $port, $ip] = self::resolveSafe($url, $allowPrivate, $allowHttp);
        $pin = str_contains($ip, ':') ? "[{$ip}]" : $ip;

        return \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->timeout($timeout)->connectTimeout(5)
            ->withOptions(['allow_redirects' => false, 'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$pin}"]]])
            ->post($url, $data);
    }
}
