<?php

declare(strict_types=1);

namespace Gacela\Router\Handlers;

use Gacela\Router\Exceptions\NotFound404Exception;

final class NotFound404ExceptionHandler
{
    public function __invoke(NotFound404Exception $notFound404Exception): string
    {
        header('HTTP/1.0 404 Not Found');

        return '';
    }
}
