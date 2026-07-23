<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Entities;

use Gacela\Router\Entities\Request;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestPathTest extends TestCase
{
    #[DataProvider('wellFormedUriProvider')]
    public function test_a_well_formed_uri_keeps_its_path(string $requestUri, string $expected): void
    {
        self::assertSame($expected, self::requestWith(['REQUEST_URI' => $requestUri])->path());
    }

    public static function wellFormedUriProvider(): Generator
    {
        yield 'absolute path' => ['/expected/uri', '/expected/uri'];
        yield 'root' => ['/', '/'];
        yield 'full url' => ['https://example.org/expected/uri', '/expected/uri'];
        yield 'query stripped' => ['/expected/uri?a=1', '/expected/uri'];
        yield 'fragment stripped' => ['/expected/uri#frag', '/expected/uri'];
        yield 'url with root path' => ['https://example.org/', '/'];
    }

    public function test_a_missing_request_uri_falls_back_to_the_root(): void
    {
        self::assertSame('/', self::requestWith([])->path());
    }

    #[DataProvider('pathlessUriProvider')]
    public function test_a_uri_without_a_path_falls_back_to_the_root(string $requestUri): void
    {
        self::assertSame('/', self::requestWith(['REQUEST_URI' => $requestUri])->path());
    }

    public static function pathlessUriProvider(): Generator
    {
        // parse_url() returns null for these.
        yield 'host only' => ['https://example.org'];
        yield 'query only' => ['?a=1'];
        // parse_url() returns '' for this one.
        yield 'empty' => [''];
    }

    #[DataProvider('malformedUriProvider')]
    public function test_a_malformed_uri_falls_back_to_the_root(string $requestUri): void
    {
        self::assertSame('/', self::requestWith(['REQUEST_URI' => $requestUri])->path());
    }

    public static function malformedUriProvider(): Generator
    {
        // parse_url() returns false for each of these.
        yield 'double slash' => ['//'];
        yield 'triple slash' => ['///'];
        yield 'empty host' => ['http:///example.com'];
        yield 'colon only' => [':'];
        yield 'port without host' => ['http://:80'];
    }

    public function test_a_non_string_request_uri_falls_back_to_the_root(): void
    {
        self::assertSame('/', self::requestWith(['REQUEST_URI' => ['not', 'a', 'string']])->path());
    }

    public function test_a_missing_request_method_does_not_blow_up(): void
    {
        self::assertSame('', self::requestWith([])->method());
        self::assertFalse(self::requestWith([])->isMethod(Request::METHOD_GET));
    }

    public function test_a_non_string_request_method_does_not_blow_up(): void
    {
        self::assertSame('', self::requestWith(['REQUEST_METHOD' => 42])->method());
    }

    public function test_a_present_request_method_is_returned(): void
    {
        self::assertSame('GET', self::requestWith(['REQUEST_METHOD' => 'GET'])->method());
        self::assertTrue(self::requestWith(['REQUEST_METHOD' => 'GET'])->isMethod(Request::METHOD_GET));
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function requestWith(array $server): Request
    {
        $_GET = [];
        $_POST = [];
        $_SERVER = $server;

        return Request::fromGlobals();
    }
}
