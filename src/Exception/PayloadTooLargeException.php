<?php

declare(strict_types=1);

/**
 * PayloadTooLargeException
 *
 * Thrown when the request payload exceeds the server's size limit.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Exception;

use Throwable;

final class PayloadTooLargeException extends HttpException
{
    /**
     * Create a new Payload Too Large (413) exception.
     *
     * @param string $message The exception message
     * @param string $detail A human-readable explanation specific to this occurrence
     * @param string $type A URI reference that identifies the problem type
     * @param string $instance A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members to include in the problem details
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = '',
        string $detail = '',
        string $type = '',
        string $instance = '',
        array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(413, $message, $detail, $type, $instance, $extensions, $previous);
    }
}
