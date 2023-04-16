<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Router\Entities\Request;

final class MappingInterfaces
{
    /** @var array<class-string, callable|class-string|object> */
    private array $mappingInterfaces = [];

    public function __construct()
    {
        $this->builtInInjections();
    }

    /**
     * @param class-string $name
     * @param callable|class-string|object $instance
     */
    public function add(string $name, mixed $instance): self
    {
        $this->mappingInterfaces[$name] = $instance;
        return $this;
    }

    /**
     * @return array<class-string, callable|class-string|object>
     */
    public function getAll(): array
    {
        return $this->mappingInterfaces;
    }

    private function builtInInjections(): void
    {
        $this->add(Request::class, Request::fromGlobals());
    }
}
