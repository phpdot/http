<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Conformance;

use Http\Psr7Test\UploadedFileIntegrationTest;
use PHPdot\Http\Message\Stream;
use PHPdot\Http\Message\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * PSR-7 conformance for PHPdot\Http\UploadedFile, driven by php-http/psr7-integration-tests.
 */
final class UploadedFileConformanceTest extends UploadedFileIntegrationTest
{
    /**
     * @return UploadedFileInterface
     */
    public function createSubject()
    {
        return new UploadedFile(
            Stream::create('phpdot upload contents'),
            22,
            \UPLOAD_ERR_OK,
            'upload.txt',
            'text/plain',
        );
    }
}
