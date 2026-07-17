<?php

declare(strict_types=1);

/**
 * SseWriter
 *
 * Formats and emits Server-Sent Events (SSE) frames over a StreamedResponse's chunk
 * writer, per the WHATWG event-stream format. Each send() produces a well-formed
 * `id:`/`event:`/`retry:`/`data:` frame terminated by a blank line; multi-line
 * payloads become multiple `data:` lines. comment() emits a heartbeat — send one
 * periodically to keep the connection alive through proxy/Cloudflare idle timeouts.
 * Every write returns false once the client disconnects, so producers can stop.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Response;

use Closure;

final class SseWriter
{
    /**
     * @var Closure(string): bool
     */
    private readonly Closure $write;

    /**
     * Wrap the underlying chunk sink for a Server-Sent Events stream.
     *
     * @param callable(string): bool $write Underlying chunk writer; returns false if the client disconnected.
     */
    public function __construct(callable $write)
    {
        $this->write = $write(...);
    }

    /**
     * Send a single SSE frame.
     *
     * @param string $data The event payload (multi-line permitted)
     * @param string|null $event Optional event name (the browser's addEventListener type)
     * @param string|null $id Optional event id (sets the client's Last-Event-ID for resumption)
     * @param int|null $retry Optional client reconnection delay in milliseconds
     *
     * @return bool False if the client has disconnected
     */
    public function send(string $data, ?string $event = null, ?string $id = null, ?int $retry = null): bool
    {
        $frame = '';

        if ($id !== null) {
            $frame .= "id: {$id}\n";
        }

        if ($event !== null) {
            $frame .= "event: {$event}\n";
        }

        if ($retry !== null) {
            $frame .= "retry: {$retry}\n";
        }

        $lines = preg_split('/\r\n|\r|\n/', $data);

        if ($lines === false) {
            $lines = [$data];
        }

        foreach ($lines as $line) {
            $frame .= "data: {$line}\n";
        }

        $frame .= "\n";

        return ($this->write)($frame);
    }

    /**
     * Send a comment line — a keep-alive heartbeat (ignored by clients) that keeps the
     * connection open through reverse-proxy / Cloudflare idle timeouts.
     *
     * @param string $text The comment text
     *
     * @return bool False if the client has disconnected
     */
    public function comment(string $text = ''): bool
    {
        return ($this->write)(": {$text}\n\n");
    }
}
