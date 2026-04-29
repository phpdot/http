<?php

declare(strict_types=1);

namespace PHPdot\Http\Tests\Unit;

use DateTimeImmutable;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use Nyholm\Psr7\Uri;
use PHPdot\Http\HttpConfig;
use PHPdot\Http\Request;
use PHPdot\Http\Tests\Stubs\Color;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    // --- PSR-7 delegation ---

    #[Test]
    public function get_method_delegates_to_inner_request(): void
    {
        $request = new Request(new ServerRequest('POST', '/test'));

        self::assertSame('POST', $request->getMethod());
    }

    #[Test]
    public function get_uri_delegates_to_inner_request(): void
    {
        $request = new Request(new ServerRequest('GET', '/users?page=1'));

        self::assertSame('/users', $request->getUri()->getPath());
        self::assertSame('page=1', $request->getUri()->getQuery());
    }

    #[Test]
    public function get_protocol_version_delegates(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withProtocolVersion('2.0');
        $request = new Request($inner);

        self::assertSame('2.0', $request->getProtocolVersion());
    }

    #[Test]
    public function get_headers_delegates(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withHeader('X-Foo', 'bar');
        $request = new Request($inner);

        self::assertTrue($request->hasHeader('X-Foo'));
        self::assertSame(['bar'], $request->getHeader('X-Foo'));
        self::assertSame('bar', $request->getHeaderLine('X-Foo'));
    }

    #[Test]
    public function get_body_delegates(): void
    {
        $inner = new ServerRequest('GET', '/');
        $request = new Request($inner);

        self::assertSame('', (string) $request->getBody());
    }

    #[Test]
    public function get_request_target_delegates(): void
    {
        $inner = new ServerRequest('GET', '/foo?bar=1');
        $request = new Request($inner);

        self::assertSame('/foo?bar=1', $request->getRequestTarget());
    }

    #[Test]
    public function get_server_params_delegates(): void
    {
        $inner = new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
        $request = new Request($inner);

        self::assertSame('1.2.3.4', $request->getServerParams()['REMOTE_ADDR']);
    }

    // --- with*() return new Request instance ---

    #[Test]
    public function with_method_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withMethod('POST');

        self::assertNotSame($request, $new);
        self::assertInstanceOf(Request::class, $new);
        self::assertSame('POST', $new->getMethod());
        self::assertSame('GET', $request->getMethod());
    }

    #[Test]
    public function with_uri_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/old'));
        $new = $request->withUri(new Uri('/new'));

        self::assertNotSame($request, $new);
        self::assertSame('/new', $new->getUri()->getPath());
    }

    #[Test]
    public function with_header_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withHeader('X-Test', 'value');

        self::assertNotSame($request, $new);
        self::assertSame('value', $new->getHeaderLine('X-Test'));
        self::assertSame('', $request->getHeaderLine('X-Test'));
    }

    #[Test]
    public function with_added_header_returns_new_instance(): void
    {
        $request = new Request((new ServerRequest('GET', '/'))->withHeader('X-Test', 'a'));
        $new = $request->withAddedHeader('X-Test', 'b');

        self::assertNotSame($request, $new);
        self::assertSame(['a', 'b'], $new->getHeader('X-Test'));
    }

    #[Test]
    public function without_header_returns_new_instance(): void
    {
        $request = new Request((new ServerRequest('GET', '/'))->withHeader('X-Test', 'a'));
        $new = $request->withoutHeader('X-Test');

        self::assertNotSame($request, $new);
        self::assertFalse($new->hasHeader('X-Test'));
    }

    #[Test]
    public function with_protocol_version_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withProtocolVersion('2.0');

        self::assertNotSame($request, $new);
        self::assertSame('2.0', $new->getProtocolVersion());
    }

    #[Test]
    public function with_request_target_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withRequestTarget('/new');

        self::assertNotSame($request, $new);
        self::assertSame('/new', $new->getRequestTarget());
    }

    #[Test]
    public function with_cookie_params_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withCookieParams(['sid' => 'abc']);

        self::assertNotSame($request, $new);
        self::assertSame(['sid' => 'abc'], $new->getCookieParams());
    }

    #[Test]
    public function with_query_params_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withQueryParams(['page' => '1']);

        self::assertNotSame($request, $new);
        self::assertSame(['page' => '1'], $new->getQueryParams());
    }

    #[Test]
    public function with_uploaded_files_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('POST', '/'));
        $file = new UploadedFile('content', 7, UPLOAD_ERR_OK);
        $new = $request->withUploadedFiles(['avatar' => $file]);

        self::assertNotSame($request, $new);
        self::assertArrayHasKey('avatar', $new->getUploadedFiles());
    }

    #[Test]
    public function with_parsed_body_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('POST', '/'));
        $new = $request->withParsedBody(['name' => 'John']);

        self::assertNotSame($request, $new);
        self::assertSame(['name' => 'John'], $new->getParsedBody());
    }

    #[Test]
    public function with_attribute_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $new = $request->withAttribute('id', 42);

        self::assertNotSame($request, $new);
        self::assertSame(42, $new->getAttribute('id'));
        self::assertNull($request->getAttribute('id'));
    }

    #[Test]
    public function without_attribute_returns_new_instance(): void
    {
        $request = new Request((new ServerRequest('GET', '/'))->withAttribute('id', 42));
        $new = $request->withoutAttribute('id');

        self::assertNotSame($request, $new);
        self::assertNull($new->getAttribute('id'));
    }

    #[Test]
    public function with_body_returns_new_instance(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        $stream = \Nyholm\Psr7\Stream::create('hello');
        $new = $request->withBody($stream);

        self::assertNotSame($request, $new);
        self::assertSame('hello', (string) $new->getBody());
    }

    // --- method() override ---

    #[Test]
    public function method_override_via_body_method_field(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['_method' => 'PUT']);
        $request = new Request($inner);

        self::assertSame('PUT', $request->method());
    }

    #[Test]
    public function method_override_via_x_http_method_override_header(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('X-HTTP-Method-Override', 'DELETE');
        $request = new Request($inner);

        self::assertSame('DELETE', $request->method());
    }

    #[Test]
    public function method_override_only_on_post(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withParsedBody(['_method' => 'DELETE']);
        $request = new Request($inner);

        self::assertSame('GET', $request->method());
    }

    #[Test]
    public function body_method_takes_precedence_over_header(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['_method' => 'PATCH'])
            ->withHeader('X-HTTP-Method-Override', 'DELETE');
        $request = new Request($inner);

        self::assertSame('PATCH', $request->method());
    }

    // --- realMethod() ---

    #[Test]
    public function real_method_always_returns_actual_method(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['_method' => 'PUT']);
        $request = new Request($inner);

        self::assertSame('POST', $request->realMethod());
    }

    // --- isGet, isPost, etc. ---

    #[Test]
    public function is_get(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));
        self::assertTrue($request->isGet());
        self::assertFalse($request->isPost());
    }

    #[Test]
    public function is_post(): void
    {
        $request = new Request(new ServerRequest('POST', '/'));
        self::assertTrue($request->isPost());
        self::assertFalse($request->isGet());
    }

    #[Test]
    public function is_put(): void
    {
        $request = new Request(new ServerRequest('PUT', '/'));
        self::assertTrue($request->isPut());
    }

    #[Test]
    public function is_patch(): void
    {
        $request = new Request(new ServerRequest('PATCH', '/'));
        self::assertTrue($request->isPatch());
    }

    #[Test]
    public function is_delete(): void
    {
        $request = new Request(new ServerRequest('DELETE', '/'));
        self::assertTrue($request->isDelete());
    }

    #[Test]
    public function is_options(): void
    {
        $request = new Request(new ServerRequest('OPTIONS', '/'));
        self::assertTrue($request->isOptions());
    }

    #[Test]
    public function is_head(): void
    {
        $request = new Request(new ServerRequest('HEAD', '/'));
        self::assertTrue($request->isHead());
    }

    // --- query() ---

    #[Test]
    public function query_with_key(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withQueryParams(['page' => '2', 'sort' => 'name']);
        $request = new Request($inner);

        self::assertSame('2', $request->query('page'));
        self::assertSame('name', $request->query('sort'));
    }

    #[Test]
    public function query_without_key_returns_all(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withQueryParams(['a' => '1', 'b' => '2']);
        $request = new Request($inner);

        self::assertSame(['a' => '1', 'b' => '2'], $request->query());
    }

    #[Test]
    public function query_with_default(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertSame(1, $request->query('page', 1));
        self::assertNull($request->query('missing'));
    }

    // --- input() ---

    #[Test]
    public function input_from_parsed_body(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['email' => 'user@example.com', 'role' => 'admin']);
        $request = new Request($inner);

        self::assertSame('user@example.com', $request->input('email'));
        self::assertSame('admin', $request->input('role'));
        self::assertNull($request->input('missing'));
        self::assertSame('default', $request->input('missing', 'default'));
    }

    // --- all() ---

    #[Test]
    public function all_merges_query_and_body_with_body_winning(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withQueryParams(['key' => 'from_query', 'only_query' => 'q'])
            ->withParsedBody(['key' => 'from_body', 'only_body' => 'b']);
        $request = new Request($inner);

        $all = $request->all();

        self::assertSame('from_body', $all['key']);
        self::assertSame('q', $all['only_query']);
        self::assertSame('b', $all['only_body']);
    }

    #[Test]
    public function all_handles_object_parsed_body(): void
    {
        $body = new \stdClass();
        $body->email = 'test@example.com';
        $body->name = 'Omar';

        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody($body);
        $request = new Request($inner);

        $all = $request->all();

        self::assertSame('test@example.com', $all['email']);
        self::assertSame('Omar', $all['name']);
    }

    // --- only(), except() ---

    #[Test]
    public function only_returns_subset(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John', 'email' => 'j@e.com', 'role' => 'admin']);
        $request = new Request($inner);

        self::assertSame(['name' => 'John', 'email' => 'j@e.com'], $request->only(['name', 'email']));
    }

    #[Test]
    public function except_excludes_keys(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John', 'email' => 'j@e.com', 'role' => 'admin']);
        $request = new Request($inner);

        $result = $request->except(['role']);
        self::assertArrayHasKey('name', $result);
        self::assertArrayHasKey('email', $result);
        self::assertArrayNotHasKey('role', $result);
    }

    // --- has(), hasAny(), filled(), missing() ---

    #[Test]
    public function has_checks_all_keys_present(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John', 'email' => 'j@e.com']);
        $request = new Request($inner);

        self::assertTrue($request->has('name'));
        self::assertTrue($request->has(['name', 'email']));
        self::assertFalse($request->has(['name', 'missing']));
    }

    #[Test]
    public function has_any_checks_at_least_one_key(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John']);
        $request = new Request($inner);

        self::assertTrue($request->hasAny(['name', 'email']));
        self::assertFalse($request->hasAny(['email', 'age']));
    }

    #[Test]
    public function filled_checks_non_empty_value(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John', 'empty' => '', 'space' => '  ', 'zero' => 0, 'false' => false, 'null' => null]);
        $request = new Request($inner);

        self::assertTrue($request->filled('name'));
        self::assertFalse($request->filled('empty'));
        self::assertFalse($request->filled('space'));
        self::assertTrue($request->filled('zero'));
        self::assertTrue($request->filled('false'));
        self::assertFalse($request->filled('null'));
        self::assertFalse($request->filled('missing'));
    }

    #[Test]
    public function missing_checks_key_absent(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['name' => 'John']);
        $request = new Request($inner);

        self::assertTrue($request->missing('email'));
        self::assertFalse($request->missing('name'));
    }

    // --- string(), integer(), float(), boolean() ---

    #[Test]
    public function string_accessor(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['name' => 'John', 'num' => '42']);
        $request = new Request($inner);

        self::assertSame('John', $request->string('name'));
        self::assertSame('42', $request->string('num'));
        self::assertSame('', $request->string('missing'));
        self::assertSame('default', $request->string('missing', 'default'));
    }

    #[Test]
    public function string_accessor_with_non_scalar_returns_default(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['arr' => ['a', 'b']]);
        $request = new Request($inner);

        self::assertSame('fallback', $request->string('arr', 'fallback'));
    }

    #[Test]
    public function integer_accessor(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['page' => '5', 'bad' => 'abc']);
        $request = new Request($inner);

        self::assertSame(5, $request->integer('page'));
        self::assertSame(0, $request->integer('bad'));
        self::assertSame(1, $request->integer('missing', 1));
    }

    #[Test]
    public function float_accessor(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['price' => '19.99', 'bad' => 'abc']);
        $request = new Request($inner);

        self::assertSame(19.99, $request->float('price'));
        self::assertSame(0.0, $request->float('bad'));
        self::assertSame(1.5, $request->float('missing', 1.5));
    }

    #[Test]
    public function boolean_accessor_truthy(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams([
                'a' => '1',
                'b' => 'true',
                'c' => 'on',
                'd' => 'yes',
            ]);
        $request = new Request($inner);

        self::assertTrue($request->boolean('a'));
        self::assertTrue($request->boolean('b'));
        self::assertTrue($request->boolean('c'));
        self::assertTrue($request->boolean('d'));
    }

    #[Test]
    public function boolean_accessor_falsy(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams([
                'a' => '0',
                'b' => 'false',
                'c' => 'off',
                'd' => 'no',
                'e' => '',
            ]);
        $request = new Request($inner);

        self::assertFalse($request->boolean('a'));
        self::assertFalse($request->boolean('b'));
        self::assertFalse($request->boolean('c'));
        self::assertFalse($request->boolean('d'));
        self::assertFalse($request->boolean('e'));
    }

    #[Test]
    public function boolean_accessor_default(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['weird' => 'maybe']);
        $request = new Request($inner);

        self::assertFalse($request->boolean('missing'));
        self::assertTrue($request->boolean('missing', true));
        self::assertFalse($request->boolean('weird'));
    }

    // --- date() ---

    #[Test]
    public function date_with_format(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['date' => '2024-06-15']);
        $request = new Request($inner);

        $date = $request->date('date', 'Y-m-d');

        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertSame('2024-06-15', $date->format('Y-m-d'));
    }

    #[Test]
    public function date_without_format(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['date' => '2024-06-15 12:00:00']);
        $request = new Request($inner);

        $date = $request->date('date');

        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertSame('2024', $date->format('Y'));
    }

    #[Test]
    public function date_returns_null_for_missing_or_invalid(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['bad' => 'not-a-date', 'empty' => '']);
        $request = new Request($inner);

        self::assertNull($request->date('missing'));
        self::assertNull($request->date('bad', 'Y-m-d'));
        self::assertNull($request->date('empty'));
    }

    // --- enum() ---

    #[Test]
    public function enum_with_backed_enum(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['color' => 'red']);
        $request = new Request($inner);

        $color = $request->enum('color', Color::class);

        self::assertSame(Color::Red, $color);
    }

    #[Test]
    public function enum_returns_null_for_invalid_value(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withQueryParams(['color' => 'green']);
        $request = new Request($inner);

        self::assertNull($request->enum('color', Color::class));
    }

    #[Test]
    public function enum_returns_null_for_missing_key(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertNull($request->enum('color', Color::class));
    }

    // --- header(), bearerToken(), basicCredentials() ---

    #[Test]
    public function header_returns_first_value(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('X-Custom', 'value1');
        $request = new Request($inner);

        self::assertSame('value1', $request->header('X-Custom'));
        self::assertNull($request->header('Missing'));
        self::assertSame('default', $request->header('Missing', 'default'));
    }

    #[Test]
    public function bearer_token(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Authorization', 'Bearer abc123token');
        $request = new Request($inner);

        self::assertSame('abc123token', $request->bearerToken());
    }

    #[Test]
    public function bearer_token_returns_null_when_missing(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertNull($request->bearerToken());
    }

    #[Test]
    public function bearer_token_returns_null_for_non_bearer(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Authorization', 'Basic abc123');
        $request = new Request($inner);

        self::assertNull($request->bearerToken());
    }

    #[Test]
    public function basic_credentials(): void
    {
        $encoded = base64_encode('user:pass');
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Authorization', 'Basic ' . $encoded);
        $request = new Request($inner);

        $creds = $request->basicCredentials();

        self::assertNotNull($creds);
        self::assertSame('user', $creds['username']);
        self::assertSame('pass', $creds['password']);
    }

    #[Test]
    public function basic_credentials_returns_null_when_missing(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertNull($request->basicCredentials());
    }

    #[Test]
    public function basic_credentials_returns_null_for_non_basic(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Authorization', 'Bearer token');
        $request = new Request($inner);

        self::assertNull($request->basicCredentials());
    }

    // --- userAgent(), contentType(), contentLength() ---

    #[Test]
    public function user_agent(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('User-Agent', 'TestBot/1.0');
        $request = new Request($inner);

        self::assertSame('TestBot/1.0', $request->userAgent());
    }

    #[Test]
    public function content_type(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        $request = new Request($inner);

        self::assertSame('application/json', $request->contentType());
    }

    #[Test]
    public function content_type_without_params(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'text/plain');
        $request = new Request($inner);

        self::assertSame('text/plain', $request->contentType());
    }

    #[Test]
    public function content_type_empty_when_missing(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertSame('', $request->contentType());
    }

    #[Test]
    public function content_length(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Length', '1024');
        $request = new Request($inner);

        self::assertSame(1024, $request->contentLength());
    }

    #[Test]
    public function content_length_null_when_missing(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertNull($request->contentLength());
    }

    #[Test]
    public function content_length_null_for_invalid(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Length', 'abc');
        $request = new Request($inner);

        self::assertNull($request->contentLength());
    }

    // --- ip() ---

    #[Test]
    public function ip_without_trusted_proxy_uses_remote_addr(): void
    {
        $inner = new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '203.0.113.50']);
        $request = new Request($inner);

        self::assertSame('203.0.113.50', $request->ip());
    }

    #[Test]
    public function ip_ignores_forwarded_for_without_trusted_proxy(): void
    {
        $inner = (new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-For', '203.0.113.50');
        $request = new Request($inner);

        self::assertSame('10.0.0.1', $request->ip());
    }

    #[Test]
    public function ip_with_trusted_proxy_and_x_forwarded_for(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.1'], trustedHeaders: Request::HEADER_X_FORWARDED_FOR);
        $inner = (new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-For', '203.0.113.50, 10.0.0.1');
        $request = new Request($inner, $config);

        self::assertSame('203.0.113.50', $request->ip());
    }

    // --- scheme(), host(), isSecure() with trusted proxy ---

    #[Test]
    public function scheme_without_trusted_proxy(): void
    {
        $request = new Request(new ServerRequest('GET', 'http://example.com/'));

        self::assertSame('http', $request->scheme());
    }

    #[Test]
    public function scheme_with_trusted_proxy(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.1'], trustedHeaders: Request::HEADER_X_FORWARDED_PROTO);
        $inner = (new ServerRequest('GET', 'http://example.com/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-Proto', 'https');
        $request = new Request($inner, $config);

        self::assertSame('https', $request->scheme());
    }

    #[Test]
    public function host_with_trusted_proxy(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.1'], trustedHeaders: Request::HEADER_X_FORWARDED_HOST);
        $inner = (new ServerRequest('GET', 'http://internal.local/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-Host', 'example.com');
        $request = new Request($inner, $config);

        self::assertSame('example.com', $request->host());
    }

    #[Test]
    public function is_secure_with_trusted_proxy(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.1'], trustedHeaders: Request::HEADER_X_FORWARDED_PROTO);
        $inner = (new ServerRequest('GET', 'http://example.com/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-Proto', 'https');
        $request = new Request($inner, $config);

        self::assertTrue($request->isSecure());
    }

    #[Test]
    public function is_not_secure(): void
    {
        $request = new Request(new ServerRequest('GET', 'http://example.com/'));

        self::assertFalse($request->isSecure());
    }

    // --- isXhr(), isJson(), isPrefetch() ---

    #[Test]
    public function is_xhr(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = new Request($inner);

        self::assertTrue($request->isXhr());
    }

    #[Test]
    public function is_not_xhr(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertFalse($request->isXhr());
    }

    #[Test]
    public function is_json(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'application/json');
        $request = new Request($inner);

        self::assertTrue($request->isJson());
    }

    #[Test]
    public function is_json_with_plus_json(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'application/vnd.api+json');
        $request = new Request($inner);

        self::assertTrue($request->isJson());
    }

    #[Test]
    public function is_not_json(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'text/html');
        $request = new Request($inner);

        self::assertFalse($request->isJson());
    }

    #[Test]
    public function is_prefetch_via_purpose(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Purpose', 'prefetch');
        $request = new Request($inner);

        self::assertTrue($request->isPrefetch());
    }

    #[Test]
    public function is_prefetch_via_sec_purpose(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Sec-Purpose', 'prefetch');
        $request = new Request($inner);

        self::assertTrue($request->isPrefetch());
    }

    #[Test]
    public function is_not_prefetch(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertFalse($request->isPrefetch());
    }

    // --- path(), url(), fullUrl() ---

    #[Test]
    public function path(): void
    {
        $request = new Request(new ServerRequest('GET', '/users/42'));

        self::assertSame('/users/42', $request->path());
    }

    #[Test]
    public function path_defaults_to_slash(): void
    {
        $request = new Request(new ServerRequest('GET', ''));

        self::assertSame('/', $request->path());
    }

    #[Test]
    public function url_without_query(): void
    {
        $request = new Request(new ServerRequest('GET', 'http://example.com/users'));

        self::assertSame('http://example.com/users', $request->url());
    }

    #[Test]
    public function full_url_with_query(): void
    {
        $request = new Request(new ServerRequest('GET', 'http://example.com/users?page=2'));

        self::assertSame('http://example.com/users?page=2', $request->fullUrl());
    }

    // --- segment(), segments() ---

    #[Test]
    public function segments(): void
    {
        $request = new Request(new ServerRequest('GET', '/api/users/42'));

        self::assertSame(['api', 'users', '42'], $request->segments());
    }

    #[Test]
    public function segments_empty_for_root(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertSame([], $request->segments());
    }

    #[Test]
    public function segment_by_index(): void
    {
        $request = new Request(new ServerRequest('GET', '/api/users/42'));

        self::assertSame('api', $request->segment(1));
        self::assertSame('users', $request->segment(2));
        self::assertSame('42', $request->segment(3));
        self::assertNull($request->segment(4));
        self::assertSame('default', $request->segment(4, 'default'));
    }

    // --- is() ---

    #[Test]
    public function is_with_wildcard_patterns(): void
    {
        $request = new Request(new ServerRequest('GET', '/api/users/42'));

        self::assertFalse($request->is('api/*'));
        self::assertTrue($request->is('api/**'));
        self::assertTrue($request->is('api/users/*'));
        self::assertFalse($request->is('admin/*'));
    }

    #[Test]
    public function is_with_exact_match(): void
    {
        $request = new Request(new ServerRequest('GET', '/api/users'));

        self::assertTrue($request->is('api/users'));
        self::assertFalse($request->is('api/posts'));
    }

    #[Test]
    public function is_with_multiple_patterns(): void
    {
        $request = new Request(new ServerRequest('GET', '/admin/settings'));

        self::assertTrue($request->is('api/*', 'admin/*'));
        self::assertFalse($request->is('api/*', 'users/*'));
    }

    // --- cookie(), cookies(), hasCookie() ---

    #[Test]
    public function cookie_returns_value(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withCookieParams(['sid' => 'abc123']);
        $request = new Request($inner);

        self::assertSame('abc123', $request->cookie('sid'));
        self::assertNull($request->cookie('missing'));
        self::assertSame('default', $request->cookie('missing', 'default'));
    }

    #[Test]
    public function cookies_returns_all(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withCookieParams(['a' => '1', 'b' => '2']);
        $request = new Request($inner);

        self::assertSame(['a' => '1', 'b' => '2'], $request->cookies());
    }

    #[Test]
    public function has_cookie(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withCookieParams(['sid' => 'abc']);
        $request = new Request($inner);

        self::assertTrue($request->hasCookie('sid'));
        self::assertFalse($request->hasCookie('missing'));
    }

    // --- file(), hasFile() ---

    #[Test]
    public function file_returns_uploaded_file(): void
    {
        $file = new UploadedFile('content', 7, UPLOAD_ERR_OK, 'test.txt', 'text/plain');
        $inner = (new ServerRequest('POST', '/'))->withUploadedFiles(['avatar' => $file]);
        $request = new Request($inner);

        self::assertSame($file, $request->file('avatar'));
        self::assertNull($request->file('missing'));
    }

    #[Test]
    public function has_file(): void
    {
        $file = new UploadedFile('content', 7, UPLOAD_ERR_OK, 'test.txt', 'text/plain');
        $inner = (new ServerRequest('POST', '/'))->withUploadedFiles(['avatar' => $file]);
        $request = new Request($inner);

        self::assertTrue($request->hasFile('avatar'));
        self::assertFalse($request->hasFile('missing'));
    }

    #[Test]
    public function has_file_returns_false_for_upload_error(): void
    {
        $file = new UploadedFile('', 0, UPLOAD_ERR_NO_FILE);
        $inner = (new ServerRequest('POST', '/'))->withUploadedFiles(['avatar' => $file]);
        $request = new Request($inner);

        self::assertFalse($request->hasFile('avatar'));
    }

    // --- route() ---

    #[Test]
    public function route_reads_from_attributes(): void
    {
        $inner = (new ServerRequest('GET', '/'))->withAttribute('id', '42');
        $request = new Request($inner);

        self::assertSame('42', $request->route('id'));
        self::assertNull($request->route('missing'));
        self::assertSame('fallback', $request->route('missing', 'fallback'));
    }

    // --- psr() ---

    #[Test]
    public function psr_returns_inner_request(): void
    {
        $inner = new ServerRequest('GET', '/');
        $request = new Request($inner);

        self::assertSame($inner, $request->psr());
    }

    // --- wantsJson(), accepts() ---

    #[Test]
    public function wants_json(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'application/json');
        $request = new Request($inner);

        self::assertTrue($request->wantsJson());
    }

    #[Test]
    public function wants_json_with_plus_json(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'application/vnd.api+json');
        $request = new Request($inner);

        self::assertTrue($request->wantsJson());
    }

    #[Test]
    public function does_not_want_json(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'text/html');
        $request = new Request($inner);

        self::assertFalse($request->wantsJson());
    }

    #[Test]
    public function does_not_want_json_when_no_accept(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertFalse($request->wantsJson());
    }

    #[Test]
    public function accepts_content_type(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'text/html, application/json');
        $request = new Request($inner);

        self::assertTrue($request->accepts('application/json'));
        self::assertTrue($request->accepts('text/html'));
        self::assertFalse($request->accepts('application/xml'));
    }

    #[Test]
    public function accepts_with_wildcard(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', '*/*');
        $request = new Request($inner);

        self::assertTrue($request->accepts('application/json'));
    }

    #[Test]
    public function accepts_with_no_accept_header(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertTrue($request->accepts('application/json'));
    }

    #[Test]
    public function accepts_array_of_types(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'text/html');
        $request = new Request($inner);

        self::assertTrue($request->accepts(['text/html', 'application/json']));
        self::assertFalse($request->accepts(['application/xml', 'application/json']));
    }

    // --- isMethod() ---

    #[Test]
    public function is_method_case_insensitive(): void
    {
        $request = new Request(new ServerRequest('GET', '/'));

        self::assertTrue($request->isMethod('get'));
        self::assertTrue($request->isMethod('GET'));
        self::assertFalse($request->isMethod('POST'));
    }

    // --- getAttributes() ---

    #[Test]
    public function get_attributes(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withAttribute('a', 1)
            ->withAttribute('b', 2);
        $request = new Request($inner);

        $attrs = $request->getAttributes();
        self::assertSame(1, $attrs['a']);
        self::assertSame(2, $attrs['b']);
    }

    // --- headers() ---

    #[Test]
    public function headers_returns_all_values(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('X-Multi', 'a')
            ->withAddedHeader('X-Multi', 'b');
        $request = new Request($inner);

        self::assertSame(['a', 'b'], $request->headers('X-Multi'));
    }

    // --- ips() ---

    #[Test]
    public function ips_returns_forwarded_chain(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.0/8'], trustedHeaders: Request::HEADER_X_FORWARDED_ALL);
        $inner = (new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-For', '203.0.113.50, 198.51.100.1');
        $request = new Request($inner, $config);

        self::assertSame(['203.0.113.50', '198.51.100.1'], $request->ips());
    }

    #[Test]
    public function ips_ignores_forwarded_for_without_trusted_proxy(): void
    {
        $inner = (new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '203.0.113.50']))
            ->withHeader('X-Forwarded-For', '10.0.0.1, 192.168.1.1');
        $request = new Request($inner);

        self::assertSame(['203.0.113.50'], $request->ips());
    }

    #[Test]
    public function ips_returns_remote_addr_when_no_forwarded(): void
    {
        $inner = new ServerRequest('GET', '/', [], null, '1.1', ['REMOTE_ADDR' => '203.0.113.50']);
        $request = new Request($inner);

        self::assertSame(['203.0.113.50'], $request->ips());
    }

    // --- port() ---

    #[Test]
    public function port_with_trusted_proxy(): void
    {
        $config = new HttpConfig(trustedProxies: ['10.0.0.1'], trustedHeaders: Request::HEADER_X_FORWARDED_PORT);
        $inner = (new ServerRequest('GET', 'http://example.com/', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-Port', '443');
        $request = new Request($inner, $config);

        self::assertSame(443, $request->port());
    }

    // --- array() ---

    #[Test]
    public function array_accessor(): void
    {
        $inner = (new ServerRequest('POST', '/'))
            ->withParsedBody(['tags' => ['a', 'b'], 'name' => 'John']);
        $request = new Request($inner);

        self::assertSame(['0' => 'a', '1' => 'b'], $request->array('tags'));
        self::assertSame([], $request->array('name'));
        self::assertSame([], $request->array('missing'));
    }

    // --- allFiles() ---

    #[Test]
    public function all_files(): void
    {
        $file = new UploadedFile('content', 7, UPLOAD_ERR_OK);
        $inner = (new ServerRequest('POST', '/'))->withUploadedFiles(['doc' => $file]);
        $request = new Request($inner);

        $files = $request->allFiles();
        self::assertArrayHasKey('doc', $files);
    }

    // --- preferredType() ---

    #[Test]
    public function preferred_type(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept', 'text/html, application/json;q=0.9');
        $request = new Request($inner);

        self::assertSame('text/html', $request->preferredType(['application/json', 'text/html']));
    }

    // --- preferredLanguage() ---

    #[Test]
    public function preferred_language(): void
    {
        $inner = (new ServerRequest('GET', '/'))
            ->withHeader('Accept-Language', 'en-US, fr;q=0.8');
        $request = new Request($inner);

        self::assertSame('en', $request->preferredLanguage(['en', 'fr']));
    }
}
