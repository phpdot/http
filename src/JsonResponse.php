<?php

declare(strict_types=1);

/**
 * JsonResponse
 *
 * Convenience response for JSON content.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

final class JsonResponse extends Response
{
    /**
     * @param mixed $data The data to encode as JSON
     * @param int $status The HTTP status code
     * @param array<string, string|string[]> $headers Additional response headers
     * @param int $options Additional JSON encoding options
     *
     * @throws \JsonException If encoding fails
     */
    public function __construct(
        mixed $data,
        int $status = 200,
        array $headers = [],
        int $options = 0,
    ) {
        $defaultOptions = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $body = json_encode($data, $defaultOptions | $options);

        $headers['Content-Type'] = 'application/json';

        parent::__construct($status, $headers, $body);
    }
}
