<?php

declare(strict_types=1);

namespace Gacela\Router;

use ReflectionClass;
use ReflectionNamedType;

use function count;
use function is_object;

final class RouteEntity
{
    private static bool $isResponded = false;

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
     * @internal for testing
     */
    public static function reset(): void
    {
        self::$isResponded = false;
    }

    public function requestMatches(): bool
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
    public function run(): string
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
        return Request::method() === $this->method;
    }

    private function pathMatches(): bool
    {
        return (bool)preg_match($this->getPathPattern(), Request::path())
            || (bool)preg_match($this->getPathPatternWithoutOptionals(), Request::path());
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
        preg_match($this->getPathPattern(), Request::path(), $pathParamValues);

        unset($pathParamValues[0], $pathParamKeys[0]);
        $pathParamKeys = array_map(static fn ($key) => trim($key, '{}'), $pathParamKeys);

        if (count($pathParamValues) !== count($pathParamKeys)) {
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
}
