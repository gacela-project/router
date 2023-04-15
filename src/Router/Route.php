<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Resolver\InstanceCreator;

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
    public function run(RoutingConfigurator $routingConfigurator): string
    {
        $params = (new RouteParams($this))->asArray();

        if (is_object($this->controller)) {
            return (string)$this->controller->{$this->action}(...$params);
        }

        $creator = new InstanceCreator($routingConfigurator->getMappingInterfaces());
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
        return Request::instance()->isMethod($this->method);
    }

    private function pathMatches(): bool
    {
        $path = Request::instance()->path();

        return preg_match($this->getPathPattern(), $path)
            || preg_match($this->getPathPatternWithoutOptionals(), $path);
    }

    public function isRedirected(Redirect $redirect): bool
    {
        return $this->path() === $redirect->destination()
            && $this->method() === $redirect->method();
    }

    private function getPathPatternWithoutOptionals(): string
    {
        $pattern = preg_replace('#/({.*\?})#U', '(/(.*))?', $this->path);

        return '#^/' . $pattern . '$#';
    }
}
