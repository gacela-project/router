<?php

declare(strict_types=1);

// Counts the regex evaluations the matcher performs.
//
// Route::pathMatches() calls preg_match() unqualified from within
// Gacela\Router\Entities, so a function of that name in the same namespace wins
// over the global one. Same idea as tests/Feature/header.php.
//
// The real call has to be made from a namespace that does *not* declare a
// preg_match of its own, otherwise it would recurse into the counter. A leading
// backslash would say that more directly, but php-cs-fixer's
// native_function_invocation rule strips it from non-compiler-optimized
// functions, so the indirection is doing that job instead.

namespace GacelaTest {
    /**
     * @param array<array-key, mixed>|null $matches
     */
    function nativePregMatch(
        string $pattern,
        string $subject,
        ?array &$matches = null,
        int $flags = 0,
        int $offset = 0,
    ): int|false {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    function countedPregMatchCalls(): int
    {
        /** @var int|null $pregMatchCalls */
        global $pregMatchCalls;

        return $pregMatchCalls ?? 0;
    }

    function resetPregMatchCalls(): void
    {
        global $pregMatchCalls;

        $pregMatchCalls = 0;
    }
}

namespace Gacela\Router\Entities {
    use function GacelaTest\nativePregMatch;

    /**
     * @param array<array-key, mixed>|null $matches
     */
    function preg_match(
        string $pattern,
        string $subject,
        ?array &$matches = null,
        int $flags = 0,
        int $offset = 0,
    ): int|false {
        /** @var int|null $pregMatchCalls */
        global $pregMatchCalls;

        $pregMatchCalls = ($pregMatchCalls ?? 0) + 1;

        return nativePregMatch($pattern, $subject, $matches, $flags, $offset);
    }
}
