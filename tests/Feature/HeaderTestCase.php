<?php

declare(strict_types=1);

namespace GacelaTest\Feature;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/header.php';

abstract class HeaderTestCase extends TestCase
{
    #[RunInSeparateProcess]
    protected function setUp(): void
    {
        global $testHeaders;

        $testHeaders = null;
    }

    protected function headers(): array
    {
        global $testHeaders;

        return $testHeaders ?? [];
    }
}
