<?php

declare(strict_types=1);

/**
 * RedirectResponse
 *
 * Convenience response for HTTP redirects.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

final class RedirectResponse extends Response
{
    /**
     * @param string $url The URL to redirect to
     * @param int $status The HTTP status code (default 302 Found)
     * @param array<string, string|string[]> $headers Additional response headers
     */
    public function __construct(
        string $url,
        int $status = 302,
        array $headers = [],
    ) {
        $headers['Location'] = $url;

        parent::__construct($status, $headers);
    }
}
