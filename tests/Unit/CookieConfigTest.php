<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use Nyholm\Psr7\ServerRequest;
use PHPdot\Http\CookieConfig;
use PHPdot\Http\HttpConfig;
use PHPdot\Http\Request;
use PHPdot\Http\ResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CookieConfigTest extends TestCase
{
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
    public function responseFactoryCookieReadsDefaultsFromHttpConfig(): void
    {
        $factory = new ResponseFactory(new HttpConfig(
            cookie: new CookieConfig(
                secure: false,
                sameSite: 'Strict',
                path: '/admin',
                domain: '.example.com',
            ),
        ));

        $cookie = $factory->cookie('session', 'abc');

        self::assertFalse($cookie->isSecure());
        self::assertSame('Strict', $cookie->getSameSite());
        self::assertSame('/admin', $cookie->getPath());
        self::assertSame('.example.com', $cookie->getDomain());
        self::assertTrue($cookie->isHttpOnly());   // default kept
    }

    #[Test]
    public function responseFactoryCookieUsesSafeDefaultsWithoutInjection(): void
    {
        $factory = new ResponseFactory();

        $cookie = $factory->cookie('default', 'val');

        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('Lax', $cookie->getSameSite());
    }

    #[Test]
    public function withSecureFalseStillWorksOnFactoryDefault(): void
    {
        $factory = new ResponseFactory(new HttpConfig(
            cookie: new CookieConfig(secure: true),
        ));

        $cookie = $factory->cookie('dev', 'val')->withSecure(false);

        self::assertFalse($cookie->isSecure());
    }

    #[Test]
    public function requestReadsTrustedProxiesFromInjectedConfig(): void
    {
        $config = new HttpConfig(
            trustedProxies: ['10.0.0.0/8'],
            trustedHeaders: Request::HEADER_X_FORWARDED_ALL,
        );

        $inner = (new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-For', '203.0.113.50');

        $request = new Request($inner, $config);

        self::assertSame('203.0.113.50', $request->ip());
    }

    #[Test]
    public function httpConfigDefaultsToFreshCookieConfig(): void
    {
        $http = new HttpConfig();

        self::assertEquals(new CookieConfig(), $http->cookie);
    }

    #[Test]
    public function defaultCookieFromFactoryIsSessionScoped(): void
    {
        $factory = new ResponseFactory();

        $cookie = $factory->cookie('flash', 'msg');

        self::assertNull($cookie->getExpires());
        self::assertNull($cookie->getMaxAge());
    }
}
