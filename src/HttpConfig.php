<?php

declare(strict_types=1);

/**
 * Http Config
 *
 * Static configuration for the HTTP layer. Carries trusted-proxy settings
 * (used by Request when deciding whether to honour X-Forwarded-* headers)
 * and a nested CookieConfig (used by Cookie::create() as the baseline).
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
     * @param CookieConfig $cookie Baseline cookie defaults applied to every Cookie::create() call
     */
    public function __construct(
        public array $trustedProxies = [],
        public int $trustedHeaders = 0,
        public CookieConfig $cookie = new CookieConfig(),
    ) {}

    /**
     * Apply the full HTTP configuration to the package's static state.
     *
     * Wires trusted-proxy settings into Request and cookie defaults into
     * Cookie. Call once during worker boot, after the container has built
     * an HttpConfig instance from config/http.php.
     */
    public static function apply(self $config): void
    {
        Request::setTrustedProxies($config->trustedProxies, $config->trustedHeaders);
        Cookie::setConfig($config->cookie);
    }
}
