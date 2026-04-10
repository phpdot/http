<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use DateTimeImmutable;
use Nyholm\Psr7\ServerRequest;
use PHPdot\Http\Cookie;
use PHPdot\Http\ResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    // --- json() ---

    #[Test]
    public function json_returns_application_json_with_encoded_body(): void
    {
        $response = $this->factory->json(['name' => 'John', 'age' => 30]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"name":"John","age":30}', (string) $response->getBody());
    }

    #[Test]
    public function json_with_custom_status(): void
    {
        $response = $this->factory->json(['id' => 1], 201);

        self::assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function json_with_custom_options(): void
    {
        $response = $this->factory->json(['a' => 1], 200, JSON_PRETTY_PRINT);

        $body = (string) $response->getBody();

        self::assertStringContainsString("\n", $body);
    }

    #[Test]
    public function json_preserves_unicode_and_slashes(): void
    {
        $response = $this->factory->json(['url' => 'https://example.com/path', 'text' => "\u{00e9}"]);

        $body = (string) $response->getBody();

        self::assertStringNotContainsString('\\/', $body);
        self::assertStringNotContainsString('\\u', $body);
    }

    // --- html() ---

    #[Test]
    public function html_returns_text_html(): void
    {
        $response = $this->factory->html('<h1>Hello</h1>');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('<h1>Hello</h1>', (string) $response->getBody());
    }

    #[Test]
    public function html_with_custom_status(): void
    {
        $response = $this->factory->html('<h1>Not Found</h1>', 404);

        self::assertSame(404, $response->getStatusCode());
    }

    // --- text() ---

    #[Test]
    public function text_returns_text_plain(): void
    {
        $response = $this->factory->text('Hello World');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('Hello World', (string) $response->getBody());
    }

    // --- xml() ---

    #[Test]
    public function xml_returns_application_xml(): void
    {
        $xml = '<?xml version="1.0"?><root/>';
        $response = $this->factory->xml($xml);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        self::assertSame($xml, (string) $response->getBody());
    }

    // --- redirect() ---

    #[Test]
    public function redirect_sets_location_header_and_status(): void
    {
        $response = $this->factory->redirect('/login');

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function redirect_with_custom_status(): void
    {
        $response = $this->factory->redirect('/new-url', 301);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/new-url', $response->getHeaderLine('Location'));
    }

    // --- noContent() ---

    #[Test]
    public function no_content_returns_204_with_empty_body(): void
    {
        $response = $this->factory->noContent();

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
    }

    // --- raw() ---

    #[Test]
    public function raw_returns_given_status(): void
    {
        $response = $this->factory->raw(418);

        self::assertSame(418, $response->getStatusCode());
    }

    // --- download() ---

    #[Test]
    public function download_sets_content_disposition_with_filename(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, 'file-content');

        try {
            $response = $this->factory->download($tmpFile, 'report.pdf');

            self::assertSame(200, $response->getStatusCode());
            $disposition = $response->getHeaderLine('Content-Disposition');
            self::assertStringContainsString('attachment', $disposition);
            self::assertStringContainsString('report.pdf', $disposition);
            self::assertSame('file-content', (string) $response->getBody());
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function download_uses_basename_when_no_name_given(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, 'data');

        try {
            $response = $this->factory->download($tmpFile);

            $disposition = $response->getHeaderLine('Content-Disposition');
            self::assertStringContainsString(basename($tmpFile), $disposition);
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function download_throws_for_non_existent_file(): void
    {
        $this->expectException(RuntimeException::class);

        $this->factory->download('/nonexistent/file.txt');
    }

    // --- file() ---

    #[Test]
    public function file_returns_200_with_full_content(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, 'hello world');

        try {
            $psrRequest = new ServerRequest('GET', '/');
            $response = $this->factory->file($tmpFile, $psrRequest);

            self::assertSame(200, $response->getStatusCode());
            self::assertSame('hello world', (string) $response->getBody());
            self::assertSame('bytes', $response->getHeaderLine('Accept-Ranges'));
            self::assertNotSame('', $response->getHeaderLine('ETag'));
            self::assertNotSame('', $response->getHeaderLine('Last-Modified'));
            self::assertSame('11', $response->getHeaderLine('Content-Length'));
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function file_returns_206_for_range_request(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '0123456789');

        try {
            $psrRequest = (new ServerRequest('GET', '/'))
                ->withHeader('Range', 'bytes=0-4');
            $response = $this->factory->file($tmpFile, $psrRequest);

            self::assertSame(206, $response->getStatusCode());
            self::assertSame('01234', (string) $response->getBody());
            self::assertSame('5', $response->getHeaderLine('Content-Length'));
            self::assertStringContainsString('bytes 0-4/10', $response->getHeaderLine('Content-Range'));
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function file_returns_416_for_invalid_range(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '0123456789');

        try {
            $psrRequest = (new ServerRequest('GET', '/'))
                ->withHeader('Range', 'bytes=50-100');
            $response = $this->factory->file($tmpFile, $psrRequest);

            self::assertSame(416, $response->getStatusCode());
            self::assertStringContainsString('bytes */10', $response->getHeaderLine('Content-Range'));
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function file_throws_for_non_existent_file(): void
    {
        $this->expectException(RuntimeException::class);

        $this->factory->file('/nonexistent/file.txt', new ServerRequest('GET', '/'));
    }

    // --- withCache() ---

    #[Test]
    public function with_cache_sets_cache_control_header(): void
    {
        $response = $this->factory->raw(200);
        $cached = $this->factory->withCache($response, 3600, public: true);

        self::assertSame('public, max-age=3600', $cached->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function with_cache_private(): void
    {
        $response = $this->factory->raw(200);
        $cached = $this->factory->withCache($response, 300);

        self::assertSame('private, max-age=300', $cached->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function with_cache_must_revalidate(): void
    {
        $response = $this->factory->raw(200);
        $cached = $this->factory->withCache($response, 0, public: true, mustRevalidate: true);

        self::assertSame('public, max-age=0, must-revalidate', $cached->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function with_cache_immutable(): void
    {
        $response = $this->factory->raw(200);
        $cached = $this->factory->withCache($response, 31536000, public: true, immutable: true);

        self::assertSame('public, max-age=31536000, immutable', $cached->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function with_cache_no_store(): void
    {
        $response = $this->factory->raw(200);
        $cached = $this->factory->withCache($response, 0, noStore: true);

        self::assertSame('no-store, no-cache', $cached->getHeaderLine('Cache-Control'));
    }

    // --- withEtag() ---

    #[Test]
    public function with_etag_strong(): void
    {
        $response = $this->factory->raw(200);
        $tagged = $this->factory->withEtag($response, 'abc123');

        self::assertSame('"abc123"', $tagged->getHeaderLine('ETag'));
    }

    #[Test]
    public function with_etag_weak(): void
    {
        $response = $this->factory->raw(200);
        $tagged = $this->factory->withEtag($response, 'abc123', weak: true);

        self::assertSame('W/"abc123"', $tagged->getHeaderLine('ETag'));
    }

    // --- withLastModified() ---

    #[Test]
    public function with_last_modified_sets_header(): void
    {
        $response = $this->factory->raw(200);
        $date = new DateTimeImmutable('2024-06-15 12:00:00 UTC');
        $modified = $this->factory->withLastModified($response, $date);

        self::assertSame('Sat, 15 Jun 2024 12:00:00 GMT', $modified->getHeaderLine('Last-Modified'));
    }

    // --- isNotModified() ---

    #[Test]
    public function is_not_modified_with_matching_etag(): void
    {
        $response = $this->factory->raw(200)->withHeader('ETag', '"abc123"');
        $psrRequest = (new ServerRequest('GET', '/'))
            ->withHeader('If-None-Match', '"abc123"');

        self::assertTrue($this->factory->isNotModified($psrRequest, $response));
    }

    #[Test]
    public function is_not_modified_with_weak_etag(): void
    {
        $response = $this->factory->raw(200)->withHeader('ETag', 'W/"abc123"');
        $psrRequest = (new ServerRequest('GET', '/'))
            ->withHeader('If-None-Match', 'W/"abc123"');

        self::assertTrue($this->factory->isNotModified($psrRequest, $response));
    }

    #[Test]
    public function is_not_modified_with_non_matching_etag(): void
    {
        $response = $this->factory->raw(200)->withHeader('ETag', '"abc123"');
        $psrRequest = (new ServerRequest('GET', '/'))
            ->withHeader('If-None-Match', '"different"');

        self::assertFalse($this->factory->isNotModified($psrRequest, $response));
    }

    #[Test]
    public function is_not_modified_with_matching_last_modified(): void
    {
        $response = $this->factory->raw(200)
            ->withHeader('Last-Modified', 'Sat, 15 Jun 2024 12:00:00 GMT');
        $psrRequest = (new ServerRequest('GET', '/'))
            ->withHeader('If-Modified-Since', 'Sat, 15 Jun 2024 12:00:00 GMT');

        self::assertTrue($this->factory->isNotModified($psrRequest, $response));
    }

    #[Test]
    public function is_not_modified_with_newer_if_modified_since(): void
    {
        $response = $this->factory->raw(200)
            ->withHeader('Last-Modified', 'Sat, 15 Jun 2024 12:00:00 GMT');
        $psrRequest = (new ServerRequest('GET', '/'))
            ->withHeader('If-Modified-Since', 'Sun, 16 Jun 2024 12:00:00 GMT');

        self::assertTrue($this->factory->isNotModified($psrRequest, $response));
    }

    #[Test]
    public function is_modified_when_no_conditional_headers(): void
    {
        $response = $this->factory->raw(200);
        $psrRequest = new ServerRequest('GET', '/');

        self::assertFalse($this->factory->isNotModified($psrRequest, $response));
    }

    // --- notModified() ---

    #[Test]
    public function not_modified_returns_304(): void
    {
        $response = $this->factory->notModified();

        self::assertSame(304, $response->getStatusCode());
    }

    // --- withCookie() ---

    #[Test]
    public function with_cookie_adds_set_cookie_header(): void
    {
        $response = $this->factory->raw(200);
        $cookie = Cookie::create('sid', 'abc123');
        $result = $this->factory->withCookie($response, $cookie);

        $header = $result->getHeaderLine('Set-Cookie');
        self::assertStringContainsString('sid=abc123', $header);
    }

    // --- withoutCookie() ---

    #[Test]
    public function without_cookie_adds_expired_cookie(): void
    {
        $response = $this->factory->raw(200);
        $result = $this->factory->withoutCookie($response, 'sid');

        $header = $result->getHeaderLine('Set-Cookie');
        self::assertStringContainsString('sid=', $header);
        self::assertStringContainsString('Max-Age=-1', $header);
    }

    #[Test]
    public function without_cookie_with_domain(): void
    {
        $response = $this->factory->raw(200);
        $result = $this->factory->withoutCookie($response, 'sid', '/', '.example.com');

        $header = $result->getHeaderLine('Set-Cookie');
        self::assertStringContainsString('Domain=.example.com', $header);
    }

    // --- problem() ---

    #[Test]
    public function problem_returns_application_problem_json(): void
    {
        $response = $this->factory->problem(
            status: 404,
            detail: 'User not found',
            type: 'https://example.com/not-found',
            title: 'Not Found',
            instance: '/users/42',
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('https://example.com/not-found', $body['type']);
        self::assertSame('Not Found', $body['title']);
        self::assertSame(404, $body['status']);
        self::assertSame('User not found', $body['detail']);
        self::assertSame('/users/42', $body['instance']);
    }

    #[Test]
    public function problem_filters_empty_fields(): void
    {
        $response = $this->factory->problem(status: 500);

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(500, $body['status']);
        self::assertSame('Internal Server Error', $body['title']);
        self::assertArrayNotHasKey('type', $body);
        self::assertArrayNotHasKey('detail', $body);
        self::assertArrayNotHasKey('instance', $body);
    }

    #[Test]
    public function problem_with_extensions(): void
    {
        $response = $this->factory->problem(
            status: 422,
            detail: 'Validation failed',
            extensions: ['errors' => ['email' => 'required']],
        );

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(['email' => 'required'], $body['errors']);
    }

    #[Test]
    public function problem_defaults_title_from_status_text(): void
    {
        $response = $this->factory->problem(status: 403);

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame('Forbidden', $body['title']);
    }
}
