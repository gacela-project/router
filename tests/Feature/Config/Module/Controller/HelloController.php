<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Config\Module\Controller;

use Gacela\Router\Entities\JsonResponse;

final class HelloController
{
    public function __invoke(string $name = ''): JsonResponse
    {
        return new JsonResponse([
            'hello' => $name ?: 'bob?',
        ]);
    }
}
