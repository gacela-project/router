<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

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
