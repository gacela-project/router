<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class MethodNotAllowed405Exception extends RuntimeException
{
    /**
     * @param list<string> $allowedMethods
     */
    private function __construct(
        private readonly array $allowedMethods,
    ) {
        parent::__construct('Error 405 - Method Not Allowed');
    }

    /**
     * @param list<string> $allowedMethods the methods the requested path does accept
     */
    public static function fromAllowedMethods(array $allowedMethods): self
    {
        return new self($allowedMethods);
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
