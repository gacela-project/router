<?php

declare(strict_types=1);

namespace Gacela\Router\Controllers;

final class RedirectController
{
    public function __construct(
        private string $destination,
        private int $status,
    ) {
    }

    public function __invoke(): void
    {
        header('Location: ' . $this->destination, true, $this->status);
    }
}
