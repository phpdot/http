<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\StatusText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatusTextTest extends TestCase
{
    #[Test]
    #[DataProvider('knownCodesProvider')]
    public function known_codes_return_correct_text(int $code, string $expected): void
    {
        self::assertSame($expected, StatusText::get($code));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function knownCodesProvider(): iterable
    {
        yield '200 OK' => [200, 'OK'];
        yield '201 Created' => [201, 'Created'];
        yield '204 No Content' => [204, 'No Content'];
        yield '301 Moved Permanently' => [301, 'Moved Permanently'];
        yield '302 Found' => [302, 'Found'];
        yield '304 Not Modified' => [304, 'Not Modified'];
        yield '400 Bad Request' => [400, 'Bad Request'];
        yield '401 Unauthorized' => [401, 'Unauthorized'];
        yield '403 Forbidden' => [403, 'Forbidden'];
        yield '404 Not Found' => [404, 'Not Found'];
        yield '405 Method Not Allowed' => [405, 'Method Not Allowed'];
        yield '422 Unprocessable Content' => [422, 'Unprocessable Content'];
        yield '429 Too Many Requests' => [429, 'Too Many Requests'];
        yield '500 Internal Server Error' => [500, 'Internal Server Error'];
        yield '502 Bad Gateway' => [502, 'Bad Gateway'];
        yield '503 Service Unavailable' => [503, 'Service Unavailable'];
    }

    #[Test]
    public function unknown_code_returns_empty_string(): void
    {
        self::assertSame('', StatusText::get(999));
        self::assertSame('', StatusText::get(0));
        self::assertSame('', StatusText::get(-1));
    }

    #[Test]
    public function all_1xx_range_has_entries(): void
    {
        $found = false;

        foreach (StatusText::MAP as $code => $text) {
            if ($code >= 100 && $code < 200) {
                self::assertNotSame('', $text);
                $found = true;
            }
        }

        self::assertTrue($found, '1xx range should have at least one entry');
    }

    #[Test]
    public function all_2xx_range_has_entries(): void
    {
        $found = false;

        foreach (StatusText::MAP as $code => $text) {
            if ($code >= 200 && $code < 300) {
                self::assertNotSame('', $text);
                $found = true;
            }
        }

        self::assertTrue($found, '2xx range should have at least one entry');
    }

    #[Test]
    public function all_3xx_range_has_entries(): void
    {
        $found = false;

        foreach (StatusText::MAP as $code => $text) {
            if ($code >= 300 && $code < 400) {
                self::assertNotSame('', $text);
                $found = true;
            }
        }

        self::assertTrue($found, '3xx range should have at least one entry');
    }

    #[Test]
    public function all_4xx_range_has_entries(): void
    {
        $found = false;

        foreach (StatusText::MAP as $code => $text) {
            if ($code >= 400 && $code < 500) {
                self::assertNotSame('', $text);
                $found = true;
            }
        }

        self::assertTrue($found, '4xx range should have at least one entry');
    }

    #[Test]
    public function all_5xx_range_has_entries(): void
    {
        $found = false;

        foreach (StatusText::MAP as $code => $text) {
            if ($code >= 500 && $code < 600) {
                self::assertNotSame('', $text);
                $found = true;
            }
        }

        self::assertTrue($found, '5xx range should have at least one entry');
    }
}
