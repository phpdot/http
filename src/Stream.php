<?php

declare(strict_types=1);

/**
 * Stream
 *
 * Standalone PSR-7 StreamInterface implementation. Wraps a PHP resource,
 * string, or existing StreamInterface into a seekable, readable stream.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class Stream implements StreamInterface
{
    /** @var resource|null */
    private $resource;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    private ?int $size;

    /**
     * @param resource $resource A PHP stream resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Stream requires a valid PHP resource');
        }

        $this->resource = $resource;

        $meta = stream_get_meta_data($resource);
        $mode = $meta['mode'];

        $this->seekable = $meta['seekable'];
        $this->readable = str_contains($mode, 'r') || str_contains($mode, '+');
        $this->writable = str_contains($mode, 'w') || str_contains($mode, 'a')
            || str_contains($mode, 'x') || str_contains($mode, 'c') || str_contains($mode, '+');

        $stat = fstat($resource);
        $this->size = $stat !== false ? $stat['size'] : null;
    }

    /**
     * Create a stream from a string, resource, or existing StreamInterface.
     *
     * @param string|resource|StreamInterface $body The body content
     *
     * @return self The stream instance
     */
    public static function create(string|StreamInterface $body = ''): self
    {
        if ($body instanceof self) {
            return $body;
        }

        if ($body instanceof StreamInterface) {
            $resource = fopen('php://memory', 'r+b');

            if ($resource === false) {
                throw new RuntimeException('Unable to open php://memory');
            }

            $contents = $body->__toString();

            if ($contents !== '') {
                fwrite($resource, $contents);
                rewind($resource);
            }

            return new self($resource);
        }

        $resource = fopen('php://memory', 'r+b');

        if ($resource === false) {
            throw new RuntimeException('Unable to open php://memory');
        }

        if ($body !== '') {
            fwrite($resource, $body);
            rewind($resource);
        }

        return new self($resource);
    }

    public function __toString(): string
    {
        if ($this->resource === null) {
            return '';
        }

        try {
            if ($this->seekable) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;
        $this->size = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stat = fstat($this->resource);

        return $stat !== false ? $stat['size'] : null;
    }

    public function tell(): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        $position = ftell($this->resource);

        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    public function eof(): bool
    {
        if ($this->resource === null) {
            return true;
        }

        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek in stream');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $this->size = null;
        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);

        if ($result === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        $contents = stream_get_contents($this->resource);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->resource === null) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key !== null) {
            return $meta[$key] ?? null;
        }

        return $meta;
    }
}
