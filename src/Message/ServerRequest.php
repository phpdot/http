<?php

declare(strict_types=1);

/**
 * ServerRequest
 *
 * Standalone, immutable PSR-7 ServerRequestInterface implementation. Carries the
 * server-side view of an incoming request: method, URI, headers, body, plus
 * server params, cookies, query, parsed body, uploaded files, and attributes.
 *
 * Message-level behavior (protocol/headers/body) comes from MessageTrait. Every
 * with*() returns a clone; instances are per-request (one coroutine).
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;

    private string $method;

    private ?string $requestTarget = null;

    private UriInterface $uri;

    /**
     * @var array<array-key, mixed>
     */
    private array $serverParams;

    /**
     * @var array<array-key, mixed>
     */
    private array $cookieParams = [];

    /**
     * @var array<array-key, mixed>
     */
    private array $queryParams = [];

    /**
     * @var array<array-key, mixed>
     */
    private array $uploadedFiles = [];

    /**
     * @var array<array-key, mixed>|object|null
     */
    private array|object|null $parsedBody = null;

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Create a server request from its method, URI, headers, body, and server parameters.
     *
     * @param string $method The HTTP method
     * @param UriInterface|string $uri The request URI
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $headers Request headers
     * @param StreamInterface|string|null $body The request body
     * @param string $version The HTTP protocol version
     * @param array<array-key, mixed> $serverParams Server parameters (typically $_SERVER)
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
        array $serverParams = [],
    ) {
        $this->method = $this->filterMethod($method);
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->serverParams = $serverParams;
        $this->protocolVersion = $version;

        $this->setHeaders($headers);

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        if ($body instanceof StreamInterface) {
            $this->body = $body;
        } else {
            $this->body = Stream::create($body ?? '');
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();

        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = $this->filterMethod($method);

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $clone->updateHostFromUri();
        }

        return $clone;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array<array-key, mixed> $cookies
     */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array<array-key, mixed> $query
     */
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<array-key, mixed> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /**
     * @return array<array-key, mixed>|object|null
     */
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    /**
     * @param mixed $data The deserialized body data; must be an array, object, or null
     */
    public function withParsedBody($data): static
    {
        if ($data !== null && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Parsed body must be an array, object, or null.');
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    /**
     * Validate an HTTP method token (RFC 7230). Case is preserved.
     *
     * @param string $method The HTTP method
     *
     * @throws InvalidArgumentException When the method is empty or malformed
     *
     * @return string The validated method
     */
    private function filterMethod(string $method): string
    {
        if ($method === '' || preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $method) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid HTTP method.', $method));
        }

        return $method;
    }

    /**
     * Set the Host header from the current URI (host[:port]), replacing any existing one.
     *
     * @return void
     */
    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        $port = $this->uri->getPort();

        if ($port !== null) {
            $host .= ':' . $port;
        }

        unset($this->headers['host'], $this->headerNames['host']);
        $this->headerNames = ['host' => 'Host'] + $this->headerNames;
        $this->headers = ['host' => [$host]] + $this->headers;
    }
}
