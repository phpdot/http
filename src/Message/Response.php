<?php

declare(strict_types=1);

/**
 * Response
 *
 * Standalone PSR-7 ResponseInterface implementation. Create responses directly
 * without factories. Immutable — all with*() methods return a new instance.
 * Message-level behavior (protocol version, headers, body) comes from MessageTrait.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Message;

use InvalidArgumentException;
use PHPdot\Http\Cookie\Cookie;
use PHPdot\Http\Support\StatusText;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use MessageTrait;

    private int $statusCode;

    private string $reasonPhrase;

    /**
     * Create a response with the given status, headers, body, and protocol version.
     *
     * @param int $status The HTTP status code
     * @param array<string, string|string[]> $headers Response headers
     * @param string|StreamInterface $body The response body
     * @param string $version The HTTP protocol version
     * @param string $reason The reason phrase (auto-resolved from status if empty)
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        string|StreamInterface $body = '',
        string $version = '1.1',
        string $reason = '',
    ) {
        $this->statusCode = $status;
        $this->reasonPhrase = $reason !== '' ? $reason : StatusText::get($status);
        $this->protocolVersion = $version;
        $this->setHeaders($headers);
        $this->body = $body instanceof StreamInterface ? $body : Stream::create($body);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $this->assertStatusCode($code);

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : StatusText::get($code);

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Add a Set-Cookie header to the response.
     *
     * @param Cookie $cookie The cookie to set
     *
     * @return static A new instance with the cookie header added
     */
    public function withCookie(Cookie $cookie): static
    {
        return $this->withAddedHeader('Set-Cookie', $cookie->toHeaderString());
    }

    /**
     * Add an expired Set-Cookie header to remove a cookie.
     *
     * @param string $name The cookie name to remove
     * @param string $path The cookie path
     * @param string $domain The cookie domain
     *
     * @return static A new instance with the expired cookie header
     */
    public function withoutCookie(string $name, string $path = '/', string $domain = ''): static
    {
        $cookie = new Cookie(
            name: $name,
            value: '',
            maxAge: -1,
            path: $path,
            domain: $domain,
        );

        return $this->withAddedHeader('Set-Cookie', $cookie->toHeaderString());
    }

    /**
     * Add Cache-Control headers to the response.
     *
     * @param int $maxAge The max-age value in seconds
     * @param bool $public Whether the response is publicly cacheable
     * @param bool $mustRevalidate Whether caches must revalidate
     * @param bool $immutable Whether the response is immutable
     * @param bool $noStore Whether the response must not be stored
     *
     * @return static A new instance with Cache-Control header
     */
    public function withCache(
        int $maxAge,
        bool $public = false,
        bool $mustRevalidate = false,
        bool $immutable = false,
        bool $noStore = false,
    ): static {
        if ($noStore) {
            return $this->withHeader('Cache-Control', 'no-store, no-cache');
        }

        $directives = [];

        if ($public) {
            $directives[] = 'public';
        } else {
            $directives[] = 'private';
        }

        $directives[] = sprintf('max-age=%d', $maxAge);

        if ($mustRevalidate) {
            $directives[] = 'must-revalidate';
        }

        if ($immutable) {
            $directives[] = 'immutable';
        }

        return $this->withHeader('Cache-Control', implode(', ', $directives));
    }

    /**
     * Add an ETag header to the response.
     *
     * @param string $etag The ETag value (without quotes)
     * @param bool $weak Whether to use a weak ETag
     *
     * @return static A new instance with ETag header
     */
    public function withEtag(string $etag, bool $weak = false): static
    {
        $value = $weak ? sprintf('W/"%s"', $etag) : sprintf('"%s"', $etag);

        return $this->withHeader('ETag', $value);
    }

    /**
     * Check if the response is informational (1xx).
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Check if the response is successful (2xx).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the response is a redirection (3xx).
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if the response is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if the response is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if the response indicates success (not 4xx or 5xx).
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->statusCode < 400;
    }

    /**
     * Assert an HTTP status code is within the valid 100-599 range.
     *
     * @param int $code The status code
     *
     * @throws InvalidArgumentException When the code is out of range
     *
     * @return void
     */
    private function assertStatusCode(int $code): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(sprintf('Invalid status code "%d"; must be between 100 and 599.', $code));
        }
    }
}
