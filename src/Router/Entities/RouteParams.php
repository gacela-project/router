<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use BackedEnum;
use Gacela\Router\Exceptions\InvalidEnumValueException;
use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use ReflectionClass;
use ReflectionEnum;

use function count;
use function is_object;
use function is_subclass_of;

final class RouteParams
{
    public const MANDATORY_PARAM_PATTERN = '#({.*[^?]})#';
    public const OPTIONAL_PARAM_PATTERN = '#(/?{.*\?})#';

    /**
     * Signatures never change within a process, so each controller action is
     * reflected once. One entry per unique `Controller::action`.
     *
     * @var array<string, list<array{name: string, type: string, enumBacking: string|null}>>
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

        foreach (self::actionParams($this->route) as $actionParam) {
            ['name' => $paramName, 'type' => $paramType, 'enumBacking' => $enumBacking] = $actionParam;

            if (isset($pathParams[$paramName])) {
                /** @var string $rawValue */
                $rawValue = $pathParams[$paramName];

                $params[$paramName] = $enumBacking === null
                    ? match ($paramType) {
                        'string' => $rawValue,
                        'int' => (int)$rawValue,
                        'float' => (float)$rawValue,
                        'bool' => (bool)json_decode($rawValue),
                        default => throw UnsupportedParamTypeException::fromType($paramType),
                    }
                : self::toBackedEnum($paramType, $enumBacking, $rawValue);
            }
        }

        return $params;
    }

    /**
     * @return list<array{name: string, type: string, enumBacking: string|null}>
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
     * @return list<array{name: string, type: string, enumBacking: string|null}>
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
                'enumBacking' => self::enumBackingType($paramType),
            ];
        }

        return $actionParams;
    }

    /**
     * 'int' or 'string' for a backed enum, null for anything else. Resolved here
     * so it lands in the cache with the rest of the signature, keeping reflection
     * out of the per-request path.
     */
    private static function enumBackingType(string $paramType): ?string
    {
        if (!is_subclass_of($paramType, BackedEnum::class)) {
            return null;
        }

        /** @var class-string<BackedEnum> $paramType */
        return (string)(new ReflectionEnum($paramType))->getBackingType();
    }

    /**
     * A path value is always a string, so an int-backed enum needs it converted
     * first: under strict_types, tryFrom() would reject the string outright.
     */
    private static function toBackedEnum(string $enumClass, string $enumBacking, string $rawValue): BackedEnum
    {
        /** @var class-string<BackedEnum> $enumClass */
        $backedValue = $enumBacking === 'int'
            // Not a plain (int) cast: that turns 'abc' into 0 and would silently
            // bind a case backed by 0.
            ? filter_var($rawValue, FILTER_VALIDATE_INT)
            : $rawValue;

        $case = $backedValue === false
            ? null
            : $enumClass::tryFrom($backedValue);

        return $case ?? throw InvalidEnumValueException::forEnum($enumClass, $rawValue);
    }
}
