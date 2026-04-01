<?php

declare(strict_types=1);

/**
 * UnprocessableEntityException
 *
 * Thrown when the server understands the request but cannot process the contained instructions,
 * typically due to validation errors.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Exception;

use Throwable;

final class UnprocessableEntityException extends HttpException
{
    /**
     * Create a new Unprocessable Entity (422) exception.
     *
     * @param array<string, list<string>> $errors The validation errors keyed by field name
     * @param string $message The exception message
     * @param string $detail A human-readable explanation specific to this occurrence
     * @param string $type A URI reference that identifies the problem type
     * @param string $instance A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members to include in the problem details
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        private readonly array $errors = [],
        string $message = '',
        string $detail = '',
        string $type = '',
        string $instance = '',
        array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(422, $message, $detail, $type, $instance, $extensions, $previous);
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
