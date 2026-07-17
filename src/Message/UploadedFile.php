<?php

declare(strict_types=1);

/**
 * UploadedFile
 *
 * Standalone PSR-7 UploadedFileInterface implementation. Wraps an uploaded file
 * (as a stream or a temporary file path) and moves it to its destination exactly
 * once. Under a CLI SAPI (Swoole) it uses rename(); under a web SAPI it uses
 * move_uploaded_file() for genuine uploads.
 *
 * Not immutable by design — moveTo() is a one-shot operation. Instances are
 * per-request (one coroutine), never shared, so the mutable moved-state is safe.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface
{
    /**
     * @var list<int> Valid PHP upload error codes
     */
    private const array UPLOAD_ERRORS = [
        \UPLOAD_ERR_OK,
        \UPLOAD_ERR_INI_SIZE,
        \UPLOAD_ERR_FORM_SIZE,
        \UPLOAD_ERR_PARTIAL,
        \UPLOAD_ERR_NO_FILE,
        \UPLOAD_ERR_NO_TMP_DIR,
        \UPLOAD_ERR_CANT_WRITE,
        \UPLOAD_ERR_EXTENSION,
    ];

    private ?StreamInterface $stream = null;

    private ?string $file = null;

    private bool $moved = false;

    /**
     * Create an uploaded-file value object from a stream or a temporary file path.
     *
     * @param StreamInterface|string $streamOrFile The uploaded content as a stream or a temp file path
     * @param int|null $size The file size in bytes
     * @param int $error One of the UPLOAD_ERR_* constants
     * @param string|null $clientFilename The filename sent by the client
     * @param string|null $clientMediaType The media type sent by the client
     *
     * @throws InvalidArgumentException When the error status is invalid
     */
    public function __construct(
        StreamInterface|string $streamOrFile,
        private readonly ?int $size = null,
        private readonly int $error = \UPLOAD_ERR_OK,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
        if (!in_array($this->error, self::UPLOAD_ERRORS, true)) {
            throw new InvalidArgumentException('Invalid upload error status; must be an UPLOAD_ERR_* constant.');
        }

        if ($this->error === \UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } else {
                $this->stream = $streamOrFile;
            }
        }
    }

    public function getStream(): StreamInterface
    {
        $this->assertActive();

        if ($this->stream !== null) {
            return $this->stream;
        }

        $file = (string) $this->file;
        $resource = fopen($file, 'rb');

        if ($resource === false) {
            throw new RuntimeException(sprintf('Unable to open uploaded file "%s".', $file));
        }

        return new Stream($resource);
    }

    public function moveTo(string $targetPath): void
    {
        $this->assertActive();

        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path must be a non-empty string.');
        }

        if ($this->file !== null) {
            $this->moved = $this->moveFile($this->file, $targetPath);
        } else {
            $this->copyStreamToPath($targetPath);
            $this->moved = true;
        }

        if ($this->moved === false) {
            throw new RuntimeException(sprintf('Unable to move uploaded file to "%s".', $targetPath));
        }

        $this->stream = null;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Ensure the file has not been moved and has no upload error.
     *
     * @throws RuntimeException When the upload errored or was already moved
     *
     * @return void
     */
    private function assertActive(): void
    {
        if ($this->error !== \UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error.');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved.');
        }
    }

    /**
     * Move a file-backed upload, choosing rename() under CLI/Swoole and
     * move_uploaded_file() for genuine web-SAPI uploads.
     *
     * @param string $source The source path
     * @param string $targetPath The destination path
     *
     * @return bool Whether the move succeeded
     */
    private function moveFile(string $source, string $targetPath): bool
    {
        if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'cli-server' || \PHP_SAPI === 'phpdbg') {
            return rename($source, $targetPath);
        }

        return is_uploaded_file($source)
            ? move_uploaded_file($source, $targetPath)
            : rename($source, $targetPath);
    }

    /**
     * Stream the upload contents to a destination path.
     *
     * @param string $targetPath The destination path
     *
     * @throws RuntimeException When the destination cannot be written
     *
     * @return void
     */
    private function copyStreamToPath(string $targetPath): void
    {
        $stream = $this->stream;

        if ($stream === null) {
            throw new RuntimeException('No stream available to move.');
        }

        $target = fopen($targetPath, 'wb');

        if ($target === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" for writing.', $targetPath));
        }

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            $chunk = $stream->read(8192);

            if ($chunk === '') {
                break;
            }

            fwrite($target, $chunk);
        }

        fclose($target);
    }
}
