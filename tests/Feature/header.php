<?php

declare(strict_types=1);

/**
 * Namespace-level header() function overrides for testing.
 *
 * These functions intercept header() calls in production code and redirect them
 * to HeaderTestHelper for capture and verification in tests. This approach uses
 * PHP's namespace function resolution: when code calls header() without a leading
 * backslash, PHP first looks in the current namespace before falling back to global.
 */

namespace Gacela\Router {
    use GacelaTest\Feature\HeaderTestHelper;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        HeaderTestHelper::capture($header, $replace, $responseCode);
    }
}

namespace Gacela\Router\Handlers {
    use GacelaTest\Feature\HeaderTestHelper;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        HeaderTestHelper::capture($header, $replace, $responseCode);
    }
}

namespace Gacela\Router\Controllers {
    use GacelaTest\Feature\HeaderTestHelper;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        HeaderTestHelper::capture($header, $replace, $responseCode);
    }
}

namespace Gacela\Router\Entities {
    use GacelaTest\Feature\HeaderTestHelper;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        HeaderTestHelper::capture($header, $replace, $responseCode);
    }
}
