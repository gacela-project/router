<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use TypeError;

final class FakeControllerWithError
{
    /**
     * @throws TypeError
     */
    public function __invoke(): string
    {
        throw new TypeError('failed');
    }
}
