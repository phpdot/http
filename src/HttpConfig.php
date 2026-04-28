<?php

declare(strict_types=1);

/**
 * Http Config
 *
 * Static configuration for the HTTP layer. Currently scoped to the
 * trusted-proxy / trusted-headers settings consumed by Request when it
 * decides whether to honour X-Forwarded-* (and Forwarded) headers.
 *
 * Auto-bound by phpdot/config when phpdot/package is installed: the user
 * edits config/http.php to set their proxies; the DTO is hydrated from
 * that file.
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
     */
    public function __construct(
        public array $trustedProxies = [],
        public int $trustedHeaders = 0,
    ) {}
}
