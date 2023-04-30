<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use ReflectionClass;

use function count;

final class RouteParams
{
    private array $params;

    public function __construct(
        private Route $route,
    ) {
        $this->params = $this->getParams();
    }

    public function getAll(): array
    {
        return $this->params;
    }

    private function getParams(): array
    {
        $params = [];
        $pathParamKeys = [];
        $pathParamValues = [];

        preg_match($this->route->getPathPattern(), '/' . $this->route->path(), $pathParamKeys);
        preg_match($this->route->getPathPattern(), Request::fromGlobals()->path(), $pathParamValues);

        unset($pathParamValues[0], $pathParamKeys[0]);
        $pathParamKeys = array_map(static fn ($key) => trim($key, '{}'), $pathParamKeys);

        while (count($pathParamValues) !== count($pathParamKeys)) {
            array_shift($pathParamKeys);
        }

        $pathParams = array_combine($pathParamKeys, $pathParamValues);
        $actionParams = (new ReflectionClass($this->route->controller()))
            ->getMethod($this->route->action())
            ->getParameters();

        foreach ($actionParams as $actionParam) {
            /** @var string|null $paramType */
            $paramType = $actionParam->getType()?->__toString();

            $paramName = $actionParam->getName();

            $value = match ($paramType) {
                'string' => $pathParams[$paramName],
                'int' => (int)$pathParams[$paramName],
                'float' => (float)$pathParams[$paramName],
                'bool' => (bool)json_decode($pathParams[$paramName]),
                null => throw UnsupportedParamTypeException::nonTyped(),
                default => throw UnsupportedParamTypeException::fromType($paramType),
            };

            $params[$paramName] = $value;
        }

        return $params;
    }
}
