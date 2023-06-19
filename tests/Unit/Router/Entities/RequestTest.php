<?php

declare(strict_types=1);

namespace Unit\Router\Entities;

use Gacela\Router\Entities\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_existing_request_key(): void
    {
        $_GET['key'] = 'value';
        $request = Request::fromGlobals();

        self::assertSame('value', $request->get('key'));
        unset($_GET['key']);
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
