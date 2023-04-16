<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Fixtures;

trait HeadersTearDown
{
    public function tearDown(): void
    {
        global $testHeaders;

        $testHeaders = null;
    }
}
