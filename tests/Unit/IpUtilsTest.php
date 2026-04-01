<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\IpUtils;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IpUtilsTest extends TestCase
{
    #[Test]
    public function ipv4_in_range(): void
    {
        self::assertTrue(IpUtils::inRange('10.0.0.5', '10.0.0.0/8'));
        self::assertTrue(IpUtils::inRange('192.168.1.100', '192.168.1.0/24'));
        self::assertTrue(IpUtils::inRange('172.16.0.1', '172.16.0.0/12'));
    }

    #[Test]
    public function ipv4_not_in_range(): void
    {
        self::assertFalse(IpUtils::inRange('11.0.0.5', '10.0.0.0/8'));
        self::assertFalse(IpUtils::inRange('192.168.2.1', '192.168.1.0/24'));
        self::assertFalse(IpUtils::inRange('8.8.8.8', '10.0.0.0/8'));
    }

    #[Test]
    public function ipv6_in_range(): void
    {
        self::assertTrue(IpUtils::inRange('::1', '::1/128'));
        self::assertTrue(IpUtils::inRange('fc00::1', 'fc00::/7'));
        self::assertTrue(IpUtils::inRange('fd00::abcd', 'fc00::/7'));
    }

    #[Test]
    public function ipv6_not_in_range(): void
    {
        self::assertFalse(IpUtils::inRange('::2', '::1/128'));
        self::assertFalse(IpUtils::inRange('2001:db8::1', 'fc00::/7'));
    }

    #[Test]
    public function matches_with_multiple_cidrs(): void
    {
        self::assertTrue(IpUtils::matches('192.168.1.5', ['10.0.0.0/8', '192.168.0.0/16']));
        self::assertTrue(IpUtils::matches('10.0.0.1', ['10.0.0.0/8', '192.168.0.0/16']));
        self::assertFalse(IpUtils::matches('8.8.8.8', ['10.0.0.0/8', '192.168.0.0/16']));
    }

    #[Test]
    public function is_private_for_rfc1918(): void
    {
        self::assertTrue(IpUtils::isPrivate('10.0.0.1'));
        self::assertTrue(IpUtils::isPrivate('172.16.0.1'));
        self::assertTrue(IpUtils::isPrivate('172.31.255.255'));
        self::assertTrue(IpUtils::isPrivate('192.168.0.1'));
        self::assertTrue(IpUtils::isPrivate('192.168.255.255'));
    }

    #[Test]
    public function is_private_for_loopback(): void
    {
        self::assertTrue(IpUtils::isPrivate('127.0.0.1'));
        self::assertTrue(IpUtils::isPrivate('127.255.255.255'));
        self::assertTrue(IpUtils::isPrivate('::1'));
    }

    #[Test]
    public function is_private_for_public_address(): void
    {
        self::assertFalse(IpUtils::isPrivate('8.8.8.8'));
        self::assertFalse(IpUtils::isPrivate('1.1.1.1'));
        self::assertFalse(IpUtils::isPrivate('2001:db8::1'));
    }

    #[Test]
    public function is_ipv4(): void
    {
        self::assertTrue(IpUtils::isIPv4('192.168.1.1'));
        self::assertTrue(IpUtils::isIPv4('0.0.0.0'));
        self::assertFalse(IpUtils::isIPv4('::1'));
        self::assertFalse(IpUtils::isIPv4('not-an-ip'));
    }

    #[Test]
    public function is_ipv6(): void
    {
        self::assertTrue(IpUtils::isIPv6('::1'));
        self::assertTrue(IpUtils::isIPv6('2001:db8::1'));
        self::assertFalse(IpUtils::isIPv6('192.168.1.1'));
        self::assertFalse(IpUtils::isIPv6('not-an-ip'));
    }

    #[Test]
    public function invalid_ip_returns_false(): void
    {
        self::assertFalse(IpUtils::inRange('not-an-ip', '10.0.0.0/8'));
        self::assertFalse(IpUtils::inRange('192.168.1.1', 'invalid/8'));
        self::assertFalse(IpUtils::isPrivate('garbage'));
    }

    #[Test]
    public function bare_ip_without_cidr_exact_match(): void
    {
        self::assertTrue(IpUtils::inRange('192.168.1.1', '192.168.1.1'));
        self::assertFalse(IpUtils::inRange('192.168.1.2', '192.168.1.1'));
        self::assertTrue(IpUtils::inRange('::1', '::1'));
        self::assertFalse(IpUtils::inRange('::2', '::1'));
    }

    #[Test]
    public function mismatched_ip_families_returns_false(): void
    {
        self::assertFalse(IpUtils::inRange('::1', '10.0.0.0/8'));
        self::assertFalse(IpUtils::inRange('192.168.1.1', '::1/128'));
    }
}
