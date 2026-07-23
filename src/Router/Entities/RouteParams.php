<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use ReflectionClass;

use function count;
use function is_object;

final class RouteParams
{
    public const MANDATORY_PARAM_PATTERN = '#({.*[^?]})#';
    public const OPTIONAL_PARAM_PATTERN = '#(/?{.*\?})#';

    /**
     * Signatures never change within a process, so each controller action is
     * reflected once. One entry per unique `Controller::action`.
     *
     * @var array<string, list<array{name: string, type: string}>>
     */
    private static array $actionParamsCache = [];

    /** @var array<string, mixed> */
    private array $params;

    public function __construct(
        private Route $route,
        private Request $request,
    ) {
        $this->params = $this->getParams();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->params;
    }

    /**
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        $params = [];
        $pathParamKeys = [];
        $pathParamValues = [];

        preg_match($this->route->getPathPattern(), '/' . $this->route->path(), $pathParamKeys);
        preg_match($this->route->getPathPattern(), $this->request->path(), $pathParamValues);

        unset($pathParamValues[0], $pathParamKeys[0]);
        $pathParamKeys = array_map(static fn ($key) => trim($key, '{?}'), $pathParamKeys);

        while (count($pathParamValues) !== count($pathParamKeys)) {
            array_shift($pathParamKeys);
        }

        $pathParams = array_combine($pathParamKeys, $pathParamValues);

        foreach (self::actionParams($this->route) as ['name' => $paramName, 'type' => $paramType]) {
            if (isset($pathParams[$paramName])) {
                $value = match ($paramType) {
                    'string' => $pathParams[$paramName],
                    'int' => (int)$pathParams[$paramName],
                    'float' => (float)$pathParams[$paramName],
                    'bool' => (bool)json_decode($pathParams[$paramName]),
                    default => throw UnsupportedParamTypeException::fromType($paramType),
                };

                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    /**
     * @return list<array{name: string, type: string}>
     */
    private static function actionParams(Route $route): array
    {
        $controller = $route->controller();
        $action = $route->action();
        $controllerClass = is_object($controller) ? $controller::class : $controller;

        // An unresolvable signature throws before assigning, so it is never
        // cached and keeps throwing on later requests, as it did before.
        return self::$actionParamsCache[$controllerClass . '::' . $action]
            ??= self::reflectActionParams($controller, $action);
    }

    /**
     * @param object|class-string $controller
     *
     * @return list<array{name: string, type: string}>
     */
    private static function reflectActionParams(object|string $controller, string $action): array
    {
        $actionParams = [];

        foreach ((new ReflectionClass($controller))->getMethod($action)->getParameters() as $actionParam) {
            /** @var string|null $paramType */
            $paramType = $actionParam->getType()?->__toString();

            if ($paramType === null) {
                throw UnsupportedParamTypeException::nonTyped();
            }

            $actionParams[] = [
                'name' => $actionParam->getName(),
                'type' => $paramType,
            ];
        }

        return $actionParams;
    }
}
