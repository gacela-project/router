<?php

declare(strict_types=1);

namespace Gacela\Router\Validators;

use Gacela\Router\Entities\RouteParams;

final class PathValidator
{
    public function __invoke(string $path): bool
    {
        return $this->isPathValid($path);
    }

    protected function isPathValid(string $path): bool
    {
        return $this->isPathEmpty($path) || $this->isPathValidFormat($path);
    }

    private function isPathEmpty(string $path): bool
    {
        return $path === '';
    }

    private function isPathValidFormat(string $path): bool
    {
        return $this->isPathStartWithoutSlash($path)
            && $this->isPathEndWithoutSlash($path)
            && $this->areOptionalArgumentsAfterMandatoryArguments($path);
    }

    private function isPathStartWithoutSlash(string $path): bool
    {
        return $path[0] !== '/';
    }

    private function isPathEndWithoutSlash(string $path): bool
    {
        return $path[-1] !== '/';
    }

    private function areOptionalArgumentsAfterMandatoryArguments(string $path): bool
    {
        $parts = explode('/', $path);
        $optionalParamFound = false;

        foreach ($parts as $part) {
            if (preg_match(RouteParams::OPTIONAL_PARAM_PATTERN, $part)) {
                $optionalParamFound = true;
            } else {
                if ($optionalParamFound) {
                    // Mandatory argument or static part found after an optional argument
                    return false;
                }
            }
        }

        return true;
    }
}
