<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Container\Container;
use Gacela\Router\Bindings;

use Gacela\Router\Exceptions\UnsupportedResponseTypeException;
use Stringable;

use function is_object;
use function is_string;

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
    public function run(Bindings $bindings): string|Stringable
    {
        $params = (new RouteParams($this))->asArray();

        if (!is_object($this->controller)) {
            $creator = new Container($bindings->getAllBindings());
            /** @var object $controller */
            $controller = $creator->get($this->controller);
            $response = $controller->{$this->action}(...$params);
        } else {
            /** @var string|Stringable $response */
            $response = $this->controller->{$this->action}(...$params);
        }

        if (!is_string($response) && !($response instanceof Stringable)) {
            throw UnsupportedResponseTypeException::fromType($response);
        }

        return $response;
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
        return $this->methodMatches() && $this->pathMatches();
    }

    private function methodMatches(): bool
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
