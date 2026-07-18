<?php

declare(strict_types=1);

namespace Gacela\Router\Validators;

final class PathPatternGenerator
{
    public const MANDATORY_PARAM_PATTERN = '#({.*[^?]})#';
    public const OPTIONAL_PARAM_PATTERN = '#(/?{.*\?})#';

    public static function generate(string $path): string
    {
        if ($path === '') {
            return '#^/?$#';
        }

        $parts = explode('/', $path);
        $pattern = '';

        foreach ($parts as $part) {
            if (preg_match(self::MANDATORY_PARAM_PATTERN, $part)) {
                $pattern .= '/([^\/]+)';
            } elseif (preg_match(self::OPTIONAL_PARAM_PATTERN, $part)) {
                $pattern .= '/?([^\/]+)?';
            } else {
                $pattern .= '/' . $part;
            }
        }

        return '#^/' . ltrim($pattern, '/') . '$#';
    }
}
