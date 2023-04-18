<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use GacelaTest\Feature\Router\Fake\NameInterface;

final class FakeControllerWithDependencies
{
    public function __construct(
        private NameInterface $name,
        private string $expected = 'default',
    ) {
    }

    public function __invoke(): string
    {
        return $this->expected . '-' . $this->name->toString();
    }
}
