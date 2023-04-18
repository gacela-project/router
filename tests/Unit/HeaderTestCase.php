<?php

declare(strict_types=1);

namespace GacelaTest\Unit;

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/Fake/header.php';

abstract class HeaderTestCase extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    protected function setUp(): void
    {
        global $testHeaders;

        $testHeaders = null;
    }

    protected function headers(): array
    {
        global $testHeaders;

        return $testHeaders;
    }
}
