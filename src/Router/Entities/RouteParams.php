<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use ReflectionClass;
use ReflectionNamedType;

use function count;

final class RouteParams
{
    private array $asArray;

    public function __construct(
        private Route $route,
    ) {
        $this->asArray = $this->getParams();
    }

    public function asArray(): array
    {
        return $this->asArray;
    }

    /**
     * @psalm-suppress MixedAssignment,PossiblyNullReference
     */
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
            $paramType = null;

            if ($actionParam->getType() && is_a($actionParam->getType(), ReflectionNamedType::class)) {
                $paramType = $actionParam->getType()->getName();
            }

            $paramName = $actionParam->getName();

            $value = match ($paramType) {
                'string' => $pathParams[$paramName],
                'int' => (int)$pathParams[$paramName],
                'float' => (float)$pathParams[$paramName],
                'bool' => (bool)json_decode($pathParams[$paramName]),
                null => null,
            };

            $params[$paramName] = $value;
        }

        return $params;
    }
}
