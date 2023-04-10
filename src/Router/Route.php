<?php

declare(strict_types=1);

namespace Gacela\Router;

use ReflectionClass;
use ReflectionNamedType;

use function count;
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
    private static ?Request $request = null;

    private static bool $isResponded = false;

    /**
     * @param object|class-string $controller
     */
    private function __construct(
        private string $method,
        private string $path,
        private object|string $controller,
        private string $action = '__invoke',
    ) {
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public static function __callStatic(string $name, array $arguments): void
    {
        match ($name) {
            'head' => self::route(Request::METHOD_HEAD, ...$arguments),
            'connect' => self::route(Request::METHOD_CONNECT, ...$arguments),
            'post' => self::route(Request::METHOD_POST, ...$arguments),
            'delete' => self::route(Request::METHOD_DELETE, ...$arguments),
            'options' => self::route(Request::METHOD_OPTIONS, ...$arguments),
            'patch' => self::route(Request::METHOD_PATCH, ...$arguments),
            'put' => self::route(Request::METHOD_PUT, ...$arguments),
            'trace' => self::route(Request::METHOD_TRACE, ...$arguments),
            default => self::route(Request::METHOD_GET, ...$arguments),
        };
    }

    /**
     * @internal for testing
     */
    public static function reset(): void
    {
        self::$isResponded = false;
        self::$request = null;
    }

    /**
     * @param object|class-string $controller
     */
    private static function route(
        string $method,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        $path = ($path === '/') ? '' : $path;

        $route = new self($method, $path, $controller, $action);

        if ($route->requestMatches()) {
            echo $route->run();
        }
    }

    private function requestMatches(): bool
    {
        if (self::$isResponded) {
            return false;
        }

        if (!$this->methodMatches()) {
            return false;
        }

        if (!$this->pathMatches()) {
            return false;
        }

        return true;
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    private function run(): string
    {
        self::$isResponded = true;

        if (is_object($this->controller)) {
            return (string)$this->controller
                ->{$this->action}(...$this->getParams());
        }
        return (string)(new $this->controller())
            ->{$this->action}(...$this->getParams());
    }

    private function methodMatches(): bool
    {
        return $this->request()->isMethod($this->method);
    }

    private function pathMatches(): bool
    {
        return (bool)preg_match($this->getPathPattern(), $this->request()->path())
            || (bool)preg_match($this->getPathPatternWithoutOptionals(), $this->request()->path());
    }

    private function getPathPattern(): string
    {
        $pattern = preg_replace('#({.*})#U', '(.*)', $this->path);

        return '#^/' . $pattern . '$#';
    }

    private function getPathPatternWithoutOptionals(): string
    {
        $pattern = preg_replace('#/({.*\?})#U', '(/(.*))?', $this->path);

        return '#^/' . $pattern . '$#';
    }

    private function getParams(): array
    {
        $params = [];
        $pathParamKeys = [];
        $pathParamValues = [];

        preg_match($this->getPathPattern(), '/' . $this->path, $pathParamKeys);
        preg_match($this->getPathPattern(), $this->request()->path(), $pathParamValues);

        unset($pathParamValues[0], $pathParamKeys[0]);
        $pathParamKeys = array_map(static fn ($key) => trim($key, '{}'), $pathParamKeys);

        while (count($pathParamValues) !== count($pathParamKeys)) {
            array_shift($pathParamKeys);
        }

        $pathParams = array_combine($pathParamKeys, $pathParamValues);
        $actionParams = (new ReflectionClass($this->controller))
            ->getMethod($this->action)
            ->getParameters();

        foreach ($actionParams as $actionParam) {
            $paramName = $actionParam->getName();
            /** @var string|null $paramType */
            $paramType = null;

            if ($actionParam->getType() && is_a($actionParam->getType(), ReflectionNamedType::class)) {
                $paramType = $actionParam->getType()->getName();
            }

            $value = match ($paramType) {
                'string' => $pathParams[$paramName] ?? '',
                'int' => (int)($pathParams[$paramName] ?? 0),
                'float' => (float)($pathParams[$paramName] ?? 0.0),
                'bool' => (bool)json_decode($pathParams[$paramName] ?? '0'),
                null => null,
            };

            $params[$paramName] = $value;
        }

        return $params;
    }

    private function request(): Request
    {
        return self::$request ??= Request::fromGlobals();
    }
}
