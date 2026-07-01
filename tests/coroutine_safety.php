<?php

declare(strict_types=1);

/**
 * Coroutine-safety stress for the SHARED singleton ResponseFactory (the only shared
 * object in the http stack). Many coroutines hammer the one factory instance with
 * yield points (Co::sleep) forcing interleaving; each asserts the objects IT built
 * are uncontaminated by the others. Catches per-call factory state or shared stream
 * cursors — the real PSR-7 coroutine footguns.
 *
 * Run: php tests/coroutine_safety.php   (requires ext-swoole)
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use PHPdot\Http\Factory\ResponseFactory;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

if (!extension_loaded('swoole')) {
    fwrite(STDERR, "ext-swoole not loaded\n");
    exit(2);
}

$factory = new ResponseFactory(); // ONE instance shared across every coroutine
$errors = [];
$count = 300;

Coroutine\run(static function () use ($factory, &$errors, $count): void {
    $wg = new WaitGroup();

    for ($i = 0; $i < $count; $i++) {
        $wg->add();
        Coroutine::create(static function () use ($factory, $i, &$errors, $wg): void {
            $tag = "coro-{$i}";

            $request = $factory->createServerRequest('POST', "/users/{$i}")
                ->withHeader('X-Tag', $tag)
                ->withAttribute('n', $i);

            Coroutine::sleep(0.001); // yield — interleave with other coroutines

            $response = $factory->json(['n' => $i, 'tag' => $tag]);
            $stream = $factory->createStream("body-{$i}");

            Coroutine::sleep(0.001); // yield again after building

            if ($request->getHeaderLine('X-Tag') !== $tag) {
                $errors[] = "request header contaminated at {$i}: '{$request->getHeaderLine('X-Tag')}'";
            }
            if ($request->getAttribute('n') !== $i) {
                $errors[] = "request attribute contaminated at {$i}";
            }
            if ((string) $request->getUri() !== "/users/{$i}") {
                $errors[] = "request uri contaminated at {$i}: '" . (string) $request->getUri() . "'";
            }

            /** @var array{n:int,tag:string} $decoded */
            $decoded = json_decode((string) $response->getBody(), true);
            if ($decoded['n'] !== $i || $decoded['tag'] !== $tag) {
                $errors[] = "response body contaminated at {$i}";
            }
            if ((string) $stream !== "body-{$i}") {
                $errors[] = "stream contaminated at {$i}: '" . (string) $stream . "'";
            }

            $wg->done();
        });
    }

    $wg->wait();
});

if ($errors === []) {
    echo "COROUTINE-SAFE: {$count} concurrent coroutines sharing one ResponseFactory, 0 contamination\n";
    exit(0);
}

echo "CONTAMINATION DETECTED (" . count($errors) . "):\n";
foreach (array_slice($errors, 0, 10) as $e) {
    echo "  - {$e}\n";
}
exit(1);
