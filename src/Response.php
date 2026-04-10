<?php

declare(strict_types=1);

/**
 * Response
 *
 * Immutable decorator over PSR-7 ResponseInterface providing a rich,
 * expressive API for building HTTP responses. Implements ResponseInterface
 * itself, delegating all PSR-7 methods to the inner response while adding
 * convenience methods for headers, cookies, and caching.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    /**
     * @param ResponseInterface $response The inner PSR-7 response
     */
    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    // =========================================================================
    // PSR-7 MessageInterface delegation
    // =========================================================================

    /**
     * Get the HTTP protocol version.
     *
     * @return string The HTTP protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * @param string $version The HTTP protocol version
     *
     * @return static A new instance with the given protocol version
     */
    public function withProtocolVersion(string $version): static
    {
        return new self($this->response->withProtocolVersion($version));
    }

    /**
     * Retrieve all message header values.
     *
     * @return array<string, list<string>> An associative array of headers
     */
    public function getHeaders(): array
    {
        $headers = $this->response->getHeaders();
        $result = [];

        foreach ($headers as $name => $values) {
            $result[(string) $name] = array_values($values);
        }

        return $result;
    }

    /**
     * Check if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name
     *
     * @return bool True if the header exists
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * Retrieve a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name
     *
     * @return list<string> An array of string values for the header
     */
    public function getHeader(string $name): array
    {
        return array_values($this->response->getHeader($name));
    }

    /**
     * Retrieve a comma-separated string of the values for a single header.
     *
     * @param string $name Case-insensitive header field name
     *
     * @return string A concatenated string of header values
     */
    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string $name Case-insensitive header field name
     * @param string|string[] $value Header value(s)
     *
     * @return static A new instance with the given header
     */
    public function withHeader(string $name, $value): static
    {
        return new self($this->response->withHeader($name, $value));
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string $name Case-insensitive header field name
     * @param string|string[] $value Header value(s)
     *
     * @return static A new instance with the appended header
     */
    public function withAddedHeader(string $name, $value): static
    {
        return new self($this->response->withAddedHeader($name, $value));
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name
     *
     * @return static A new instance without the header
     */
    public function withoutHeader(string $name): static
    {
        return new self($this->response->withoutHeader($name));
    }

    /**
     * Get the body of the message.
     *
     * @return StreamInterface The body as a stream
     */
    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body The message body
     *
     * @return static A new instance with the given body
     */
    public function withBody(StreamInterface $body): static
    {
        return new self($this->response->withBody($body));
    }

    // =========================================================================
    // PSR-7 ResponseInterface delegation
    // =========================================================================

    /**
     * Get the response status code.
     *
     * @return int The status code
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * @param int $code The 3-digit integer result code
     * @param string $reasonPhrase The reason phrase
     *
     * @return static A new instance with the given status
     */
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        return new self($this->response->withStatus($code, $reasonPhrase));
    }

    /**
     * Get the response reason phrase.
     *
     * @return string The reason phrase
     */
    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
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
        return new self($this->response->withAddedHeader('Set-Cookie', $cookie->toHeaderString()));
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

        return new self($this->response->withAddedHeader('Set-Cookie', $cookie->toHeaderString()));
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
            return new self($this->response->withHeader('Cache-Control', 'no-store, no-cache'));
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

        return new self($this->response->withHeader('Cache-Control', implode(', ', $directives)));
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

        return new self($this->response->withHeader('ETag', $value));
    }

    /**
     * Check if the response is informational (1xx).
     *
     * @return bool True if status is 1xx
     */
    public function isInformational(): bool
    {
        return $this->response->getStatusCode() >= 100 && $this->response->getStatusCode() < 200;
    }

    /**
     * Check if the response is successful (2xx).
     *
     * @return bool True if status is 2xx
     */
    public function isSuccessful(): bool
    {
        return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300;
    }

    /**
     * Check if the response is a redirection (3xx).
     *
     * @return bool True if status is 3xx
     */
    public function isRedirection(): bool
    {
        return $this->response->getStatusCode() >= 300 && $this->response->getStatusCode() < 400;
    }

    /**
     * Check if the response is a client error (4xx).
     *
     * @return bool True if status is 4xx
     */
    public function isClientError(): bool
    {
        return $this->response->getStatusCode() >= 400 && $this->response->getStatusCode() < 500;
    }

    /**
     * Check if the response is a server error (5xx).
     *
     * @return bool True if status is 5xx
     */
    public function isServerError(): bool
    {
        return $this->response->getStatusCode() >= 500 && $this->response->getStatusCode() < 600;
    }

    /**
     * Check if the response indicates success (not 4xx or 5xx).
     *
     * @return bool True if status is not 4xx or 5xx
     */
    public function isOk(): bool
    {
        return $this->response->getStatusCode() < 400;
    }
}
