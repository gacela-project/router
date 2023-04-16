<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router;

use Gacela\Router\MappingInterfaces;
use Gacela\Router\Router;
use Gacela\Router\Routes;
use GacelaTest\Unit\Router\Fake\Name;
use GacelaTest\Unit\Router\Fake\NameInterface;
use GacelaTest\Unit\Router\Fixtures\FakeControllerWithDependencies;
use GacelaTest\Unit\Router\Fixtures\FakeControllerWithRequest;
use PHPUnit\Framework\TestCase;

final class RouterInjectionTest extends TestCase
{
    public function test_inject_dependencies_in_controllers(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('default-Expected!');

        Router::configure(static function (Routes $routes, MappingInterfaces $mappingInterfaces): void {
            $routes->get('expected/uri', FakeControllerWithDependencies::class);
            $mappingInterfaces->add(NameInterface::class, new Name('Expected!'));
        });
    }

    public function test_inject_controller_with_request_dependency(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['name'] = 'Katarn';

        $this->expectOutputString('Katarn');

        Router::configure(static function (Routes $routes): void {
            $routes->get('expected', FakeControllerWithRequest::class);
        });
    }
}
