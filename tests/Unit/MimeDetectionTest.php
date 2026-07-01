<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use PHPdot\Http\Factory\ResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verifies MIME detection uses the industry-standard league/mime-type-detection
 * (finfo content sniffing + the generated extension database) rather than the old
 * hand-rolled 17-entry map.
 */
final class MimeDetectionTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    #[Test]
    public function covers_extensions_beyond_the_old_hardcoded_map(): void
    {
        // ".js" was NOT in the old 17-entry map; the extension database knows it.
        $path = tempnam(sys_get_temp_dir(), 'mime') . '.js';
        file_put_contents($path, "export const x = 1;\n");

        try {
            $response = $this->factory->download($path);

            self::assertStringContainsString('javascript', $response->getHeaderLine('Content-Type'));
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function detects_by_content_when_extension_is_absent(): void
    {
        // A GIF header but no extension — content sniffing must win over the extension.
        $path = tempnam(sys_get_temp_dir(), 'mime');
        file_put_contents($path, 'GIF89a' . str_repeat("\x00", 20));

        try {
            $response = $this->factory->download($path);

            self::assertSame('image/gif', $response->getHeaderLine('Content-Type'));
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function unknown_content_and_extension_falls_back_to_octet_stream(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mime') . '.zzznotathing';
        file_put_contents($path, random_bytes(32));

        try {
            $response = $this->factory->download($path);

            self::assertSame('application/octet-stream', $response->getHeaderLine('Content-Type'));
        } finally {
            unlink($path);
        }
    }
}
