<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

enum FakeStringEnum: string
{
    case Active = 'active';
    case Archived = 'archived';
}
