<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\Factory\ResponseFactory;
use PHPdot\Http\Response\SseWriter;
use PHPdot\Http\Response\StreamedResponse;
use PHPdot\Http\Response\StreamedResponseInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * StreamedResponse + SSE, verified with a collecting writer (no transport needed).
 * server-swoole maps the writer to Swoole's Response::write().
 */
final class StreamedResponseTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    #[Test]
    public function streamed_response_implements_the_transport_seam(): void
    {
        $response = $this->factory->stream(static function (callable $w): void {
            $w('x');
        });

        // The seam server-swoole / server-sapi detect on.
        self::assertInstanceOf(StreamedResponseInterface::class, $response);
    }

    #[Test]
    public function stream_emits_chunks_in_order(): void
    {
        $chunks = [];
        $write = static function (string $c) use (&$chunks): bool {
            $chunks[] = $c;

            return true;
        };

        $response = $this->factory->stream(static function (callable $w): void {
            $w('one');
            $w('two');
            $w('three');
        }, 200, ['Content-Type' => 'text/plain']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));

        $response->emit($write);

        self::assertSame(['one', 'two', 'three'], $chunks);
    }

    #[Test]
    public function sse_sets_proxy_safe_event_stream_headers(): void
    {
        $response = $this->factory->sse(static function (SseWriter $sse): void {});

        self::assertSame('text/event-stream', $response->getHeaderLine('Content-Type'));
        // no-transform keeps Cloudflare / CDNs from buffering or gzipping the stream.
        self::assertSame('no-cache, no-transform', $response->getHeaderLine('Cache-Control'));
        self::assertSame('keep-alive', $response->getHeaderLine('Connection'));
        self::assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));
    }

    #[Test]
    public function sse_formats_frames_per_the_event_stream_spec(): void
    {
        $out = '';
        $write = static function (string $c) use (&$out): bool {
            $out .= $c;

            return true;
        };

        $response = $this->factory->sse(static function (SseWriter $sse): void {
            $sse->send(data: '{"n":1}', event: 'update', id: '7');
            $sse->send(data: "line1\nline2");
            $sse->comment('keep-alive');
        });

        $response->emit($write);

        self::assertSame(
            "id: 7\nevent: update\ndata: {\"n\":1}\n\n"
            . "data: line1\ndata: line2\n\n"
            . ": keep-alive\n\n",
            $out,
        );
    }

    #[Test]
    public function streamed_response_is_immutable_and_keeps_its_producer(): void
    {
        $response = $this->factory->stream(static function (callable $w): void {
            $w('x');
        });

        $new = $response->withStatus(201)->withHeader('X-Foo', 'bar');

        self::assertNotSame($response, $new);
        self::assertInstanceOf(StreamedResponse::class, $new);
        self::assertSame(201, $new->getStatusCode());
        self::assertSame('bar', $new->getHeaderLine('X-Foo'));

        $chunks = [];
        $new->emit(static function (string $c) use (&$chunks): bool {
            $chunks[] = $c;

            return true;
        });

        self::assertSame(['x'], $chunks);
    }
}
