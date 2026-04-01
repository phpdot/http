<?php

declare(strict_types=1);

/**
 * ServiceUnavailableException
 *
 * Thrown when the server is currently unable to handle the request due to temporary overloading or maintenance.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Exception;

use Throwable;

final class ServiceUnavailableException extends HttpException
{
    /**
     * Create a new Service Unavailable (503) exception.
     *
     * @param int $retryAfter The number of seconds the client should wait before retrying
     * @param string $message The exception message
     * @param string $detail A human-readable explanation specific to this occurrence
     * @param string $type A URI reference that identifies the problem type
     * @param string $instance A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members to include in the problem details
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        private readonly int $retryAfter,
        string $message = '',
        string $detail = '',
        string $type = '',
        string $instance = '',
        array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(503, $message, $detail, $type, $instance, array_merge(['retry_after' => $this->retryAfter], $extensions), $previous);
    }

    /**
     * Get the number of seconds the client should wait before retrying.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
