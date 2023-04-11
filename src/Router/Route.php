<?php

declare(strict_types=1);

namespace Gacela\Router;

use function is_object;

/**
 * @method static get(string $path, object|string $controller, string $action = '__invoke')
 * @method static head(string $path, object|string $controller, string $action = '__invoke')
 * @method static connect(string $path, object|string $controller, string $action = '__invoke')
 * @method static post(string $path, object|string $controller, string $action = '__invoke')
 * @method static delete(string $path, object|string $controller, string $action = '__invoke')
 * @method static options(string $path, object|string $controller, string $action = '__invoke')
 * @method static patch(string $path, object|string $controller, string $action = '__invoke')
 * @method static put(string $path, object|string $controller, string $action = '__invoke')
 * @method static trace(string $path, object|string $controller, string $action = '__invoke')
 */
final class Route
{
    /**
     * @param object|class-string $controller
     */
    public function __construct(
        private string $method,
        private string $path,
        private object|string $controller,
        private string $action = '__invoke',
    ) {
    }

    /**
     * @param callable(RoutingConfigurator):void $fn
     */
    public static function configure(callable $fn): void
    {
        $routingConfigurator = new RoutingConfigurator();
        $fn($routingConfigurator);

        foreach ($routingConfigurator->routes() as $route) {
            if ($route->requestMatches()) {
                echo $route->run();
                break;
            }
        }
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function run(): string
    {
        $params = (new RouteParams($this))->asArray();

        if (is_object($this->controller)) {
            return (string)$this->controller
                ->{$this->action}(...$params);
        }

        return (string)(new $this->controller())
            ->{$this->action}(...$params);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return object|class-string
     */
    public function controller(): object|string
    {
        return $this->controller;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function getPathPattern(): string
    {
        $pattern = preg_replace('#({.*})#U', '(.*)', $this->path);

        return '#^/' . $pattern . '$#';
    }

    private function requestMatches(): bool
    {
        if (!$this->methodMatches()) {
            return false;
        }

        if (!$this->pathMatches()) {
            return false;
        }

        return true;
    }

    private function methodMatches(): bool
    {
        return Request::instance()->isMethod($this->method);
    }

    private function pathMatches(): bool
    {
        $path = Request::instance()->path();

        return preg_match($this->getPathPattern(), $path)
            || preg_match($this->getPathPatternWithoutOptionals(), $path);
    }

    private function getPathPatternWithoutOptionals(): string
    {
        $pattern = preg_replace('#/({.*\?})#U', '(/(.*))?', $this->path);

        return '#^/' . $pattern . '$#';
    }
}
