<?php

declare(strict_types=1);

namespace Gacela\Router\Configure;

final class Bindings
{
    /** @var array<class-string, callable|class-string|object> */
    private array $bindings = [];

    /**
     * @param class-string $name
     * @param callable|class-string|object $instance
     */
    public function bind(string $name, mixed $instance): self
    {
        $this->bindings[$name] = $instance;
        return $this;
    }

    /**
     * @return array<class-string, callable|class-string|object>
     */
    public function getAllBindings(): array
    {
        return $this->bindings;
    }
}
