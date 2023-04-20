<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Gacela\Router\Exceptions\UnhandledException;

final class FakeControllerWithUnhandledException
{
    /**
     * @throws UnhandledException
     */
    public function __invoke(): string
    {
        throw new UnhandledException('failed');
    }
}
