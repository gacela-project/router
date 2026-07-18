<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Router\Exceptions\UnsupportedParamTypeException;
use Gacela\Router\Validators\PathPatternGenerator;
use ReflectionClass;

use function count;

final class RouteParams
{
    /** @deprecated use PathPatternGenerator::MANDATORY_PARAM_PATTERN instead */
    public const MANDATORY_PARAM_PATTERN = PathPatternGenerator::MANDATORY_PARAM_PATTERN;

    /** @deprecated use PathPatternGenerator::OPTIONAL_PARAM_PATTERN instead */
    public const OPTIONAL_PARAM_PATTERN = PathPatternGenerator::OPTIONAL_PARAM_PATTERN;

    /** @var array<string, bool|float|int|string> */
    private array $params;

    public function __construct(private Route $route)
    {
        $this->params = $this->getParams();
    }

    /**
     * @return array<string, bool|float|int|string>
     */
    public function getAll(): array
    {
        return $this->params;
    }

    /**
     * @return array<string, bool|float|int|string>
     */
    private function getParams(): array
    {
        $params = [];
        $pathParamKeys = [];
        $pathParamValues = [];

        preg_match($this->route->getPathPattern(), '/' . $this->route->path(), $pathParamKeys);
        preg_match($this->route->getPathPattern(), Request::fromGlobals()->path(), $pathParamValues);

        unset($pathParamValues[0], $pathParamKeys[0]);
        $pathParamKeys = array_map(static fn (string $key): string => trim($key, '{?}'), $pathParamKeys);

        while (count($pathParamValues) !== count($pathParamKeys)) {
            array_shift($pathParamKeys);
        }

        $pathParams = array_combine($pathParamKeys, $pathParamValues);
        $actionParams = (new ReflectionClass($this->route->controller()))
            ->getMethod($this->route->action())
            ->getParameters();

        foreach ($actionParams as $actionParam) {
            $paramType = $actionParam->getType()?->__toString();

            if ($paramType === null) {
                throw UnsupportedParamTypeException::nonTyped();
            }

            $paramName = $actionParam->getName();
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
}
