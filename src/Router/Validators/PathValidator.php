<?php

declare(strict_types=1);

namespace Gacela\Router\Validators;

use Gacela\Router\Entities\RouteParams;

final class PathValidator
{
    public static function isValid(string $path): bool
    {
        if ($path === '/') {
            return true;
        }

        if (!self::hasValidFormat($path)) {
            return false;
        }

        return self::hasOnlyWholeSegmentParams($path) && self::hasValidParameterOrder($path);
    }

    private static function hasValidFormat(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, '/')) {
            return false;
        }

        if (str_ends_with($path, '/')) {
            return false;
        }

        return !self::hasEmptyParts($path);
    }

    private static function hasEmptyParts(string $path): bool
    {
        $parts = explode('/', $path);

        foreach ($parts as $part) {
            // Strictly against '': a '0' segment is falsy but perfectly valid.
            if ($part === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * A brace belongs to a segment that is exactly one parameter. Anything else,
     * such as 'v{version}' or '{id', would be matched as a wildcard that ignores
     * the text around the braces.
     */
    private static function hasOnlyWholeSegmentParams(string $path): bool
    {
        foreach (explode('/', $path) as $part) {
            $isParam = preg_match(RouteParams::MANDATORY_PARAM_PATTERN, $part)
                || preg_match(RouteParams::OPTIONAL_PARAM_PATTERN, $part);

            if (!$isParam && strpbrk($part, '{}') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Only reached once hasValidFormat() has ruled out empty segments, so it
     * does not re-check them: duplicating that made both checks individually
     * removable without any test noticing.
     */
    private static function hasValidParameterOrder(string $path): bool
    {
        $parts = explode('/', $path);
        $optionalParamFound = false;

        foreach ($parts as $part) {
            if (preg_match(RouteParams::OPTIONAL_PARAM_PATTERN, $part)) { // Optional argument found
                $optionalParamFound = true;
            } elseif ($optionalParamFound) { // Mandatory argument or static part found after an optional argument
                return false;
            }
        }

        return true;
    }
}
