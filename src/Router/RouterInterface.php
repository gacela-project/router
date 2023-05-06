<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;

interface RouterInterface
{
    public function configure(Closure $fn): self;

    public function run(): void;
}
