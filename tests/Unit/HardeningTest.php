<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\Message\Stream;
use PHPdot\Http\Message\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Adversarial / production-readiness hardening beyond the PSR-7 conformance suite:
 * URI idempotency over nasty inputs, and stream resource lifecycle safety.
 */
final class HardeningTest extends TestCase
{
    /** @return list<array{string}> */
    public static function nastyUris(): array
    {
        return [
            'ipv6-port'          => ['http://[::1]:8080/path'],
            'ipv6-full'          => ['http://[2001:db8::1]/'],
            'encoded-userinfo'   => ['https://user%40name:p%40ss@example.com/a/b?x=1#frag'],
            'protocol-relative'  => ['//protocol-relative/path'],
            'encoded-path-query' => ['http://example.com/a%2Fb/c%20d?q=a%26b&r=c#s%20t'],
            'double-slashes'     => ['http://example.com//double///slashes'],
            'empty-query'        => ['http://example.com?'],
            'empty-fragment'     => ['http://example.com#'],
            'port-zero'          => ['http://example.com:0/'],
            'port-max'           => ['http://example.com:65535/'],
            'rootless-path'      => ['just/a/path'],
            'uppercase'          => ['HTTP://EXAMPLE.COM/PATH'],
            'mailto'             => ['mailto:foo@bar.com'],
            'zeros'              => ['https://0:0@0:1/0?0#0'],
        ];
    }

    #[Test]
    #[DataProvider('nastyUris')]
    public function uri_string_representation_is_idempotent(string $input): void
    {
        $once = (string) new Uri($input);
        $twice = (string) new Uri($once);

        self::assertSame($once, $twice, "Uri not idempotent for input: {$input}");
    }

    #[Test]
    public function ipv6_host_brackets_survive_parse_authority_and_tostring(): void
    {
        $uri = new Uri('http://[::1]:8080/path');

        self::assertSame('[::1]', $uri->getHost());
        self::assertSame(8080, $uri->getPort());
        self::assertSame('[::1]:8080', $uri->getAuthority());
        self::assertSame('http://[::1]:8080/path', (string) $uri);
    }

    #[Test]
    public function uppercase_scheme_and_host_lowercased_path_preserved(): void
    {
        $uri = new Uri('HTTP://EXAMPLE.COM/PATH');

        self::assertSame('http', $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertSame('/PATH', $uri->getPath());
    }

    #[Test]
    public function stream_detach_leaves_it_inert(): void
    {
        $stream = Stream::create('hello');
        $resource = $stream->detach();

        self::assertIsResource($resource);
        self::assertNull($stream->detach());
        self::assertSame('', (string) $stream);
        self::assertTrue($stream->eof());
        self::assertNull($stream->getSize());

        fclose($resource);
    }

    #[Test]
    public function stream_double_close_is_safe(): void
    {
        $stream = Stream::create('data');
        $stream->close();
        $stream->close();

        self::assertSame('', (string) $stream);
    }

    #[Test]
    public function stream_read_after_close_throws(): void
    {
        $stream = Stream::create('data');
        $stream->close();

        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }
}
