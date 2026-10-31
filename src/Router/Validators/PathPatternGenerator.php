<?php

declare(strict_types=1);

namespace Gacela\Router\Validators;

use Gacela\Router\Entities\RouteParams;

final class PathPatternGenerator
{
    public static function generate(string $path): string
    {
        if ($path === '') {
            return '#^/?$#';
        }

        $parts = explode('/', $path);
        $pattern = '';

        foreach ($parts as $part) {
            if (preg_match(RouteParams::MANDATORY_PARAM_PATTERN, $part)) {
                $pattern .= '/([^\/]+)';
            } elseif (preg_match(RouteParams::OPTIONAL_PARAM_PATTERN, $part)) {
                // The slash lives inside the optional group, so '/custom' and
                // '/custom/5' match but '/customers' and '/custom5' do not.
                $pattern .= '(?:/([^\/]+))?';
            } else {
                $pattern .= '/' . $part;
            }
        }

        // Nothing fixed comes first, so the root has to match on its own too.
        if (str_starts_with($pattern, '(')) {
            return '#^(?:/?|' . $pattern . ')$#';
        }

        return '#^' . $pattern . '$#';
    }
}
