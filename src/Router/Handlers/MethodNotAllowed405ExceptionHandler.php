<?php

declare(strict_types=1);

namespace Gacela\Router\Handlers;

use Gacela\Router\Exceptions\MethodNotAllowed405Exception;

final class MethodNotAllowed405ExceptionHandler
{
    public function __invoke(MethodNotAllowed405Exception $methodNotAllowed405Exception): string
    {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: ' . implode(', ', $methodNotAllowed405Exception->allowedMethods()));

        return '';
    }
}
