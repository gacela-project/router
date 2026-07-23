<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Gacela\Router\UrlGenerator;

final class FakeUrlGeneratingController
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(): string
    {
        return $this->urlGenerator->generate('users.show', ['id' => 7]);
    }
}
