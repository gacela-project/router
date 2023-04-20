<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

use Exception;

final class UnhandledException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct("Unhandled '{$name}'!");
    }
}
