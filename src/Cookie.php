<?php

declare(strict_types=1);

/**
 * Cookie
 *
 * Immutable value object representing an HTTP cookie per RFC 6265.
 * All with*() methods return new instances via clone.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class Cookie
{
    /**
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param DateTimeInterface|null $expires The expiration date, or null for session cookie
     * @param int|null $maxAge The max age in seconds, or null if not set
     * @param string $path The cookie path
     * @param string $domain The cookie domain
     * @param bool $secure Whether the cookie is secure-only
     * @param bool $httpOnly Whether the cookie is HTTP-only
     * @param string $sameSite The SameSite attribute value
     * @param bool $partitioned Whether the cookie is partitioned
     *
     * @throws InvalidArgumentException If the cookie name or attribute values are invalid
     */
    public function __construct(
        private readonly string $name,
        private readonly string $value = '',
        private readonly ?DateTimeInterface $expires = null,
        private readonly ?int $maxAge = null,
        private readonly string $path = '/',
        private readonly string $domain = '',
        private readonly bool $secure = true,
        private readonly bool $httpOnly = true,
        private readonly string $sameSite = 'Lax',
        private readonly bool $partitioned = false,
    ) {
        self::validateName($name);

        if ($path !== '') {
            self::validateAttributeValue($path, 'path');
        }

        if ($domain !== '') {
            self::validateAttributeValue($domain, 'domain');
        }
    }

    /**
     * Get the cookie name.
     *
     * @return string The cookie name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the cookie value.
     *
     * @return string The cookie value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the expiration date.
     *
     * @return DateTimeInterface|null The expiration date, or null for session cookie
     */
    public function getExpires(): ?DateTimeInterface
    {
        return $this->expires;
    }

    /**
     * Get the max age in seconds.
     *
     * @return int|null The max age, or null if not set
     */
    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    /**
     * Get the cookie path.
     *
     * @return string The cookie path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the cookie domain.
     *
     * @return string The cookie domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Check if the cookie is secure-only.
     *
     * @return bool True if the cookie is secure-only
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Check if the cookie is HTTP-only.
     *
     * @return bool True if the cookie is HTTP-only
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Get the SameSite attribute value.
     *
     * @return string The SameSite value
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Check if the cookie is partitioned.
     *
     * @return bool True if the cookie is partitioned
     */
    public function isPartitioned(): bool
    {
        return $this->partitioned;
    }

    /**
     * Check if the cookie has expired.
     *
     * @return bool True if the cookie expiration date is in the past
     */
    public function isExpired(): bool
    {
        if ($this->expires === null) {
            return false;
        }

        return $this->expires < new DateTimeImmutable();
    }

    /**
     * Return a new instance with the given value.
     *
     * @param string $value The cookie value
     *
     * @return self A new Cookie instance
     */
    public function withValue(string $value): self
    {
        return new self(
            name: $this->name,
            value: $value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given expiration date.
     *
     * @param DateTimeInterface|null $expires The expiration date, or null for session cookie
     *
     * @return self A new Cookie instance
     */
    public function withExpires(?DateTimeInterface $expires): self
    {
        return new self(
            name: $this->name,
            value: $this->value,
            expires: $expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given max age.
     *
     * @param int|null $maxAge The max age in seconds, or null to unset
     *
     * @return self A new Cookie instance
     */
    public function withMaxAge(?int $maxAge): self
    {
        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given path.
     *
     * @param string $path The cookie path
     *
     *
     * @throws InvalidArgumentException If the path contains control characters
     * @return self A new Cookie instance
     */
    public function withPath(string $path): self
    {
        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given domain.
     *
     * @param string $domain The cookie domain
     *
     *
     * @throws InvalidArgumentException If the domain contains control characters
     * @return self A new Cookie instance
     */
    public function withDomain(string $domain): self
    {
        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given secure flag.
     *
     * @param bool $secure Whether the cookie is secure-only
     *
     *
     * @throws InvalidArgumentException If unsetting Secure while SameSite=None or Partitioned is set
     * @return self A new Cookie instance
     */
    public function withSecure(bool $secure): self
    {
        if (!$secure && ($this->sameSite === 'None' || $this->partitioned)) {
            throw new InvalidArgumentException(
                'Cannot remove Secure flag while SameSite=None or Partitioned is set.',
            );
        }

        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given HTTP-only flag.
     *
     * @param bool $httpOnly Whether the cookie is HTTP-only
     *
     * @return self A new Cookie instance
     */
    public function withHttpOnly(bool $httpOnly): self
    {
        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given SameSite attribute.
     *
     * @param string $sameSite The SameSite value (Strict, Lax, or None)
     *
     *
     * @throws InvalidArgumentException If the value is invalid or None without Secure
     * @return self A new Cookie instance
     */
    public function withSameSite(string $sameSite): self
    {
        $normalized = ucfirst(strtolower($sameSite));

        if ($normalized !== 'Strict' && $normalized !== 'Lax' && $normalized !== 'None') {
            throw new InvalidArgumentException(
                sprintf('Invalid SameSite value "%s". Must be Strict, Lax, or None.', $sameSite),
            );
        }

        if ($normalized === 'None' && !$this->secure) {
            throw new InvalidArgumentException('SameSite=None requires the Secure attribute.');
        }

        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $normalized,
            partitioned: $this->partitioned,
        );
    }

    /**
     * Return a new instance with the given partitioned flag.
     *
     * @param bool $partitioned Whether the cookie is partitioned
     *
     *
     * @throws InvalidArgumentException If partitioned is true without Secure
     * @return self A new Cookie instance
     */
    public function withPartitioned(bool $partitioned): self
    {
        if ($partitioned && !$this->secure) {
            throw new InvalidArgumentException('Partitioned cookies require the Secure attribute.');
        }

        return new self(
            name: $this->name,
            value: $this->value,
            expires: $this->expires,
            maxAge: $this->maxAge,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $partitioned,
        );
    }

    /**
     * Build the Set-Cookie header string value.
     *
     * @return string The formatted Set-Cookie header value
     */
    public function toHeaderString(): string
    {
        $parts = [sprintf('%s=%s', $this->name, rawurlencode($this->value))];

        if ($this->path !== '') {
            $parts[] = sprintf('Path=%s', $this->path);
        }

        if ($this->domain !== '') {
            $parts[] = sprintf('Domain=%s', $this->domain);
        }

        if ($this->maxAge !== null) {
            $parts[] = sprintf('Max-Age=%d', $this->maxAge);
        }

        if ($this->expires !== null) {
            $parts[] = sprintf('Expires=%s', gmdate('D, d M Y H:i:s T', $this->expires->getTimestamp()));
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== '') {
            $parts[] = sprintf('SameSite=%s', $this->sameSite);
        }

        if ($this->partitioned) {
            $parts[] = 'Partitioned';
        }

        return implode('; ', $parts);
    }

    /**
     * Parse a Set-Cookie header string into a Cookie instance.
     *
     * @param string $header The Set-Cookie header value
     *
     *
     * @throws InvalidArgumentException If the header cannot be parsed
     * @return self A new Cookie instance
     */
    public static function fromHeaderString(string $header): self
    {
        $segments = explode(';', $header);
        $firstSegment = trim(array_shift($segments));

        $equalsPos = strpos($firstSegment, '=');

        if ($equalsPos === false) {
            throw new InvalidArgumentException('Invalid Set-Cookie header: missing name=value pair.');
        }

        $name = substr($firstSegment, 0, $equalsPos);
        $value = rawurldecode(substr($firstSegment, $equalsPos + 1));

        $expires = null;
        $maxAge = null;
        $path = '/';
        $domain = '';
        $secure = false;
        $httpOnly = false;
        $sameSite = 'Lax';
        $partitioned = false;

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $attrEqualsPos = strpos($segment, '=');

            if ($attrEqualsPos === false) {
                $attrName = strtolower($segment);
                $attrValue = null;
            } else {
                $attrName = strtolower(substr($segment, 0, $attrEqualsPos));
                $attrValue = substr($segment, $attrEqualsPos + 1);
            }

            switch ($attrName) {
                case 'expires':
                    if ($attrValue !== null) {
                        $parsed = DateTimeImmutable::createFromFormat('D, d M Y H:i:s T', $attrValue);

                        if ($parsed !== false) {
                            $expires = $parsed;
                        }
                    }

                    break;
                case 'max-age':
                    if ($attrValue !== null && ctype_digit(ltrim($attrValue, '-'))) {
                        $maxAge = (int) $attrValue;
                    }

                    break;
                case 'path':
                    if ($attrValue !== null) {
                        $path = $attrValue;
                    }

                    break;
                case 'domain':
                    if ($attrValue !== null) {
                        $domain = $attrValue;
                    }

                    break;
                case 'secure':
                    $secure = true;

                    break;
                case 'httponly':
                    $httpOnly = true;

                    break;
                case 'samesite':
                    if ($attrValue !== null) {
                        $normalized = ucfirst(strtolower($attrValue));

                        if ($normalized !== 'Strict' && $normalized !== 'Lax' && $normalized !== 'None') {
                            throw new InvalidArgumentException(
                                sprintf('Invalid SameSite value "%s" in Set-Cookie header.', $attrValue),
                            );
                        }

                        $sameSite = $normalized;
                    }

                    break;
                case 'partitioned':
                    $partitioned = true;

                    break;
            }
        }

        if ($sameSite === 'None' && !$secure) {
            throw new InvalidArgumentException(
                'Invalid Set-Cookie header: SameSite=None requires the Secure attribute.',
            );
        }

        if ($partitioned && !$secure) {
            throw new InvalidArgumentException(
                'Invalid Set-Cookie header: Partitioned requires the Secure attribute.',
            );
        }

        return new self(
            name: $name,
            value: $value,
            expires: $expires,
            maxAge: $maxAge,
            path: $path,
            domain: $domain,
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite,
            partitioned: $partitioned,
        );
    }

    /**
     * Validate the cookie name per RFC 6265.
     *
     * @param string $name The cookie name to validate
     *
     *
     * @throws InvalidArgumentException If the name contains invalid characters
     */
    private static function validateName(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Cookie name must not be empty.');
        }

        if (preg_match('/[\x00-\x1f\x7f()<>@,;:\\\\"\/\[\]?={} \t]/', $name) === 1) {
            throw new InvalidArgumentException(
                sprintf('Cookie name "%s" contains invalid characters.', $name),
            );
        }
    }

    /**
     * Validate a cookie attribute value (path, domain) against header injection.
     *
     * @throws InvalidArgumentException If the value contains control or whitespace characters
     */
    private static function validateAttributeValue(string $value, string $attribute): void
    {
        if (preg_match('/[\x00-\x1f\x7f;]/', $value) === 1) {
            throw new InvalidArgumentException(
                sprintf('Cookie %s contains invalid characters.', $attribute),
            );
        }
    }
}
