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

    public static function resetCache(): void
    {
        self::$request = null;
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
        if (is_object($this->controller)) {
            return (string)$this->controller
                ->{$this->action}(
                    ...$this->getParams()
                );
        }
        return (string)(new $this->controller())
            ->{$this->action}(
                ...$this->getParams()
            );
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
