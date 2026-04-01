<?php

declare(strict_types=1);

/**
 * Response Factory
 *
 * Creates PSR-7 response instances with convenient helpers for JSON, HTML,
 * file downloads, caching, cookies, and RFC 9457 problem details.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

use DateTimeInterface;
use DateTimeZone;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

final class ResponseFactory
{
    /** @var array<string, string> */
    private const array MIME_TYPES = [
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
        'csv'  => 'text/csv',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'html' => 'text/html',
        'txt'  => 'text/plain',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'mp4'  => 'video/mp4',
        'mp3'  => 'audio/mpeg',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @param ResponseFactoryInterface $responseFactory The PSR-17 response factory
     * @param StreamFactoryInterface $streamFactory The PSR-17 stream factory
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Create a JSON response.
     *
     * @param mixed $data The data to encode as JSON
     * @param int $status The HTTP status code
     * @param int $options Additional JSON encoding options OR'd with defaults
     *
     *
     * @throws \JsonException If encoding fails
     * @return ResponseInterface The JSON response
     */
    public function json(mixed $data, int $status = 200, int $options = 0): ResponseInterface
    {
        $defaultOptions = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $body = json_encode($data, $defaultOptions | $options);

        return $this->createResponse($body, $status, 'application/json');
    }

    /**
     * Create an HTML response.
     *
     * @param string $html The HTML content
     * @param int $status The HTTP status code
     *
     * @return ResponseInterface The HTML response
     */
    public function html(string $html, int $status = 200): ResponseInterface
    {
        return $this->createResponse($html, $status, 'text/html; charset=UTF-8');
    }

    /**
     * Create a plain text response.
     *
     * @param string $text The text content
     * @param int $status The HTTP status code
     *
     * @return ResponseInterface The plain text response
     */
    public function text(string $text, int $status = 200): ResponseInterface
    {
        return $this->createResponse($text, $status, 'text/plain; charset=UTF-8');
    }

    /**
     * Create an XML response.
     *
     * @param string $xml The XML content
     * @param int $status The HTTP status code
     *
     * @return ResponseInterface The XML response
     */
    public function xml(string $xml, int $status = 200): ResponseInterface
    {
        return $this->createResponse($xml, $status, 'application/xml; charset=UTF-8');
    }

    /**
     * Create a redirect response.
     *
     * @param string $url The URL to redirect to
     * @param int $status The HTTP status code (default 302 Found)
     *
     * @return ResponseInterface The redirect response
     */
    public function redirect(string $url, int $status = 302): ResponseInterface
    {
        return $this->responseFactory->createResponse($status)
            ->withHeader('Location', $url);
    }

    /**
     * Create a no-content response.
     *
     * @param int $status The HTTP status code (default 204 No Content)
     *
     * @return ResponseInterface The empty response
     */
    public function noContent(int $status = 204): ResponseInterface
    {
        return $this->responseFactory->createResponse($status);
    }

    /**
     * Create a raw response with the given status code.
     *
     * @param int $status The HTTP status code
     *
     * @return ResponseInterface The raw response
     */
    public function raw(int $status = 200): ResponseInterface
    {
        return $this->responseFactory->createResponse($status);
    }

    /**
     * Create a file download response.
     *
     * Sets Content-Disposition with ASCII fallback and UTF-8 per RFC 5987.
     *
     * @param string $path The absolute path to the file
     * @param string $name The download filename (defaults to basename)
     * @param array<string, string> $headers Additional headers to include
     *
     *
     * @throws RuntimeException If the file does not exist or is not readable
     * @return ResponseInterface The download response
     */
    public function download(string $path, string $name = '', array $headers = []): ResponseInterface
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                sprintf('File "%s" does not exist or is not readable.', $path),
            );
        }

        $fileName = $name !== '' ? $name : basename($path);
        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $fileName) ?? $fileName;
        $mimeType = $this->guessMimeType($path);
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                sprintf('Unable to read file "%s".', $path),
            );
        }

        $disposition = sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $asciiName,
            rawurlencode($fileName),
        );

        $response = $this->createResponse($contents, 200, $mimeType)
            ->withHeader('Content-Disposition', $disposition);

        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        return $response;
    }

    /**
     * Serve a file inline with HTTP Range support (RFC 7233).
     *
     * Sets ETag, Last-Modified, and Accept-Ranges headers. Supports single
     * byte-range requests returning 206 Partial Content.
     *
     * @param string $path The absolute path to the file
     * @param ServerRequestInterface $request The incoming request for Range header parsing
     * @param array<string, string> $headers Additional headers to include
     *
     *
     * @throws RuntimeException If the file does not exist or is not readable
     * @return ResponseInterface The file response
     */
    public function file(string $path, ServerRequestInterface $request, array $headers = []): ResponseInterface
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                sprintf('File "%s" does not exist or is not readable.', $path),
            );
        }

        $mimeType = $this->guessMimeType($path);
        $fileSize = filesize($path);
        $mtime = filemtime($path);

        if ($fileSize === false || $mtime === false) {
            throw new RuntimeException(
                sprintf('Unable to stat file "%s".', $path),
            );
        }

        $etag = sprintf('W/"%d-%d"', $mtime, $fileSize);
        $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

        $rangeHeader = $request->getHeaderLine('Range');

        if ($rangeHeader === '') {
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException(
                    sprintf('Unable to read file "%s".', $path),
                );
            }

            $response = $this->createResponse($contents, 200, $mimeType)
                ->withHeader('ETag', $etag)
                ->withHeader('Last-Modified', $lastModified)
                ->withHeader('Accept-Ranges', 'bytes')
                ->withHeader('Content-Length', (string) $fileSize);

            foreach ($headers as $headerName => $headerValue) {
                $response = $response->withHeader($headerName, $headerValue);
            }

            return $response;
        }

        $matches = [];

        if (preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches) !== 1) {
            return $this->responseFactory->createResponse(416)
                ->withHeader('Content-Range', sprintf('bytes */%d', $fileSize));
        }

        $start = $matches[1] !== '' ? (int) $matches[1] : null;
        $end = $matches[2] !== '' ? (int) $matches[2] : null;

        if ($start === null && $end === null) {
            return $this->responseFactory->createResponse(416)
                ->withHeader('Content-Range', sprintf('bytes */%d', $fileSize));
        }

        if ($start === null) {
            /** @var int $end */
            $start = $fileSize - $end;
            $end = $fileSize - 1;
        } elseif ($end === null) {
            $end = $fileSize - 1;
        }

        if ($start < 0 || $start > $end || $end >= $fileSize) {
            return $this->responseFactory->createResponse(416)
                ->withHeader('Content-Range', sprintf('bytes */%d', $fileSize));
        }

        $length = max(1, $end - $start + 1);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(
                sprintf('Unable to open file "%s".', $path),
            );
        }

        fseek($handle, $start);
        $contents = fread($handle, $length);
        fclose($handle);

        if ($contents === false) {
            throw new RuntimeException(
                sprintf('Unable to read file "%s".', $path),
            );
        }

        $response = $this->createResponse($contents, 206, $mimeType)
            ->withHeader('ETag', $etag)
            ->withHeader('Last-Modified', $lastModified)
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Length', (string) $length)
            ->withHeader('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $fileSize));

        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        return $response;
    }

    /**
     * Add Cache-Control headers to a response.
     *
     * @param ResponseInterface $response The response to modify
     * @param int $maxAge The max-age value in seconds
     * @param bool $public Whether the response is publicly cacheable
     * @param bool $mustRevalidate Whether caches must revalidate
     * @param bool $immutable Whether the response is immutable
     * @param bool $noStore Whether the response must not be stored
     *
     * @return ResponseInterface The response with Cache-Control header
     */
    public function withCache(
        ResponseInterface $response,
        int $maxAge,
        bool $public = false,
        bool $mustRevalidate = false,
        bool $immutable = false,
        bool $noStore = false,
    ): ResponseInterface {
        if ($noStore) {
            return $response->withHeader('Cache-Control', 'no-store, no-cache');
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

        return $response->withHeader('Cache-Control', implode(', ', $directives));
    }

    /**
     * Add an ETag header to a response.
     *
     * @param ResponseInterface $response The response to modify
     * @param string $etag The ETag value (without quotes)
     * @param bool $weak Whether to use a weak ETag
     *
     * @return ResponseInterface The response with ETag header
     */
    public function withEtag(ResponseInterface $response, string $etag, bool $weak = false): ResponseInterface
    {
        $value = $weak ? sprintf('W/"%s"', $etag) : sprintf('"%s"', $etag);

        return $response->withHeader('ETag', $value);
    }

    /**
     * Add a Last-Modified header to a response.
     *
     * @param ResponseInterface $response The response to modify
     * @param DateTimeInterface $date The last modification date
     *
     * @return ResponseInterface The response with Last-Modified header
     */
    public function withLastModified(ResponseInterface $response, DateTimeInterface $date): ResponseInterface
    {
        $formatted = (new \DateTimeImmutable('@' . $date->getTimestamp()))
            ->setTimezone(new DateTimeZone('GMT'))
            ->format('D, d M Y H:i:s') . ' GMT';

        return $response->withHeader('Last-Modified', $formatted);
    }

    /**
     * Check if a response has not been modified based on request conditions.
     *
     * Compares If-None-Match against ETag and If-Modified-Since against
     * Last-Modified. When both are present, both must pass.
     *
     * @param ServerRequestInterface $request The incoming request
     * @param ResponseInterface $response The prepared response
     *
     * @return bool True if the response has not been modified
     */
    public function isNotModified(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');

        if ($ifNoneMatch === '' && $ifModifiedSince === '') {
            return false;
        }

        $etagMatch = null;
        $modifiedMatch = null;

        if ($ifNoneMatch !== '') {
            $responseEtag = $response->getHeaderLine('ETag');

            if ($responseEtag === '') {
                $etagMatch = false;
            } else {
                $stripWeak = static fn(string $tag): string => str_starts_with($tag, 'W/') ? substr($tag, 2) : $tag;

                $clientTags = array_map(
                    static fn(string $tag): string => $stripWeak(trim($tag)),
                    explode(',', $ifNoneMatch),
                );

                $etagMatch = in_array($stripWeak($responseEtag), $clientTags, true);
            }
        }

        if ($ifModifiedSince !== '') {
            $lastModified = $response->getHeaderLine('Last-Modified');

            if ($lastModified === '') {
                $modifiedMatch = false;
            } else {
                $sinceTime = strtotime($ifModifiedSince);
                $lastTime = strtotime($lastModified);

                $modifiedMatch = $sinceTime !== false
                    && $lastTime !== false
                    && $lastTime <= $sinceTime;
            }
        }

        if ($etagMatch !== null && $modifiedMatch !== null) {
            return $etagMatch && $modifiedMatch;
        }

        if ($etagMatch !== null) {
            return $etagMatch;
        }

        return $modifiedMatch;
    }

    /**
     * Create a 304 Not Modified response.
     *
     * @return ResponseInterface The 304 response
     */
    public function notModified(): ResponseInterface
    {
        return $this->responseFactory->createResponse(304);
    }

    /**
     * Add a Set-Cookie header to a response.
     *
     * @param ResponseInterface $response The response to modify
     * @param Cookie $cookie The cookie to set
     *
     * @return ResponseInterface The response with Set-Cookie header added
     */
    public function withCookie(ResponseInterface $response, Cookie $cookie): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $cookie->toHeaderString());
    }

    /**
     * Add an expired Set-Cookie header to remove a cookie.
     *
     * @param ResponseInterface $response The response to modify
     * @param string $name The cookie name to remove
     * @param string $path The cookie path
     * @param string $domain The cookie domain
     *
     * @return ResponseInterface The response with expired Set-Cookie header
     */
    public function withoutCookie(
        ResponseInterface $response,
        string $name,
        string $path = '/',
        string $domain = '',
    ): ResponseInterface {
        $cookie = Cookie::create($name, '')
            ->withPath($path)
            ->withMaxAge(-1);

        if ($domain !== '') {
            $cookie = $cookie->withDomain($domain);
        }

        return $response->withAddedHeader('Set-Cookie', $cookie->toHeaderString());
    }

    /**
     * Create an RFC 9457 Problem Details JSON response.
     *
     * @param int $status The HTTP status code
     * @param string $detail A human-readable explanation
     * @param string $type A URI reference identifying the problem type
     * @param string $title A short summary (defaults to status reason phrase)
     * @param string $instance A URI reference identifying the specific occurrence
     * @param array<string, mixed> $extensions Additional problem detail members
     *
     *
     * @throws \JsonException If encoding fails
     * @return ResponseInterface The problem details response
     */
    public function problem(
        int $status,
        string $detail = '',
        string $type = '',
        string $title = '',
        string $instance = '',
        array $extensions = [],
    ): ResponseInterface {
        $resolvedTitle = $title !== '' ? $title : StatusText::get($status);

        $body = [
            'type'     => $type,
            'title'    => $resolvedTitle,
            'status'   => $status,
            'detail'   => $detail,
            'instance' => $instance,
        ];

        $body = array_filter(
            $body,
            static fn(mixed $value): bool => $value !== '',
        );

        $body = array_merge($body, $extensions);

        $defaultOptions = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $encoded = json_encode($body, $defaultOptions);

        return $this->createResponse($encoded, $status, 'application/problem+json');
    }

    /**
     * Create a response with a body and content type.
     *
     * @param string $body The response body
     * @param int $status The HTTP status code
     * @param string $contentType The Content-Type header value
     *
     * @return ResponseInterface The response
     */
    private function createResponse(string $body, int $status, string $contentType): ResponseInterface
    {
        $stream = $this->streamFactory->createStream($body);

        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', $contentType)
            ->withBody($stream);
    }

    /**
     * Guess the MIME type of a file based on its extension.
     *
     * @param string $path The file path
     *
     * @return string The guessed MIME type, or application/octet-stream if unknown
     */
    private function guessMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return self::MIME_TYPES[$extension] ?? 'application/octet-stream';
    }
}
