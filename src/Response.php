<?php

declare(strict_types=1);

/**
 * Response
 *
 * Standalone PSR-7 ResponseInterface implementation. Create responses directly
 * without factories. Immutable — all with*() methods return a new instance.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private int $statusCode;

    private string $reasonPhrase;

    /** @var array<string, list<string>> */
    private array $headers = [];

    /** @var array<string, string> Original header name casing */
    private array $headerNames = [];

    private StreamInterface $body;

    private string $protocolVersion = '1.1';

    /**
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

        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$normalized] = is_array($value) ? array_values($value) : [$value];
        }

        $this->body = $body instanceof StreamInterface ? $body : Stream::create($body);
    }

    // =========================================================================
    // PSR-7 ResponseInterface
    // =========================================================================

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : StatusText::get($code);

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    // =========================================================================
    // PSR-7 MessageInterface
    // =========================================================================

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        $result = [];

        foreach ($this->headers as $normalized => $values) {
            $name = $this->headerNames[$normalized];
            $result[$name] = $values;
        }

        return $result;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @return list<string>
     */
    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);

        return $this->headers[$normalized] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $normalized = strtolower($name);
        $clone = clone $this;
        $clone->headerNames[$normalized] = $name;
        $clone->headers[$normalized] = is_array($value) ? array_values($value) : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $normalized = strtolower($name);
        $clone = clone $this;

        if (!isset($clone->headerNames[$normalized])) {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$normalized] = [];
        }

        $newValues = is_array($value) ? array_values($value) : [$value];
        $clone->headers[$normalized] = array_merge($clone->headers[$normalized], $newValues);

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $normalized = strtolower($name);
        $clone = clone $this;
        unset($clone->headers[$normalized], $clone->headerNames[$normalized]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    // =========================================================================
    // Convenience methods
    // =========================================================================

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
        $cookie = Cookie::create($name, '')
            ->withPath($path)
            ->withMaxAge(-1);

        if ($domain !== '') {
            $cookie = $cookie->withDomain($domain);
        }

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
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Check if the response is successful (2xx).
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the response is a redirection (3xx).
     */
    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if the response is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if the response is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if the response indicates success (not 4xx or 5xx).
     */
    public function isOk(): bool
    {
        return $this->statusCode < 400;
    }
}
