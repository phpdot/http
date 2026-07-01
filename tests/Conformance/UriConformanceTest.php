<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Conformance;

use Http\Psr7Test\UriIntegrationTest;
use PHPdot\Http\Message\Uri;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 conformance for PHPdot\Http\Uri, driven by php-http/psr7-integration-tests.
 */
final class UriConformanceTest extends UriIntegrationTest
{
    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    public function createUri($uri)
    {
        return new Uri($uri);
    }
}
