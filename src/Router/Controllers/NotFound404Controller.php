<?php

declare(strict_types=1);

namespace Gacela\Router\Controllers;

use Gacela\Router\Entities\Request;

final class NotFound404Controller
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function __invoke(): void
    {
        header('Location: ' . $this->request->path(), true, 404);
    }
}
