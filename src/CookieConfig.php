<?php

declare(strict_types=1);

/**
 * Cookie Config
 *
 * Baseline defaults applied by `Cookie::create()`. Set once at boot via
 * `Cookie::setConfig()` (typically called from `HttpConfig::apply()`),
 * then inherited by every cookie unless explicitly overridden via the
 * `with*()` setters.
 *
 * Nested under HttpConfig — phpdot/config's nested DTO hydration handles
 * the typed sub-array under `'cookie' => [...]` in `config/http.php`.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

final readonly class CookieConfig
{
    /**
     * @param bool $secure Send only over HTTPS. Override per-environment via config.
     * @param bool $httpOnly Forbid JavaScript access (document.cookie).
     * @param string $sameSite 'Strict' | 'Lax' | 'None'. None requires Secure.
     * @param string $path Cookie path scope.
     * @param string $domain Cookie domain scope (empty = current host).
     * @param bool $partitioned CHIPS — partition cookie by top-level site. Requires Secure.
     */
    public function __construct(
        public bool $secure = true,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',
        public string $path = '/',
        public string $domain = '',
        public bool $partitioned = false,
    ) {}
}
