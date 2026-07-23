<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Router\Fixtures;

final class FakeEnumController
{
    public function stringEnumAction(FakeStringEnum $param): string
    {
        return "The enum is '{$param->value}'!";
    }

    public function intEnumAction(FakeIntEnum $param): string
    {
        return "The enum is '{$param->value}'!";
    }

    public function pureEnumAction(FakePureEnum $param): string
    {
        return "The enum is '{$param->name}'!";
    }
}
