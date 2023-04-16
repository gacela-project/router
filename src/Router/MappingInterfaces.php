<?php

declare(strict_types=1);

namespace Gacela\Router;

final class MappingInterfaces
{
    /** @var array<class-string, callable|class-string|object> */
    private array $mappingInterfaces = [];

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
}
