<?php

declare(strict_types=1);

/**
 * Uri
 *
 * Standalone, immutable PSR-7 UriInterface implementation (RFC 3986). Parses,
 * normalizes, and percent-encodes URI components. Scheme and host are
 * lowercased; default ports are omitted; percent-encoding is idempotent
 * (already-encoded sequences are preserved).
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

final class Uri implements UriInterface
{
    /**
     * @var array<string, int> Default ports per scheme (omitted from authority)
     */
    private const array DEFAULT_PORTS = [
        'http'   => 80,
        'https'  => 443,
        'ftp'    => 21,
        'gopher' => 70,
        'nntp'   => 119,
        'news'   => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap'   => 143,
        'pop'    => 110,
        'ldap'   => 389,
    ];

    private const string CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    private const string CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    private string $scheme = '';

    private string $userInfo = '';

    private string $host = '';

    private ?int $port = null;

    private string $path = '';

    private string $query = '';

    private string $fragment = '';

    /**
     * Parse a URI string into its components.
     *
     * @param string $uri A URI string to parse (empty for a blank URI)
     *
     * @throws InvalidArgumentException When the URI cannot be parsed
     */
    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            return;
        }

        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Unable to parse URI: "%s".', $uri));
        }

        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQueryOrFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterQueryOrFragment($parts['fragment']) : '';

        if (isset($parts['user'])) {
            $this->userInfo = $this->encodeComponent($parts['user'], '');

            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $this->encodeComponent($parts['pass'], '');
            }
        }

        $this->removeDefaultPort();
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * The URI path, with multiple leading slashes collapsed to one to prevent
     * XSS / URL-poisoning when the path is used without an authority
     * (CVE-2015-3257).
     */
    public function getPath(): string
    {
        $path = $this->path;

        if ($path !== '' && $path[0] === '/' && isset($path[1]) && $path[1] === '/') {
            $path = '/' . ltrim($path, '/');
        }

        return $path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @param string $scheme The scheme (lowercased; empty removes it)
     */
    public function withScheme(string $scheme): static
    {
        $scheme = $this->filterScheme($scheme);

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->removeDefaultPort();

        return $clone;
    }

    /**
     * @param string $user The user name
     * @param string|null $password The password, if any
     */
    public function withUserInfo(string $user, ?string $password = null): static
    {
        $info = $this->encodeComponent($user, '');

        if ($password !== null && $password !== '') {
            $info .= ':' . $this->encodeComponent($password, '');
        }

        $clone = clone $this;
        $clone->userInfo = $info;

        return $clone;
    }

    /**
     * @param string $host The host (lowercased; empty removes it)
     */
    public function withHost(string $host): static
    {
        $clone = clone $this;
        $clone->host = strtolower($host);

        return $clone;
    }

    /**
     * @param int|null $port The port (null removes it; default ports are dropped)
     */
    public function withPort(?int $port): static
    {
        $clone = clone $this;
        $clone->port = $this->filterPort($port);
        $clone->removeDefaultPort();

        return $clone;
    }

    /**
     * @param string $path The path (percent-encoded idempotently)
     */
    public function withPath(string $path): static
    {
        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        return $clone;
    }

    /**
     * @param string $query The query string without leading "?" (encoded idempotently)
     */
    public function withQuery(string $query): static
    {
        $clone = clone $this;
        $clone->query = $this->filterQueryOrFragment($query);

        return $clone;
    }

    /**
     * @param string $fragment The fragment without leading "#" (encoded idempotently)
     */
    public function withFragment(string $fragment): static
    {
        $clone = clone $this;
        $clone->fragment = $this->filterQueryOrFragment($fragment);

        return $clone;
    }

    /**
     * The full URI string. Path composition follows RFC 3986: a rootless path
     * with an authority is prefixed with "/", and a "//"-leading path without
     * an authority is reduced to a single leading slash.
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();

        if ($authority !== '' || $this->scheme === 'file') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;

        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '') {
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && $path[1] === '/' && $authority === '') {
                $path = '/' . ltrim($path, '/');
            }

            $uri .= $path;
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Validate and lowercase a scheme.
     *
     * @param string $scheme The raw scheme
     *
     * @throws InvalidArgumentException When the scheme is malformed
     *
     * @return string The normalized scheme
     */
    private function filterScheme(string $scheme): string
    {
        $scheme = strtolower($scheme);

        if ($scheme !== '' && preg_match('/^[a-z][a-z0-9+.-]*$/', $scheme) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid URI scheme: "%s".', $scheme));
        }

        return $scheme;
    }

    /**
     * Validate a port number.
     *
     * @param int|null $port The raw port
     *
     * @throws InvalidArgumentException When the port is out of range
     *
     * @return int|null The validated port
     */
    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535.', $port));
        }

        return $port;
    }

    /**
     * Drop the port when it matches the scheme's default.
     *
     * @return void
     */
    private function removeDefaultPort(): void
    {
        if ($this->port !== null
            && isset(self::DEFAULT_PORTS[$this->scheme])
            && self::DEFAULT_PORTS[$this->scheme] === $this->port
        ) {
            $this->port = null;
        }
    }

    /**
     * Percent-encode a path, preserving valid existing encodings and "/", ":", "@".
     *
     * @param string $path The raw path
     *
     * @return string The encoded path
     */
    private function filterPath(string $path): string
    {
        return $this->encodeComponent($path, ':@\/');
    }

    /**
     * Percent-encode a query or fragment, preserving valid encodings and "/", ":", "@", "?".
     *
     * @param string $value The raw query or fragment
     *
     * @return string The encoded value
     */
    private function filterQueryOrFragment(string $value): string
    {
        return $this->encodeComponent($value, ':@\/\?');
    }

    /**
     * Idempotently percent-encode a URI component: encode disallowed characters
     * but leave already-encoded (%XX) sequences untouched.
     *
     * @param string $value The raw component
     * @param string $extraAllowed Extra allowed characters for the regex class (pre-escaped)
     *
     * @return string The encoded component
     */
    private function encodeComponent(string $value, string $extraAllowed): string
    {
        $pattern = '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . $extraAllowed . ']++|%(?![A-Fa-f0-9]{2}))/';

        $encoded = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $value,
        );

        return $encoded ?? $value;
    }
}
