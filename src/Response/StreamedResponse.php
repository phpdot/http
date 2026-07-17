<?php

declare(strict_types=1);

/**
 * StreamedResponse
 *
 * A response whose body is produced incrementally at send time rather than held in
 * memory — for large/generated output and Server-Sent Events. A full PSR-7 response
 * (status + headers), but its body is a producer callback rather than a materialized
 * stream. The server emitter runs the producer, mapping each chunk to the transport's
 * write (Swoole's Response::write() over a long-lived coroutine). Implements
 * StreamedResponseInterface so transports detect it without coupling to this class.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Response;

use Closure;
use PHPdot\Http\Message\Response;

final class StreamedResponse extends Response implements StreamedResponseInterface
{
    /**
     * @var Closure(callable(string): bool): void
     */
    private readonly Closure $producer;

    /**
     * Create a streamed response whose body is produced on demand.
     *
     * The producer emits body chunks through the writer it is handed; the
     * writer returns false once the client has disconnected.
     *
     * @param Closure(callable(string): bool): void $producer
     * @param int $status The HTTP status code
     * @param array<string, string|string[]> $headers Response headers
     */
    public function __construct(Closure $producer, int $status = 200, array $headers = [])
    {
        parent::__construct($status, $headers);
        $this->producer = $producer;
    }

    public function emit(callable $write): void
    {
        ($this->producer)($write);
    }
}
