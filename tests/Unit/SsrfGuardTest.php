<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SsrfGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SsrfGuardTest extends TestCase
{
    #[DataProvider('blockedAddresses')]
    public function test_internal_and_special_addresses_are_blocked(string $ip): void
    {
        self::assertFalse(SsrfGuard::isPublicIp($ip), $ip);
    }

    public static function blockedAddresses(): array
    {
        return array_map(static fn (string $ip): array => [$ip], [
            '127.0.0.1', '10.0.0.1', '172.16.0.1', '192.168.1.1',
            '169.254.169.254', '0.0.0.0', '224.0.0.1',
            '::', '::1', 'fc00::1', 'fd12::1', 'fe80::1', 'fe90::1', 'febf::1',
            '::ffff:127.0.0.1', '::ffff:169.254.169.254', 'not-an-ip',
        ]);
    }

    #[DataProvider('publicAddresses')]
    public function test_public_addresses_are_allowed(string $ip): void
    {
        self::assertTrue(SsrfGuard::isPublicIp($ip), $ip);
    }

    public static function publicAddresses(): array
    {
        return [['8.8.8.8'], ['1.1.1.1'], ['2606:4700:4700::1111']];
    }

    public function test_credentials_in_url_are_rejected_before_dns(): void
    {
        $this->expectException(RuntimeException::class);
        SsrfGuard::resolveSafe('https://user:secret@example.com');
    }

    public function test_plain_http_is_rejected_by_default(): void
    {
        $this->expectException(RuntimeException::class);
        SsrfGuard::resolveSafe('http://8.8.8.8/');
    }

    public function test_private_literal_can_only_be_explicitly_allowed(): void
    {
        self::assertSame(['127.0.0.1', 8080, '127.0.0.1'], SsrfGuard::resolveSafe('http://127.0.0.1:8080/feed', true, true));
    }
}
