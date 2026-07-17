<?php

declare(strict_types=1);

/**
 * MessageTrait
 *
 * Shared PSR-7 MessageInterface implementation: protocol version, headers
 * (case-insensitive lookup, original-casing preservation, RFC 7230 validation),
 * and body. Immutable — every with*() returns a clone. Used by ServerRequest
 * (and Response).
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    private string $protocolVersion = '1.1';

    /**
     * @var array<string, list<string>> Lowercased name => values
     */
    private array $headers = [];

    /**
     * @var array<string, string> Lowercased name => original casing
     */
    private array $headerNames = [];

    private StreamInterface $body;

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
            $result[$this->headerNames[$normalized]] = $values;
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
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $values = $this->filterHeaderValue($value);

        $normalized = strtolower($name);
        $clone = clone $this;
        $clone->headerNames[$normalized] = $name;
        $clone->headers[$normalized] = $values;

        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $newValues = $this->filterHeaderValue($value);

        $normalized = strtolower($name);
        $clone = clone $this;

        if (!isset($clone->headerNames[$normalized])) {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$normalized] = [];
        }

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

    /**
     * Populate the header maps from a constructor-supplied array, with validation.
     *
     * @param array<array-key, string|int|float|array<array-key, string|int|float>> $headers
     *
     * @return void
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $name = (string) $name;
            $this->assertHeaderName($name);
            $normalized = strtolower($name);

            if (isset($this->headers[$normalized])) {
                $this->headers[$normalized] = array_merge($this->headers[$normalized], $this->filterHeaderValue($value));
            } else {
                $this->headerNames[$normalized] = $name;
                $this->headers[$normalized] = $this->filterHeaderValue($value);
            }
        }
    }

    /**
     * Assert a header name is a valid RFC 7230 field-name token.
     *
     * @param string $name The header name
     *
     * @throws InvalidArgumentException When the name is empty or malformed
     *
     * @return void
     */
    private function assertHeaderName(string $name): void
    {
        if ($name === '' || preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid HTTP header name.', $name));
        }
    }

    /**
     * Validate and normalize a header value into a list of strings.
     *
     * @param mixed $value A string, number, or non-empty array of strings/numbers
     *
     * @throws InvalidArgumentException When the value is empty, non-scalar, or contains invalid characters
     *
     * @return list<string> The normalized header values
     */
    private function filterHeaderValue(mixed $value): array
    {
        $values = is_array($value) ? array_values($value) : [$value];

        if ($values === []) {
            throw new InvalidArgumentException('Header value must not be an empty array.');
        }

        $result = [];

        foreach ($values as $item) {
            if (is_int($item) || is_float($item)) {
                $item = (string) $item;
            }

            if (!is_string($item)) {
                throw new InvalidArgumentException('Header values must be strings or numbers.');
            }

            if (preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/D', $item) !== 1) {
                throw new InvalidArgumentException('Header value contains invalid characters (CR, LF, or NUL).');
            }

            $result[] = $item;
        }

        return $result;
    }
}
