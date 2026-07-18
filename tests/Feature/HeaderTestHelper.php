<?php

declare(strict_types=1);

namespace GacelaTest\Feature;

/**
 * @psalm-type CapturedHeader = array{header: string, replace: bool, response_code: int}
 *
 * @phpstan-type CapturedHeader array{header: string, replace: bool, response_code: int}
 */
final class HeaderTestHelper
{
    /** @var list<CapturedHeader> */
    private static array $headers = [];

    public static function capture(string $header, bool $replace = true, int $responseCode = 0): void
    {
        self::$headers[] = [
            'header' => $header,
            'replace' => $replace,
            'response_code' => $responseCode,
        ];
    }

    /**
     * @return list<CapturedHeader>
     */
    public static function getHeaders(): array
    {
        return self::$headers;
    }

    public static function clear(): void
    {
        self::$headers = [];
    }
}
