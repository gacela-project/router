<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Exception;

final class FakeControllerWithUnhandledException
{
    /**
     * @throws Exception
     */
    public function __invoke(): string
    {
        throw new Exception('Unhandled Exception');
    }
}
