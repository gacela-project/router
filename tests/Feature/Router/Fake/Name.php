<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fake;

final class Name implements NameInterface
{
    public function __construct(private string $name)
    {
    }

    public function toString(): string
    {
        return $this->name;
    }
}
