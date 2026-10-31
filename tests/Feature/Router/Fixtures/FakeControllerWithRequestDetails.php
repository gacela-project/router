<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Gacela\Router\Entities\Request;

final class FakeControllerWithRequestDetails
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function __invoke(): string
    {
        return $this->request->path() . '|' . (string)$this->request->get('q') . '|' . spl_object_id($this->request);
    }
}
