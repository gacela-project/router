<?php

declare(strict_types=1);

namespace Gacela\Router\Handlers;

use Gacela\Router\Exceptions\InvalidParamValueException;

final class InvalidParamValueExceptionHandler
{
    public function __invoke(InvalidParamValueException $invalidParamValueException): string
    {
        header('HTTP/1.0 404 Not Found');

        return '';
    }
}
