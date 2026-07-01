<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Conformance;

use Http\Psr7Test\ServerRequestIntegrationTest;
use PHPdot\Http\Message\ServerRequest;
use PHPdot\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 conformance for PHPdot\Http\ServerRequest (also runs the RequestIntegrationTest
 * and MessageTrait cases), driven by php-http/psr7-integration-tests.
 */
final class ServerRequestConformanceTest extends ServerRequestIntegrationTest
{
    /**
     * @return ServerRequestInterface
     */
    public function createSubject()
    {
        return new ServerRequest('GET', new Uri('/'), [], null, '1.1', $_SERVER);
    }
}
