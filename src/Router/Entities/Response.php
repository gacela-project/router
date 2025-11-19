<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Override;
use Stringable;

class Response implements Stringable
{
    /**
     * @param list<string> $headers
     */
    public function __construct(
        private string $content,
        private array $headers = [],
    ) {
    }

    #[Override]
    public function __toString(): string
    {
        foreach ($this->headers as $header) {
            header($header);
        }

        return $this->content;
    }
}
