<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPdot\Http\Cookie;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CookieTest extends TestCase
{
    #[Test]
    public function create_with_name_and_value(): void
    {
        $cookie = Cookie::create('session', 'abc123');

        self::assertSame('session', $cookie->getName());
        self::assertSame('abc123', $cookie->getValue());
    }

    #[Test]
    public function default_values(): void
    {
        $cookie = Cookie::create('test', 'value');

        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('Lax', $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('', $cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertFalse($cookie->isPartitioned());
        self::assertNull($cookie->getExpires());
        self::assertNull($cookie->getMaxAge());
    }

    #[Test]
    public function with_value_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'old');
        $new = $cookie->withValue('new');

        self::assertNotSame($cookie, $new);
        self::assertSame('old', $cookie->getValue());
        self::assertSame('new', $new->getValue());
    }

    #[Test]
    public function with_expires_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val');
        $date = new DateTimeImmutable('2030-01-01');
        $new = $cookie->withExpires($date);

        self::assertNotSame($cookie, $new);
        self::assertNull($cookie->getExpires());
        self::assertSame($date, $new->getExpires());
    }

    #[Test]
    public function with_max_age_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val');
        $new = $cookie->withMaxAge(3600);

        self::assertNotSame($cookie, $new);
        self::assertNull($cookie->getMaxAge());
        self::assertSame(3600, $new->getMaxAge());
    }

    #[Test]
    public function with_path_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val');
        $new = $cookie->withPath('/admin');

        self::assertNotSame($cookie, $new);
        self::assertSame('/', $cookie->getPath());
        self::assertSame('/admin', $new->getPath());
    }

    #[Test]
    public function with_domain_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val');
        $new = $cookie->withDomain('.example.com');

        self::assertNotSame($cookie, $new);
        self::assertSame('', $cookie->getDomain());
        self::assertSame('.example.com', $new->getDomain());
    }

    #[Test]
    public function with_secure_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val')->withSecure(false);
        $new = $cookie->withSecure(true);

        self::assertNotSame($cookie, $new);
        self::assertFalse($cookie->isSecure());
        self::assertTrue($new->isSecure());
    }

    #[Test]
    public function with_http_only_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val');
        $new = $cookie->withHttpOnly(false);

        self::assertNotSame($cookie, $new);
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($new->isHttpOnly());
    }

    #[Test]
    public function with_same_site_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val')->withSecure(true);
        $new = $cookie->withSameSite('None');

        self::assertNotSame($cookie, $new);
        self::assertSame('Lax', $cookie->getSameSite());
        self::assertSame('None', $new->getSameSite());
    }

    #[Test]
    public function with_partitioned_returns_new_instance(): void
    {
        $cookie = Cookie::create('test', 'val')->withSecure(true);
        $new = $cookie->withPartitioned(true);

        self::assertNotSame($cookie, $new);
        self::assertFalse($cookie->isPartitioned());
        self::assertTrue($new->isPartitioned());
    }

    #[Test]
    public function to_header_string_contains_all_attributes(): void
    {
        $expires = new DateTimeImmutable('2030-06-15 12:00:00 UTC');
        $cookie = Cookie::create('sid', 'xyz')
            ->withPath('/app')
            ->withDomain('.example.com')
            ->withMaxAge(7200)
            ->withExpires($expires)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('Strict');

        $header = $cookie->toHeaderString();

        self::assertStringContainsString('sid=xyz', $header);
        self::assertStringContainsString('Path=/app', $header);
        self::assertStringContainsString('Domain=.example.com', $header);
        self::assertStringContainsString('Max-Age=7200', $header);
        self::assertStringContainsString('Expires=', $header);
        self::assertStringContainsString('Secure', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('SameSite=Strict', $header);
    }

    #[Test]
    public function to_header_string_contains_partitioned(): void
    {
        $cookie = Cookie::create('sid', 'xyz')
            ->withSecure(true)
            ->withPartitioned(true);

        $header = $cookie->toHeaderString();

        self::assertStringContainsString('Partitioned', $header);
    }

    #[Test]
    public function from_header_string_round_trips(): void
    {
        $original = Cookie::create('token', 'abc 123')
            ->withPath('/api')
            ->withDomain('.example.com')
            ->withMaxAge(3600)
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('Strict');

        $header = $original->toHeaderString();
        $parsed = Cookie::fromHeaderString($header);

        self::assertSame($original->getName(), $parsed->getName());
        self::assertSame($original->getValue(), $parsed->getValue());
        self::assertSame($original->getPath(), $parsed->getPath());
        self::assertSame($original->getDomain(), $parsed->getDomain());
        self::assertSame($original->getMaxAge(), $parsed->getMaxAge());
        self::assertSame($original->isSecure(), $parsed->isSecure());
        self::assertSame($original->isHttpOnly(), $parsed->isHttpOnly());
        self::assertSame($original->getSameSite(), $parsed->getSameSite());
    }

    #[Test]
    public function same_site_none_without_secure_throws(): void
    {
        $cookie = Cookie::create('test', 'val')->withSecure(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SameSite=None requires the Secure attribute.');

        $cookie->withSameSite('None');
    }

    #[Test]
    public function partitioned_without_secure_throws(): void
    {
        $cookie = Cookie::create('test', 'val')->withSecure(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partitioned cookies require the Secure attribute.');

        $cookie->withPartitioned(true);
    }

    #[Test]
    public function invalid_cookie_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Cookie::create('bad name', 'val');
    }

    #[Test]
    public function empty_cookie_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie name must not be empty.');

        Cookie::create('', 'val');
    }

    #[Test]
    public function invalid_same_site_value_throws(): void
    {
        $cookie = Cookie::create('test', 'val');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SameSite value');

        $cookie->withSameSite('Invalid');
    }

    #[Test]
    public function is_expired_for_past_date(): void
    {
        $cookie = Cookie::create('test', 'val')
            ->withExpires(new DateTimeImmutable('2000-01-01'));

        self::assertTrue($cookie->isExpired());
    }

    #[Test]
    public function is_not_expired_for_future_date(): void
    {
        $cookie = Cookie::create('test', 'val')
            ->withExpires(new DateTimeImmutable('2099-01-01'));

        self::assertFalse($cookie->isExpired());
    }

    #[Test]
    public function is_not_expired_for_session_cookie(): void
    {
        $cookie = Cookie::create('test', 'val');

        self::assertFalse($cookie->isExpired());
    }

    #[Test]
    public function from_header_string_invalid_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Cookie::fromHeaderString('no-equals-sign');
    }

    #[Test]
    public function value_is_url_encoded_in_header(): void
    {
        $cookie = Cookie::create('test', 'hello world');
        $header = $cookie->toHeaderString();

        self::assertStringContainsString('test=hello%20world', $header);
    }
}
