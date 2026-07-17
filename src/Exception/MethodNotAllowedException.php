<?php

declare(strict_types=1);

/**
 * MethodNotAllowedException
 *
 * Thrown when the request method is not supported for the target resource.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Exception;

use Throwable;

final class MethodNotAllowedException extends HttpException
{
    /**
     * Create a new Method Not Allowed (405) exception.
     *
     * @param array<int, string> $allowedMethods The list of allowed HTTP methods
     * @param string $message The exception message
     * @param string $detail A human-readable explanation specific to this occurrence
     * @param string $type A URI reference that identifies the problem type
     * @param string $instance A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members to include in the problem details
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        private readonly array $allowedMethods,
        string $message = '',
        string $detail = '',
        string $type = '',
        string $instance = '',
        array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(405, $message, $detail, $type, $instance, array_merge(['allowed_methods' => $this->allowedMethods], $extensions), $previous);
    }

    /**
     * Get the list of allowed HTTP methods.
     *
     * @return array<int, string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
