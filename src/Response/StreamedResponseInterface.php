<?php

declare(strict_types=1);

/**
 * StreamedResponseInterface
 *
 * A response whose body is produced incrementally at send time rather than buffered.
 * Server transports (server-swoole / server-sapi) detect this interface and pump the
 * chunks through the connection's writer over the request's lifetime — enabling large
 * or generated output and Server-Sent Events. This is the seam that lets phpdot/http
 * own streaming while the transports stay decoupled from the concrete class.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Response;

use Psr\Http\Message\ResponseInterface;

interface StreamedResponseInterface extends ResponseInterface
{
    /**
     * Emit the body incrementally, sending each chunk through $write. The transport
     * supplies a writer bound to the connection (e.g. Swoole's Response::write());
     * the producer may loop (with Co::sleep() between chunks, e.g. an SSE stream)
     * until the client disconnects, at which point $write returns false.
     *
     * @param callable(string): bool $write Writes a chunk; returns false if the client disconnected.
     *
     * @return void
     */
    public function emit(callable $write): void;
}
