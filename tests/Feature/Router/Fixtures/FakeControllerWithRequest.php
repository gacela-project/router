<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Gacela\Router\Entities\Request;

final class FakeControllerWithRequest
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function __invoke(): string
    {
        return (string)$this->request->get('name');
    }
}
