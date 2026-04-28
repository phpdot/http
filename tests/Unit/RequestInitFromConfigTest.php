<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\HttpConfig;
use PHPdot\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestInitFromConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], 0);
    }

    public function testInitFromConfigPopulatesTrustedProxies(): void
    {
        $config = new HttpConfig(
            trustedProxies: ['10.0.0.0/8', '173.245.48.0/20'],
            trustedHeaders: Request::HEADER_X_FORWARDED_ALL,
        );

        Request::initFromConfig($config);

        self::assertSame(['10.0.0.0/8', '173.245.48.0/20'], Request::getTrustedProxies());
        self::assertSame(Request::HEADER_X_FORWARDED_ALL, Request::getTrustedHeaders());
    }

    public function testInitFromConfigWithDefaultsIsSafe(): void
    {
        Request::initFromConfig(new HttpConfig());

        self::assertSame([], Request::getTrustedProxies());
        self::assertSame(0, Request::getTrustedHeaders());
    }

    public function testInitFromConfigOverridesPreviousConfiguration(): void
    {
        Request::setTrustedProxies(['1.2.3.4'], Request::HEADER_X_FORWARDED_FOR);

        Request::initFromConfig(new HttpConfig(
            trustedProxies: ['9.8.7.6'],
            trustedHeaders: Request::HEADER_X_FORWARDED_PROTO,
        ));

        self::assertSame(['9.8.7.6'], Request::getTrustedProxies());
        self::assertSame(Request::HEADER_X_FORWARDED_PROTO, Request::getTrustedHeaders());
    }
}
