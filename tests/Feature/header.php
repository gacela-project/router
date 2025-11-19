<?php

declare(strict_types=1);

namespace Gacela\Router {
    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        /** @var list<array{header: string, replace: bool, response_code: int}> | null $testHeaders */
        global $testHeaders;

        if (!\is_array($testHeaders)) {
            $testHeaders = [];
        }

        $testHeaders[] = [
            'header' => $header,
            'replace' => $replace,
            'response_code' => $responseCode,
        ];
    }
}

// TODO: Find a better way to mock the head function in different namespaces

namespace Gacela\Router\Handlers {
    use function Gacela\Router\header as rootHeader;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        rootHeader($header, $replace, $responseCode);
    }
}

// TODO: Find a better way to mock the head function in different namespaces

namespace Gacela\Router\Controllers {
    use function Gacela\Router\header as rootHeader;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        rootHeader($header, $replace, $responseCode);
    }
}

// TODO: Find a better way to mock the head function in different namespaces

namespace Gacela\Router\Entities {
    use function Gacela\Router\header as rootHeader;

    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        rootHeader($header, $replace, $responseCode);
    }
}
