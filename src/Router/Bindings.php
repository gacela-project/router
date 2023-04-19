<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Router\Entities\Request;

final class Bindings
{
    /** @var array<class-string, callable|class-string|object> */
    private array $bindings = [];

    public function __construct()
    {
        $this->addBuiltInBindings();
    }

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

    private function addBuiltInBindings(): void
    {
        $this->bind(Request::class, Request::fromGlobals());
    }
}
