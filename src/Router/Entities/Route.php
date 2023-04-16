<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Resolver\InstanceCreator;
use Gacela\Router\RouterConfigurator;

use function is_object;

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
     * @psalm-suppress MixedMethodCall
     */
    public function run(RouterConfigurator $routerConfigurator): string
    {
        $params = (new RouteParams($this))->asArray();

        if (is_object($this->controller)) {
            return (string)$this->controller->{$this->action}(...$params);
        }

        $creator = new InstanceCreator($routerConfigurator->getMappingInterfaces());
        $controller = $creator->createByClassName($this->controller);

        return (string)$controller->{$this->action}(...$params);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function method(): string
    {
        return $this->method;
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

    public function requestMatches(): bool
    {
        if (!$this->methodMatches()) {
            return false;
        }

        if (!$this->pathMatches()) {
            return false;
        }

        return true;
    }

    public function methodMatches(): bool
    {
        return Request::fromGlobals()->isMethod($this->method);
    }

    private function pathMatches(): bool
    {
        $path = Request::fromGlobals()->path();

        return preg_match($this->getPathPattern(), $path)
            || preg_match($this->getPathPatternWithoutOptionals(), $path);
    }

    private function getPathPatternWithoutOptionals(): string
    {
        $pattern = preg_replace('#/({.*\?})#U', '(/(.*))?', $this->path);

        return '#^/' . $pattern . '$#';
    }
}
