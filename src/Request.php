<?php

declare(strict_types=1);

/**
 * Request
 *
 * Immutable decorator over PSR-7 ServerRequestInterface providing a rich,
 * expressive API for inspecting HTTP requests. Implements ServerRequestInterface
 * itself, delegating all PSR-7 methods to the inner request while adding
 * convenience methods for input access, content negotiation, trusted proxy
 * handling, and more.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use BackedEnum;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class Request implements ServerRequestInterface
{
    public const int HEADER_X_FORWARDED_FOR = 0b00001;
    public const int HEADER_X_FORWARDED_HOST = 0b00010;
    public const int HEADER_X_FORWARDED_PORT = 0b00100;
    public const int HEADER_X_FORWARDED_PROTO = 0b01000;
    public const int HEADER_FORWARDED = 0b10000;
    public const int HEADER_X_FORWARDED_ALL = 0b01111;

    /**
     * @param ServerRequestInterface $request The inner PSR-7 server request
     * @param HttpConfig $config Trusted-proxy / trusted-header settings (defaults to a fresh HttpConfig if not injected)
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly HttpConfig $config = new HttpConfig(),
    ) {}

    /**
     * Get the HTTP protocol version.
     *
     * @return string The HTTP protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
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
        return new self($this->request->withProtocolVersion($version), $this->config);
    }

    /**
     * Retrieve all message header values.
     *
     * @return array<string, list<string>> An associative array of headers
     */
    public function getHeaders(): array
    {
        $headers = $this->request->getHeaders();
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
        return $this->request->hasHeader($name);
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
        return array_values($this->request->getHeader($name));
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
        return $this->request->getHeaderLine($name);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string $name Case-insensitive header field name
     * @param string|string[] $value Header value(s)
     *
     * @return static A new instance with the replaced header
     */
    public function withHeader(string $name, $value): static
    {
        return new self($this->request->withHeader($name, $value), $this->config);
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
        return new self($this->request->withAddedHeader($name, $value), $this->config);
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove
     *
     * @return static A new instance without the header
     */
    public function withoutHeader(string $name): static
    {
        return new self($this->request->withoutHeader($name), $this->config);
    }

    /**
     * Get the body of the message.
     *
     * @return StreamInterface The body as a stream
     */
    public function getBody(): StreamInterface
    {
        return $this->request->getBody();
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
        return new self($this->request->withBody($body), $this->config);
    }

    /**
     * Retrieve the message's request target.
     *
     * @return string The request target
     */
    public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    /**
     * Return an instance with the specific request-target.
     *
     * @param string $requestTarget The request target
     *
     * @return static A new instance with the given request target
     */
    public function withRequestTarget(string $requestTarget): static
    {
        return new self($this->request->withRequestTarget($requestTarget), $this->config);
    }

    /**
     * Retrieve the HTTP method of the request.
     *
     * @return string The request method
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive HTTP method
     *
     * @return static A new instance with the given method
     */
    public function withMethod(string $method): static
    {
        return new self($this->request->withMethod($method), $this->config);
    }

    /**
     * Retrieve the URI instance.
     *
     * @return UriInterface The URI of the request
     */
    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * Return an instance with the provided URI.
     *
     * @param UriInterface $uri New request URI
     * @param bool $preserveHost Preserve the original Host header
     *
     * @return static A new instance with the given URI
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        return new self($this->request->withUri($uri, $preserveHost), $this->config);
    }

    /**
     * Retrieve server parameters.
     *
     * @return array<string, mixed> Server parameters
     */
    public function getServerParams(): array
    {
        $params = $this->request->getServerParams();
        $result = [];

        foreach ($params as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * Retrieve cookies sent by the client.
     *
     * @return array<string, string> Cookie parameters
     */
    public function getCookieParams(): array
    {
        $cookies = $this->request->getCookieParams();
        $result = [];

        foreach ($cookies as $key => $value) {
            $result[(string) $key] = is_string($value) ? $value : '';
        }

        return $result;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array<mixed> $cookies Array of key/value pairs representing cookies
     *
     * @return static A new instance with the given cookies
     */
    public function withCookieParams(array $cookies): static
    {
        return new self($this->request->withCookieParams($cookies), $this->config);
    }

    /**
     * Retrieve query string arguments.
     *
     * @return array<string, mixed> Query string arguments
     */
    public function getQueryParams(): array
    {
        $params = $this->request->getQueryParams();
        $result = [];

        foreach ($params as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array<mixed> $query Query string arguments
     *
     * @return static A new instance with the given query params
     */
    public function withQueryParams(array $query): static
    {
        return new self($this->request->withQueryParams($query), $this->config);
    }

    /**
     * Retrieve normalized file upload data.
     *
     * @return array<string, UploadedFileInterface|array<UploadedFileInterface>> Uploaded files
     */
    public function getUploadedFiles(): array
    {
        $files = $this->request->getUploadedFiles();
        $result = [];

        foreach ($files as $key => $value) {
            $result[(string) $key] = $value;
        }

        /** @var array<string, UploadedFileInterface|array<UploadedFileInterface>> $result */
        return $result;
    }

    /**
     * Return an instance with the specified uploaded files.
     *
     * @param array<mixed> $uploadedFiles Uploaded files
     *
     * @return static A new instance with the given uploaded files
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        return new self($this->request->withUploadedFiles($uploadedFiles), $this->config);
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return array<string, mixed>|object|null The deserialized body parameters
     */
    public function getParsedBody(): array|object|null
    {
        /** @var array<string, mixed>|object|null */
        return $this->request->getParsedBody();
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param array<mixed>|object|null $data The deserialized body data
     *
     * @return static A new instance with the given parsed body
     */
    public function withParsedBody($data): static
    {
        return new self($this->request->withParsedBody($data), $this->config);
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return array<string, mixed> Attributes derived from the request
     */
    public function getAttributes(): array
    {
        $attrs = $this->request->getAttributes();
        $result = [];

        foreach ($attrs as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @param string $name The attribute name
     * @param mixed $default Default value if attribute does not exist
     *
     * @return mixed The attribute value
     */
    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     *
     * @return static A new instance with the given attribute
     */
    public function withAttribute(string $name, $value): static
    {
        return new self($this->request->withAttribute($name, $value), $this->config);
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @param string $name The attribute name to remove
     *
     * @return static A new instance without the attribute
     */
    public function withoutAttribute(string $name): static
    {
        return new self($this->request->withoutAttribute($name), $this->config);
    }

    /**
     * Get the HTTP method, respecting method override on POST requests.
     *
     * On POST requests, checks for a _method field in the parsed body or an
     * X-HTTP-Method-Override header and returns the override value if present.
     *
     * @return string The HTTP method in uppercase
     */
    public function method(): string
    {
        $actual = strtoupper($this->request->getMethod());

        if ($actual !== 'POST') {
            return $actual;
        }

        $parsedBody = $this->request->getParsedBody();

        if (is_array($parsedBody) && isset($parsedBody['_method']) && is_string($parsedBody['_method'])) {
            return strtoupper($parsedBody['_method']);
        }

        $override = $this->request->getHeaderLine('X-HTTP-Method-Override');

        if ($override !== '') {
            return strtoupper($override);
        }

        return $actual;
    }

    /**
     * Get the actual HTTP method without any override.
     *
     * @return string The real HTTP method in uppercase
     */
    public function realMethod(): string
    {
        return strtoupper($this->request->getMethod());
    }

    /**
     * Check if the request method matches the given method.
     *
     * @param string $method The method to compare against
     *
     * @return bool True if the methods match (case-insensitive)
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * Check if this is a GET request.
     *
     * @return bool True if the method is GET
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Check if this is a POST request.
     *
     * @return bool True if the method is POST
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if this is a PUT request.
     *
     * @return bool True if the method is PUT
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * Check if this is a PATCH request.
     *
     * @return bool True if the method is PATCH
     */
    public function isPatch(): bool
    {
        return $this->method() === 'PATCH';
    }

    /**
     * Check if this is a DELETE request.
     *
     * @return bool True if the method is DELETE
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * Check if this is an OPTIONS request.
     *
     * @return bool True if the method is OPTIONS
     */
    public function isOptions(): bool
    {
        return $this->method() === 'OPTIONS';
    }

    /**
     * Check if this is a HEAD request.
     *
     * @return bool True if the method is HEAD
     */
    public function isHead(): bool
    {
        return $this->method() === 'HEAD';
    }

    /**
     * Get a query string parameter or all query parameters.
     *
     * @param string|null $key The parameter name, or null for all parameters
     * @param mixed $default The default value if the key is not found
     *
     * @return mixed The parameter value, default, or all parameters
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        $params = $this->request->getQueryParams();

        if ($key === null) {
            return $params;
        }

        return $params[$key] ?? $default;
    }

    /**
     * Get a value from the parsed request body.
     *
     * @param string $key The parameter name
     * @param mixed $default The default value if the key is not found
     *
     * @return mixed The parameter value or default
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->request->getParsedBody();

        if (is_array($body)) {
            return $body[$key] ?? $default;
        }

        return $default;
    }

    /**
     * Get all input data merged from query and parsed body.
     *
     * Parsed body values take precedence over query parameters.
     *
     * @return array<string, mixed> Merged input data
     */
    public function all(): array
    {
        $query = $this->getQueryParams();
        $body = $this->request->getParsedBody();

        if (is_array($body)) {
            $bodyArray = $body;
        } elseif (is_object($body)) {
            $bodyArray = get_object_vars($body);
        } else {
            $bodyArray = [];
        }

        $result = $query;

        foreach ($bodyArray as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * Get a subset of the input data for the given keys.
     *
     * @param list<string> $keys The keys to include
     *
     * @return array<string, mixed> The filtered input data
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    /**
     * Get all input data except for the given keys.
     *
     * @param list<string> $keys The keys to exclude
     *
     * @return array<string, mixed> The filtered input data
     */
    public function except(array $keys): array
    {
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Check if all given keys are present in the input data.
     *
     * @param string|list<string> $keys A single key or list of keys to check
     *
     * @return bool True if all keys are present
     */
    public function has(string|array $keys): bool
    {
        $all = $this->all();
        $keyList = is_array($keys) ? $keys : [$keys];

        foreach ($keyList as $key) {
            if (!array_key_exists($key, $all)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given keys are present in the input data.
     *
     * @param list<string> $keys The keys to check
     *
     * @return bool True if any key is present
     */
    public function hasAny(array $keys): bool
    {
        $all = $this->all();

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a key is present and its value is not empty.
     *
     * @param string $key The key to check
     *
     * @return bool True if the key is present and its value is not empty
     */
    public function filled(string $key): bool
    {
        $all = $this->all();

        if (!array_key_exists($key, $all)) {
            return false;
        }

        $value = $all[$key];

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null;
    }

    /**
     * Check if a key is missing from the input data.
     *
     * @param string $key The key to check
     *
     * @return bool True if the key is not present
     */
    public function missing(string $key): bool
    {
        return !array_key_exists($key, $this->all());
    }

    /**
     * Get an input value as a string.
     *
     * @param string $key The input key
     * @param string $default The default value
     *
     * @return string The value cast to string, or default
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Get an input value as an integer.
     *
     * @param string $key The input key
     * @param int $default The default value
     *
     * @return int The validated integer value, or default on failure
     */
    public function integer(string $key, int $default = 0): int
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_INT);

        if ($filtered === false) {
            return $default;
        }

        return $filtered;
    }

    /**
     * Get an input value as a float.
     *
     * @param string $key The input key
     * @param float $default The default value
     *
     * @return float The validated float value, or default on failure
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($filtered === false) {
            return $default;
        }

        return $filtered;
    }

    /**
     * Get an input value as a boolean.
     *
     * Truthy values: "1", "true", "on", "yes".
     * Falsy values: "0", "false", "off", "no", "".
     * Any other value returns the default.
     *
     * @param string $key The input key
     * @param bool $default The default value
     *
     * @return bool The boolean value, or default if not recognized
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        $normalized = is_scalar($value) ? strtolower((string) $value) : '';

        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return $default;
    }

    /**
     * Get an input value as an array.
     *
     * @param string $key The input key
     * @param array<string, mixed> $default The default value
     *
     * @return array<string, mixed> The array value, or default if not an array
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->all()[$key] ?? null;

        if (is_array($value)) {
            $result = [];

            foreach ($value as $k => $v) {
                $result[(string) $k] = $v;
            }

            return $result;
        }

        return $default;
    }

    /**
     * Get an input value as a DateTimeImmutable instance.
     *
     * @param string $key The input key
     * @param string|null $format The date format to parse with, or null for automatic parsing
     *
     * @return DateTimeImmutable|null The parsed date, or null on failure
     */
    public function date(string $key, ?string $format = null): ?DateTimeImmutable
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || !is_string($value) || $value === '') {
            return null;
        }

        if ($format !== null) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value);

            return $parsed !== false ? $parsed : null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get an input value as a backed enum instance.
     *
     * @template T of BackedEnum
     *
     * @param string $key The input key
     * @param class-string<T> $enumClass The fully qualified enum class name
     *
     * @return T|null The enum instance, or null if not matched
     */
    public function enum(string $key, string $enumClass): ?BackedEnum
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_subclass_of($enumClass, BackedEnum::class)) {
            return null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $castValue = is_int(($enumClass::cases()[0])->value)
            ? filter_var($value, FILTER_VALIDATE_INT)
            : (string) $value;

        if ($castValue === false) {
            return null;
        }

        return $enumClass::tryFrom($castValue);
    }

    /**
     * Get a route parameter from the request attributes.
     *
     * @param string $key The route parameter name
     * @param mixed $default The default value if not found
     *
     * @return mixed The route parameter value
     */
    public function route(string $key, mixed $default = null): mixed
    {
        return $this->request->getAttribute($key, $default);
    }

    /**
     * Get the first value of a header or the default.
     *
     * @param string $key The case-insensitive header name
     * @param string|null $default The default value if the header is missing
     *
     * @return string|null The first header value, or default
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $values = $this->request->getHeader($key);

        if ($values === []) {
            return $default;
        }

        return $values[0];
    }

    /**
     * Get all values for a header.
     *
     * @param string $key The case-insensitive header name
     *
     * @return list<string> All values for the header
     */
    public function headers(string $key): array
    {
        return array_values($this->request->getHeader($key));
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * @return string|null The token string, or null if not present
     */
    public function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        if ($header === '') {
            return null;
        }

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);

            return $token !== '' ? $token : null;
        }

        return null;
    }

    /**
     * Extract Basic authentication credentials from the Authorization header.
     *
     * @return array{username: string, password: string}|null The credentials, or null
     */
    public function basicCredentials(): ?array
    {
        $header = $this->request->getHeaderLine('Authorization');

        if ($header === '' || !str_starts_with($header, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false) {
            return null;
        }

        $colonPos = strpos($decoded, ':');

        if ($colonPos === false) {
            return null;
        }

        return [
            'username' => substr($decoded, 0, $colonPos),
            'password' => substr($decoded, $colonPos + 1),
        ];
    }

    /**
     * Get the User-Agent header value.
     *
     * @return string The user agent string, or empty string if not present
     */
    public function userAgent(): string
    {
        return $this->request->getHeaderLine('User-Agent');
    }

    /**
     * Get the Content-Type header value without parameters.
     *
     * @return string The content type, or empty string if not present
     */
    public function contentType(): string
    {
        $header = $this->request->getHeaderLine('Content-Type');

        if ($header === '') {
            return '';
        }

        $semicolonPos = strpos($header, ';');

        if ($semicolonPos !== false) {
            return trim(substr($header, 0, $semicolonPos));
        }

        return trim($header);
    }

    /**
     * Get the Content-Length header value.
     *
     * @return int|null The content length, or null if not present or invalid
     */
    public function contentLength(): ?int
    {
        $header = $this->request->getHeaderLine('Content-Length');

        if ($header === '') {
            return null;
        }

        $value = filter_var($header, FILTER_VALIDATE_INT);

        if ($value === false) {
            return null;
        }

        return $value;
    }

    /**
     * Check if the request accepts any of the given content types.
     *
     * @param string|list<string> $types One or more content types to check
     *
     * @return bool True if any of the types is acceptable
     */
    public function accepts(string|array $types): bool
    {
        $types = is_array($types) ? $types : [$types];
        $acceptHeader = $this->request->getHeaderLine('Accept');

        if ($acceptHeader === '') {
            return true;
        }

        $parsed = $this->parseAcceptHeader($acceptHeader);

        foreach ($parsed as $accept) {
            if ($accept['type'] === '*/*') {
                return true;
            }

            foreach ($types as $type) {
                if ($this->matchesMediaType($accept['type'], $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the client wants a JSON response.
     *
     * @return bool True if the Accept header indicates JSON
     */
    public function wantsJson(): bool
    {
        $acceptHeader = $this->request->getHeaderLine('Accept');

        if ($acceptHeader === '') {
            return false;
        }

        return str_contains($acceptHeader, '/json') || str_contains($acceptHeader, '+json');
    }

    /**
     * Determine the preferred content type from the available options.
     *
     * @param list<string> $available The available content types
     *
     * @return string|null The preferred type, or null if no match
     */
    public function preferredType(array $available): ?string
    {
        $acceptHeader = $this->request->getHeaderLine('Accept');

        if ($acceptHeader === '') {
            return $available[0] ?? null;
        }

        $parsed = $this->parseAcceptHeader($acceptHeader);

        foreach ($parsed as $accept) {
            foreach ($available as $type) {
                if ($this->matchesMediaType($accept['type'], $type)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Determine the preferred language from the available options.
     *
     * @param list<string> $available The available language codes
     *
     * @return string|null The preferred language, or null if no match
     */
    public function preferredLanguage(array $available): ?string
    {
        $header = $this->request->getHeaderLine('Accept-Language');

        if ($header === '') {
            return $available[0] ?? null;
        }

        $parsed = $this->parseAcceptHeader($header);

        foreach ($parsed as $accept) {
            foreach ($available as $language) {
                $acceptLang = strtolower($accept['type']);
                $availableLang = strtolower($language);

                if ($acceptLang === $availableLang || $acceptLang === '*') {
                    return $language;
                }

                if (str_starts_with($availableLang, $acceptLang . '-')) {
                    return $language;
                }

                if (str_contains($acceptLang, '-') && str_starts_with($acceptLang, $availableLang)) {
                    return $language;
                }
            }
        }

        return null;
    }

    /**
     * Get the client IP address, respecting trusted proxy configuration.
     *
     * @return string The client IP address
     */
    public function ip(): string
    {
        $remoteAddr = $this->serverParam('REMOTE_ADDR', '127.0.0.1');

        if (!$this->isTrustedProxy()) {
            return $remoteAddr;
        }

        if (($this->config->trustedHeaders & self::HEADER_FORWARDED) !== 0) {
            $forwarded = $this->request->getHeaderLine('Forwarded');

            if ($forwarded !== '' && preg_match('/for="?\[?([^"\];,\s]+)/', $forwarded, $matches) === 1) {
                return $matches[1];
            }
        }

        if (($this->config->trustedHeaders & self::HEADER_X_FORWARDED_FOR) !== 0) {
            $forwardedFor = $this->request->getHeaderLine('X-Forwarded-For');

            if ($forwardedFor !== '') {
                $ips = array_map('trim', explode(',', $forwardedFor));

                return $ips[0];
            }
        }

        return $remoteAddr;
    }

    /**
     * Get all client IP addresses from the X-Forwarded-For chain.
     *
     * @return list<string> The list of IP addresses
     */
    public function ips(): array
    {
        if ($this->isTrustedProxy() && ($this->config->trustedHeaders & self::HEADER_X_FORWARDED_FOR) !== 0) {
            $forwardedFor = $this->request->getHeaderLine('X-Forwarded-For');

            if ($forwardedFor !== '') {
                return array_map('trim', explode(',', $forwardedFor));
            }
        }

        return [$this->serverParam('REMOTE_ADDR', '127.0.0.1')];
    }

    /**
     * Get the request scheme, respecting trusted proxy configuration.
     *
     * @return string The URI scheme (e.g. "http" or "https")
     */
    public function scheme(): string
    {
        if ($this->isTrustedProxy() && ($this->config->trustedHeaders & self::HEADER_X_FORWARDED_PROTO) !== 0) {
            $proto = $this->request->getHeaderLine('X-Forwarded-Proto');

            if ($proto !== '') {
                return strtolower($proto);
            }
        }

        $scheme = $this->request->getUri()->getScheme();

        return $scheme !== '' ? $scheme : 'http';
    }

    /**
     * Get the host, respecting trusted proxy configuration.
     *
     * @return string The host name
     */
    public function host(): string
    {
        if ($this->isTrustedProxy() && ($this->config->trustedHeaders & self::HEADER_X_FORWARDED_HOST) !== 0) {
            $host = $this->request->getHeaderLine('X-Forwarded-Host');

            if ($host !== '') {
                return strtolower(explode(',', $host)[0]);
            }
        }

        return $this->request->getUri()->getHost();
    }

    /**
     * Get the port, respecting trusted proxy configuration.
     *
     * @return int|null The port number, or null if not specified
     */
    public function port(): ?int
    {
        if ($this->isTrustedProxy() && ($this->config->trustedHeaders & self::HEADER_X_FORWARDED_PORT) !== 0) {
            $port = $this->request->getHeaderLine('X-Forwarded-Port');

            if ($port !== '') {
                $filtered = filter_var($port, FILTER_VALIDATE_INT);

                return $filtered !== false ? $filtered : null;
            }
        }

        return $this->request->getUri()->getPort();
    }

    /**
     * Check if the request uses HTTPS, respecting trusted proxy configuration.
     *
     * @return bool True if the request is secure
     */
    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
    }

    /**
     * Check if the request is an XMLHttpRequest (XHR/AJAX).
     *
     * @return bool True if X-Requested-With is XMLHttpRequest
     */
    public function isXhr(): bool
    {
        return strtolower($this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * Check if the request Content-Type indicates JSON.
     *
     * @return bool True if the content type contains /json or +json
     */
    public function isJson(): bool
    {
        $contentType = $this->contentType();

        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    /**
     * Check if the request is a prefetch request.
     *
     * @return bool True if Purpose or Sec-Purpose header indicates prefetch
     */
    public function isPrefetch(): bool
    {
        return strtolower($this->request->getHeaderLine('Purpose')) === 'prefetch'
            || strtolower($this->request->getHeaderLine('Sec-Purpose')) === 'prefetch';
    }

    /**
     * Get the URI path.
     *
     * @return string The URI path
     */
    public function path(): string
    {
        $path = $this->request->getUri()->getPath();

        return $path !== '' ? $path : '/';
    }

    /**
     * Get the URL without query string (scheme + host + path).
     *
     * @return string The URL
     */
    public function url(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->path();
    }

    /**
     * Get the full URL including query string.
     *
     * @return string The full URL
     */
    public function fullUrl(): string
    {
        $url = $this->url();
        $query = $this->request->getUri()->getQuery();

        if ($query !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Get a specific path segment by 1-indexed position.
     *
     * @param int $index The 1-indexed segment position
     * @param string|null $default The default value if the segment does not exist
     *
     * @return string|null The segment value, or default
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        $segments = $this->segments();

        return $segments[$index - 1] ?? $default;
    }

    /**
     * Get all path segments.
     *
     * @return list<string> The path segments
     */
    public function segments(): array
    {
        $path = trim($this->path(), '/');

        if ($path === '') {
            return [];
        }

        return explode('/', $path);
    }

    /**
     * Check if the request path matches any of the given patterns.
     *
     * Patterns support * (matches one segment) and ** (matches any number of segments).
     *
     * @param string ...$patterns The patterns to match against
     *
     * @return bool True if any pattern matches
     */
    public function is(string ...$patterns): bool
    {
        $path = trim($this->path(), '/');

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern, '/');
            $regex = $this->patternToRegex($pattern);

            if (preg_match($regex, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an uploaded file by key.
     *
     * @param string $key The upload field name
     *
     * @return UploadedFileInterface|array<UploadedFileInterface>|null The uploaded file(s) or null
     */
    public function file(string $key): UploadedFileInterface|array|null
    {
        $files = $this->getUploadedFiles();

        return $files[$key] ?? null;
    }

    /**
     * Check if a valid uploaded file exists for the given key.
     *
     * @param string $key The upload field name
     *
     * @return bool True if the file exists and has no upload error
     */
    public function hasFile(string $key): bool
    {
        $files = $this->getUploadedFiles();

        if (!isset($files[$key])) {
            return false;
        }

        $file = $files[$key];

        if (is_array($file)) {
            foreach ($file as $item) {
                if ($item->getError() === UPLOAD_ERR_OK) {
                    return true;
                }
            }

            return false;
        }

        return $file->getError() === UPLOAD_ERR_OK;
    }

    /**
     * Get all uploaded files.
     *
     * @return array<string, UploadedFileInterface|array<UploadedFileInterface>> All uploaded files
     */
    public function allFiles(): array
    {
        return $this->getUploadedFiles();
    }

    /**
     * Get a cookie value.
     *
     * @param string $key The cookie name
     * @param string|null $default The default value
     *
     * @return string|null The cookie value, or default
     */
    public function cookie(string $key, ?string $default = null): ?string
    {
        $cookies = $this->request->getCookieParams();

        if (!isset($cookies[$key])) {
            return $default;
        }

        $value = $cookies[$key];

        return is_string($value) ? $value : $default;
    }

    /**
     * Get all cookie parameters.
     *
     * @return array<string, string> All cookies
     */
    public function cookies(): array
    {
        return $this->getCookieParams();
    }

    /**
     * Check if a cookie exists.
     *
     * @param string $key The cookie name
     *
     * @return bool True if the cookie is present
     */
    public function hasCookie(string $key): bool
    {
        return isset($this->request->getCookieParams()[$key]);
    }

    /**
     * Get the inner PSR-7 server request.
     *
     * @return ServerRequestInterface The wrapped PSR-7 request
     */
    public function psr(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Check if the remote address is a trusted proxy.
     *
     * @return bool True if the remote address matches a trusted proxy
     */
    private function isTrustedProxy(): bool
    {
        if ($this->config->trustedProxies === []) {
            return false;
        }

        $remoteAddr = $this->serverParam('REMOTE_ADDR', '');

        if ($remoteAddr === '') {
            return false;
        }

        return IpUtils::matches($remoteAddr, $this->config->trustedProxies);
    }

    /**
     * Get a server parameter value.
     *
     * @param string $key The server parameter name
     * @param string $default The default value
     *
     * @return string The server parameter value
     */
    private function serverParam(string $key, string $default): string
    {
        $params = $this->request->getServerParams();
        $value = $params[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * Parse an Accept or Accept-Language header into sorted entries.
     *
     * @param string $header The raw header value
     *
     * @return list<array{type: string, quality: float}> Sorted accept entries
     */
    private function parseAcceptHeader(string $header): array
    {
        $entries = [];
        $parts = explode(',', $header);

        foreach ($parts as $index => $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $type = trim($segments[0]);
            $quality = 1.0;

            for ($i = 1, $count = count($segments); $i < $count; $i++) {
                $param = trim($segments[$i]);

                if (str_starts_with($param, 'q=') || str_starts_with($param, 'Q=')) {
                    $qValue = substr($param, 2);
                    $filtered = filter_var($qValue, FILTER_VALIDATE_FLOAT);

                    if ($filtered !== false) {
                        $quality = $filtered;
                    }
                }
            }

            $specificity = 0;

            if (str_contains($type, '/')) {
                [$mainType, $subType] = explode('/', $type, 2);

                if ($mainType !== '*') {
                    $specificity += 2;
                }

                if ($subType !== '*') {
                    $specificity += 1;
                }
            }

            $entries[] = [
                'type' => $type,
                'quality' => $quality,
                'specificity' => $specificity,
                'order' => $index,
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            $qualityDiff = $b['quality'] <=> $a['quality'];

            if ($qualityDiff !== 0) {
                return $qualityDiff;
            }

            $specificityDiff = $b['specificity'] <=> $a['specificity'];

            if ($specificityDiff !== 0) {
                return $specificityDiff;
            }

            return $a['order'] <=> $b['order'];
        });

        return array_map(
            static fn(array $entry): array => ['type' => $entry['type'], 'quality' => $entry['quality']],
            $entries,
        );
    }

    /**
     * Check if two media types match, supporting wildcards.
     *
     * @param string $pattern The Accept header media type (may contain wildcards)
     * @param string $type The concrete media type to match against
     *
     * @return bool True if the types match
     */
    private function matchesMediaType(string $pattern, string $type): bool
    {
        if ($pattern === $type || $pattern === '*/*') {
            return true;
        }

        if (!str_contains($pattern, '/') || !str_contains($type, '/')) {
            return false;
        }

        [$patternMain, $patternSub] = explode('/', $pattern, 2);
        [$typeMain, $typeSub] = explode('/', $type, 2);

        if ($patternMain !== $typeMain && $patternMain !== '*') {
            return false;
        }

        return $patternSub === $typeSub || $patternSub === '*';
    }

    /**
     * Convert a path pattern with * and ** wildcards to a regex.
     *
     * @param string $pattern The pattern string
     *
     * @return string The regex pattern
     */
    private function patternToRegex(string $pattern): string
    {
        $parts = explode('/', $pattern);
        $regexParts = [];

        foreach ($parts as $part) {
            if ($part === '**') {
                $regexParts[] = '.*';
            } elseif ($part === '*') {
                $regexParts[] = '[^/]+';
            } else {
                $regexParts[] = preg_quote($part, '#');
            }
        }

        return '#^' . implode('/', $regexParts) . '$#';
    }
}
