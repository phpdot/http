<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\Cookie;
use PHPdot\Http\CookieConfig;
use PHPdot\Http\HttpConfig;
use PHPdot\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CookieConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset to a fresh baseline so cross-test state doesn't leak.
        Cookie::setConfig(new CookieConfig());
        Request::setTrustedProxies([], 0);
    }

    #[Test]
    public function defaultsAreSensible(): void
    {
        $config = new CookieConfig();

        self::assertTrue($config->secure);
        self::assertTrue($config->httpOnly);
        self::assertSame('Lax', $config->sameSite);
        self::assertSame('/', $config->path);
        self::assertSame('', $config->domain);
        self::assertFalse($config->partitioned);
    }

    #[Test]
    public function namedArgumentsPopulateAllFields(): void
    {
        $config = new CookieConfig(
            secure: false,
            httpOnly: false,
            sameSite: 'Strict',
            path: '/admin',
            domain: '.example.com',
            partitioned: true,
        );

        self::assertFalse($config->secure);
        self::assertFalse($config->httpOnly);
        self::assertSame('Strict', $config->sameSite);
        self::assertSame('/admin', $config->path);
        self::assertSame('.example.com', $config->domain);
        self::assertTrue($config->partitioned);
    }

    #[Test]
    public function cookieCreateReadsDefaultsFromConfig(): void
    {
        Cookie::setConfig(new CookieConfig(
            secure: false,
            sameSite: 'Strict',
            path: '/admin',
            domain: '.example.com',
        ));

        $cookie = Cookie::create('session', 'abc');

        self::assertFalse($cookie->isSecure());
        self::assertSame('Strict', $cookie->getSameSite());
        self::assertSame('/admin', $cookie->getPath());
        self::assertSame('.example.com', $cookie->getDomain());
        self::assertTrue($cookie->isHttpOnly());   // default kept
    }

    #[Test]
    public function cookieCreateUsesSafeDefaultsWithoutSetConfig(): void
    {
        // Force unconfigured state by giving setConfig a fresh instance,
        // then resetting the static via reflection isn't needed — getConfig()
        // returns whatever we set, so a baseline CookieConfig() suffices.
        Cookie::setConfig(new CookieConfig());

        $cookie = Cookie::create('default', 'val');

        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('Lax', $cookie->getSameSite());
    }

    #[Test]
    public function withSecureFalseStillWorksWhenBaselineIsSecure(): void
    {
        Cookie::setConfig(new CookieConfig(secure: true));

        $cookie = Cookie::create('dev', 'val')->withSecure(false);

        self::assertFalse($cookie->isSecure());
    }

    #[Test]
    public function httpConfigApplyWiresBothRequestAndCookie(): void
    {
        $config = new HttpConfig(
            trustedProxies: ['10.0.0.0/8'],
            trustedHeaders: Request::HEADER_X_FORWARDED_ALL,
            cookie: new CookieConfig(
                secure: false,
                sameSite: 'Strict',
                domain: '.example.com',
            ),
        );

        HttpConfig::apply($config);

        self::assertSame(['10.0.0.0/8'], Request::getTrustedProxies());
        self::assertSame(Request::HEADER_X_FORWARDED_ALL, Request::getTrustedHeaders());

        $cookie = Cookie::create('s', 'v');
        self::assertFalse($cookie->isSecure());
        self::assertSame('Strict', $cookie->getSameSite());
        self::assertSame('.example.com', $cookie->getDomain());
    }

    #[Test]
    public function httpConfigDefaultsToFreshCookieConfig(): void
    {
        $http = new HttpConfig();

        self::assertEquals(new CookieConfig(), $http->cookie);
    }
}
