<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Conformance;

use Http\Psr7Test\StreamIntegrationTest;
use PHPdot\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 conformance for PHPdot\Http\Stream, driven by php-http/psr7-integration-tests.
 */
final class StreamConformanceTest extends StreamIntegrationTest
{
    /**
     * @param string|resource|StreamInterface $data
     *
     * @return StreamInterface
     */
    public function createStream($data)
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }

        if (\is_resource($data)) {
            return new Stream($data);
        }

        return Stream::create((string) $data);
    }
}
