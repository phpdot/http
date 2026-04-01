<?php

declare(strict_types=1);

/**
 * HttpException
 *
 * Base HTTP exception following RFC 9457 Problem Details for HTTP APIs.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Exception;

use PHPdot\Http\StatusText;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /**
     * Create a new HTTP exception.
     *
     * @param int $statusCode The HTTP status code
     * @param string $message The exception message
     * @param string $detail A human-readable explanation specific to this occurrence
     * @param string $type A URI reference that identifies the problem type
     * @param string $instance A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members to include in the problem details
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly string $detail = '',
        private readonly string $type = '',
        private readonly string $instance = '',
        private readonly array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : StatusText::get($statusCode), $statusCode, $previous);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the human-readable detail explanation.
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * Get the problem type URI reference.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the specific occurrence URI reference.
     */
    public function getInstance(): string
    {
        return $this->instance;
    }

    /**
     * Get the additional extension members.
     *
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Convert the exception to an RFC 9457 Problem Details array.
     *
     * @return array<string, mixed>
     */
    public function toProblemDetails(): array
    {
        $details = [
            'type' => $this->type !== '' ? $this->type : 'about:blank',
            'title' => StatusText::get($this->statusCode),
            'status' => $this->statusCode,
            'detail' => $this->detail,
            'instance' => $this->instance,
            ...$this->extensions,
        ];

        return array_filter($details, static fn(mixed $value): bool => $value !== '');
    }
}
