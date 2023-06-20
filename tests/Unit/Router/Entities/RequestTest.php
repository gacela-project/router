<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Entities;

use Gacela\Router\Entities\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_existing_request_key_get(): void
    {
        $_GET['get_key'] = 'get value';
        $request = Request::fromGlobals();

        self::assertSame('get value', $request->get('get_key'));
        unset($_GET['get_key']);
    }

    public function test_existing_request_key_post(): void
    {
        $_POST['post_key'] = 'post value';
        $request = Request::fromGlobals();

        self::assertSame('post value', $request->get('post_key'));
        unset($_POST['post_key']);
    }

    public function test_non_existing_request_key(): void
    {
        $request = Request::fromGlobals();

        self::assertNull($request->get('non-existing-key'));
    }

    public function test_default_request_get_value(): void
    {
        $request = Request::fromGlobals();
        $actual = $request->get('non-existing-key', 'default-value');

        self::assertSame('default-value', $actual);
    }
}
