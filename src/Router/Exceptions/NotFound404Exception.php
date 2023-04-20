<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class NotFound404Exception extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Error 404 - Not Found');
    }
}
