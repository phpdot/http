<?php

declare(strict_types=1);

/**
 * Http Config
 *
 * HTTP-layer configuration DTO. Carries trusted-proxy settings (consumed by
 * Request when deciding whether to honour X-Forwarded-* headers) and a nested
 * CookieConfig (consumed by ResponseFactory::cookie() as the baseline for
 * cookies it builds).
 *
 * Auto-bound by phpdot/config when phpdot/package is installed: the user
 * edits config/http.php; the DTO is hydrated from that file. The nested
 * `cookie` array is recursively hydrated into a typed CookieConfig.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use PHPdot\Container\Attribute\Config;

#[Config('http')]
final readonly class HttpConfig
{
    /**
     * @param list<string> $trustedProxies IPs or CIDR ranges of proxies to trust (e.g. CloudFlare's published ranges)
     * @param int $trustedHeaders Bitmask of Request::HEADER_* constants — pass `Request::HEADER_X_FORWARDED_ALL` for the common set
     * @param CookieConfig $cookie Baseline cookie defaults consumed by ResponseFactory::cookie()
     */
    public function __construct(
        public array $trustedProxies = [],
        public int $trustedHeaders = 0,
        public CookieConfig $cookie = new CookieConfig(),
    ) {}
}
