<?php

declare(strict_types=1);

namespace Gacela\Router\Controllers {
    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        /** @var list<array{header: string, replace: boolean, response_code: int}> | null $testHeaders */
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

namespace Gacela\Router {
    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        /** @var list<array{header: string, replace: boolean, response_code: int}> | null $testHeaders */
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

namespace GacelaTest\Feature\Router {
    function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        /** @var list<array{header: string, replace: boolean, response_code: int}> | null $testHeaders */
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
