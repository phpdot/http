<?php

declare(strict_types=1);

/**
 * HtmlResponse
 *
 * Convenience response for HTML content.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

final class HtmlResponse extends Response
{
    /**
     * @param string $html The HTML content
     * @param int $status The HTTP status code
     * @param array<string, string|string[]> $headers Additional response headers
     */
    public function __construct(
        string $html,
        int $status = 200,
        array $headers = [],
    ) {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        parent::__construct($status, $headers, $html);
    }
}
