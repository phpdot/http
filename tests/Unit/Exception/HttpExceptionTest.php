<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit\Exception;

use PHPdot\Http\Exception\BadRequestException;
use PHPdot\Http\Exception\HttpException;
use PHPdot\Http\Exception\MethodNotAllowedException;
use PHPdot\Http\Exception\NotFoundException;
use PHPdot\Http\Exception\ServiceUnavailableException;
use PHPdot\Http\Exception\TooManyRequestsException;
use PHPdot\Http\Exception\UnprocessableEntityException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HttpExceptionTest extends TestCase
{
    #[Test]
    public function http_exception_carries_status_code_and_message(): void
    {
        $exception = new HttpException(500, 'Server error');

        self::assertSame(500, $exception->getStatusCode());
        self::assertSame('Server error', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
    }

    #[Test]
    public function to_problem_details_returns_rfc9457_array(): void
    {
        $exception = new HttpException(
            statusCode: 404,
            message: 'Not found',
            detail: 'User 42 was not found',
            type: 'https://example.com/not-found',
            instance: '/users/42',
        );

        $details = $exception->toProblemDetails();

        self::assertSame('https://example.com/not-found', $details['type']);
        self::assertSame('Not Found', $details['title']);
        self::assertSame(404, $details['status']);
        self::assertSame('User 42 was not found', $details['detail']);
        self::assertSame('/users/42', $details['instance']);
    }

    #[Test]
    public function empty_fields_filtered_from_problem_details(): void
    {
        $exception = new HttpException(500, 'Error');

        $details = $exception->toProblemDetails();

        self::assertSame('about:blank', $details['type']);
        self::assertArrayHasKey('status', $details);
        self::assertArrayNotHasKey('detail', $details);
        self::assertArrayNotHasKey('instance', $details);
    }

    #[Test]
    public function problem_details_includes_extensions(): void
    {
        $exception = new HttpException(
            statusCode: 400,
            extensions: ['traceId' => 'abc-123'],
        );

        $details = $exception->toProblemDetails();

        self::assertSame('abc-123', $details['traceId']);
    }

    #[Test]
    public function bad_request_exception_has_status_400(): void
    {
        $exception = new BadRequestException('Invalid input');

        self::assertSame(400, $exception->getStatusCode());
        self::assertSame('Invalid input', $exception->getMessage());
    }

    #[Test]
    public function not_found_exception_has_status_404(): void
    {
        $exception = new NotFoundException('Resource not found');

        self::assertSame(404, $exception->getStatusCode());
        self::assertSame('Resource not found', $exception->getMessage());
    }

    #[Test]
    public function method_not_allowed_exception_carries_allowed_methods(): void
    {
        $exception = new MethodNotAllowedException(['GET', 'POST'], 'Method not allowed');

        self::assertSame(405, $exception->getStatusCode());
        self::assertSame(['GET', 'POST'], $exception->getAllowedMethods());
        self::assertSame('Method not allowed', $exception->getMessage());
    }

    #[Test]
    public function unprocessable_entity_exception_carries_validation_errors(): void
    {
        $errors = [
            'email' => ['The email field is required.'],
            'name' => ['The name must be at least 3 characters.'],
        ];
        $exception = new UnprocessableEntityException($errors, 'Validation failed');

        self::assertSame(422, $exception->getStatusCode());
        self::assertSame($errors, $exception->getErrors());
        self::assertSame('Validation failed', $exception->getMessage());
    }

    #[Test]
    public function too_many_requests_exception_carries_retry_after(): void
    {
        $exception = new TooManyRequestsException(60, 'Rate limited');

        self::assertSame(429, $exception->getStatusCode());
        self::assertSame(60, $exception->getRetryAfter());
        self::assertSame('Rate limited', $exception->getMessage());
    }

    #[Test]
    public function service_unavailable_exception_carries_retry_after(): void
    {
        $exception = new ServiceUnavailableException(120, 'Under maintenance');

        self::assertSame(503, $exception->getStatusCode());
        self::assertSame(120, $exception->getRetryAfter());
        self::assertSame('Under maintenance', $exception->getMessage());
    }

    #[Test]
    public function http_exception_accessors(): void
    {
        $exception = new HttpException(
            statusCode: 403,
            detail: 'Insufficient permissions',
            type: 'https://example.com/forbidden',
            instance: '/admin/settings',
            extensions: ['scope' => 'admin'],
        );

        self::assertSame('Insufficient permissions', $exception->getDetail());
        self::assertSame('https://example.com/forbidden', $exception->getType());
        self::assertSame('/admin/settings', $exception->getInstance());
        self::assertSame(['scope' => 'admin'], $exception->getExtensions());
    }

    #[Test]
    public function http_exception_supports_previous(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new HttpException(500, 'Wrapper', previous: $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
