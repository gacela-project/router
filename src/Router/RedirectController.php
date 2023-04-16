<?php

declare(strict_types=1);

namespace Gacela\Router;

final class RedirectController
{
    public function __construct(
        private string $destination,
    ) {
    }

    public function __invoke(): void
    {
        header('Location: ' . $this->destination, true, 302);
    }
}
