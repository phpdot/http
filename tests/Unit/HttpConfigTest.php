<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\HttpConfig;
use PHPdot\Http\Request;
use PHPUnit\Framework\TestCase;

final class HttpConfigTest extends TestCase
{
    public function testDefaultsAreSafe(): void
    {
        $config = new HttpConfig();

        self::assertSame([], $config->trustedProxies);
        self::assertSame(0, $config->trustedHeaders);
    }

    public function testNamedArgumentsPopulateAllFields(): void
    {
        $config = new HttpConfig(
            trustedProxies: ['10.0.0.0/8', '173.245.48.0/20'],
            trustedHeaders: Request::HEADER_X_FORWARDED_ALL,
        );

        self::assertSame(['10.0.0.0/8', '173.245.48.0/20'], $config->trustedProxies);
        self::assertSame(Request::HEADER_X_FORWARDED_ALL, $config->trustedHeaders);
    }

    public function testReadonlyConstructionStoresValues(): void
    {
        $config = new HttpConfig(['1.2.3.4'], Request::HEADER_X_FORWARDED_FOR);

        self::assertSame(['1.2.3.4'], $config->trustedProxies);
        self::assertSame(Request::HEADER_X_FORWARDED_FOR, $config->trustedHeaders);
    }
}
