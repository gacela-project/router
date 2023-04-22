<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

class Response
{
    /**
     * @param list<string> $headers
     */
    public function __construct(
        private string $content,
        private array $headers = [],
    ) {
    }

    public function __toString(): string
    {
        foreach ($this->headers as $header) {
            header($header);
        }

        return $this->content;
    }
}
