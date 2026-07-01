<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Conformance;

use Http\Psr7Test\ResponseIntegrationTest;
use PHPdot\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-7 conformance for PHPdot\Http\Response, driven by php-http/psr7-integration-tests.
 */
final class ResponseConformanceTest extends ResponseIntegrationTest
{
    /**
     * @return ResponseInterface
     */
    public function createSubject()
    {
        return new Response();
    }
}
