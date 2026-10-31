<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router;

use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\Router\Fixtures\FakeController;
use GacelaTest\Feature\Router\Fixtures\FakeControllerWithRequest;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouterParamTest extends TestCase
{
    public function test_pass_many_params_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The params are '{$params[0]}', '{$params[1]}' and '{$params[2]}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('{firstParam}/{secondParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
        $router->run();
    }

    public function test_pass_associated_params_by_name_to_the_action(): void
    {
        $params = ['foo', 'bar', 'baz'];

        $_SERVER['REQUEST_URI'] = "https://example.org/{$params[0]}/{$params[1]}/{$params[2]}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The params are '{$params[1]}', '{$params[0]}' and '{$params[2]}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('{secondParam}/{firstParam}/{thirdParam}', FakeController::class, 'manyParamsAction');
        });
        $router->run();
    }

    #[DataProvider('stringProvider')]
    public function test_pass_string_params_to_the_action(string $string): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/string/is/{$string}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'string' param is '{$string}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/string/is/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();
    }

    public static function stringProvider(): Generator
    {
        yield 'word' => ['string' => 'word'];
        yield 'zero, which is falsy' => ['string' => '0'];
        yield 'upper case' => ['string' => 'UPPER'];
        yield 'with dashes' => ['string' => 'with-some-dashes'];
        yield 'digits only' => ['string' => '12345'];
    }

    public function test_do_not_pass_optional_string_params_to_the_action(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/string/is';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The optional 'string' param is 'bob'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/string/is/{param?}', FakeController::class, 'optionalStringParamAction');
        });
        $router->run();
    }

    public function test_pass_optional_string_params_to_the_action(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/string/is/alice';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The optional 'string' param is 'alice'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/string/is/{param?}', FakeController::class, 'optionalStringParamAction');
        });
        $router->run();
    }

    #[DataProvider('trailingOptionalProvider')]
    public function test_an_omitted_trailing_optional_keeps_every_other_param_bound(
        string $path,
        string $action,
        string $uri,
        string $expected,
    ): void {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString($expected);

        $router = new Router(static function (Routes $routes) use ($path, $action): void {
            $routes->get($path, FakeController::class, $action);
        });
        $router->run();
    }

    public static function trailingOptionalProvider(): Generator
    {
        $withComment = 'posts/{id}/comments/{commentId?}';

        yield 'mandatory, optional omitted' => [$withComment, 'mandatoryThenOptionalAction', '/posts/5/comments', 'id=5 commentId=-1'];
        yield 'mandatory, optional given' => [$withComment, 'mandatoryThenOptionalAction', '/posts/5/comments/9', 'id=5 commentId=9'];
        yield 'only optionals, none given' => ['{a?}/{b?}', 'twoOptionalsAction', '/', 'a=A b=B'];
        yield 'only optionals, first given' => ['{a?}/{b?}', 'twoOptionalsAction', '/x', 'a=x b=B'];
        yield 'only optionals, both given' => ['{a?}/{b?}', 'twoOptionalsAction', '/x/y', 'a=x b=y'];
        yield 'static prefix, none given' => ['a/{a?}/{b?}', 'twoOptionalsAction', '/a', 'a=A b=B'];
        yield 'static prefix, first given' => ['a/{a?}/{b?}', 'twoOptionalsAction', '/a/1', 'a=1 b=B'];
        yield 'static prefix, both given' => ['a/{a?}/{b?}', 'twoOptionalsAction', '/a/1/2', 'a=1 b=2'];
    }

    public function test_a_custom_pattern_with_an_extra_capture_still_maps_params_by_name(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/42';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'int' param is '42'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('api/{param}', FakeController::class, 'intParamAction', '#^/(api)/([^/]+)$#');
        });
        $router->run();
    }

    public function test_a_closure_controller_receives_its_path_params(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/7';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString('user 7');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('users/{id}', static fn (int $id): string => "user {$id}");
        });
        $router->run();
    }

    public function test_closures_with_different_signatures_do_not_share_one(): void
    {
        $router = new Router(static function (Routes $routes): void {
            $routes->get('users/{id}', static fn (int $id): string => "user {$id}");
            $routes->get('posts/{slug}', static fn (string $slug): string => "post {$slug}");
        });

        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $outputs = [];
        foreach (['/users/7', '/posts/hello'] as $uri) {
            $_SERVER['REQUEST_URI'] = $uri;

            ob_start();
            $router->run();
            $outputs[] = (string)ob_get_clean();
        }

        self::assertSame(['user 7', 'post hello'], $outputs);
    }

    #[DataProvider('encodedSegmentProvider')]
    public function test_a_path_param_reaches_the_action_percent_decoded(string $segment, string $expected): void
    {
        $_SERVER['REQUEST_URI'] = "/hello/{$segment}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'string' param is '{$expected}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('hello/{param}', FakeController::class, 'stringParamAction');
        });
        $router->run();
    }

    public static function encodedSegmentProvider(): Generator
    {
        yield 'utf-8 and a space' => ['caf%C3%A9%20bar', 'café bar'];
        yield 'an encoded slash' => ['a%2Fb', 'a/b'];
        yield 'a plus stays a plus' => ['a+b', 'a+b'];
        yield 'an encoded percent' => ['100%25', '100%'];
        yield 'an invalid sequence stays as written' => ['%zz', '%zz'];
        yield 'nothing to decode' => ['plain', 'plain'];
    }

    #[DataProvider('intProvider')]
    public function test_pass_int_params_to_the_action(string $int): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/integer/is/{$int}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'int' param is '{$int}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/integer/is/{param}', FakeController::class, 'intParamAction');
        });
        $router->run();
    }

    public static function intProvider(): Generator
    {
        yield 'zero, which is falsy' => ['int' => '0'];
        yield 'one' => ['int' => '1'];
        yield 'negative' => ['int' => '-5'];
        yield 'four digits' => ['int' => '9999'];
        yield 'the largest int' => ['int' => (string)PHP_INT_MAX];
    }

    #[DataProvider('floatProvider')]
    public function test_pass_float_params_to_the_action(string $float): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/float/is/{$float}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'float' param is '{$float}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/float/is/{param}', FakeController::class, 'floatParamAction');
        });
        $router->run();
    }

    public static function floatProvider(): Generator
    {
        yield 'zero, which is falsy' => ['float' => '0'];
        yield 'a whole number' => ['float' => '100'];
        yield 'a fraction' => ['float' => '1.5'];
        yield 'negative' => ['float' => '-2.25'];
    }

    #[DataProvider('boolProvider')]
    public function test_pass_bool_params_to_the_action(string $given, string $expected): void
    {
        $_SERVER['REQUEST_URI'] = "https://example.org/expected/bool/is/{$given}";
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        $this->expectOutputString("The 'bool' param is '{$expected}'!");

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/bool/is/{param}', FakeController::class, 'boolParamAction');
        });
        $router->run();
    }

    public static function boolProvider(): iterable
    {
        yield 'true' => ['given' => 'true', 'expected' => 'true'];
        yield 'false' => ['given' => 'false', 'expected' => 'false'];
        yield '1' => ['given' => '1', 'expected' => 'true'];
        yield '0' => ['given' => '0', 'expected' => 'false'];
    }

    public function test_priori_post_params_over_get(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/expected/uri';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_GET['name'] = 'Unexpected!';
        $_POST['name'] = 'Expected!';

        $this->expectOutputString('Expected!');

        $router = new Router(static function (Routes $routes): void {
            $routes->get('expected/uri', FakeControllerWithRequest::class);
        });
        $router->run();
    }
}
