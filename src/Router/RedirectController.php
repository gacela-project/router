<?php

declare(strict_types=1);

namespace Gacela\Router;

class RedirectController
{
    public function __invoke(): void
    {
        header('Location: ' . 'https://gacela-project.com/', true, 302);
    }
}
