<?php

declare(strict_types=1);

namespace Gacela\Router\Config;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Router\Router;
use Gacela\Router\RouterInterface;

final class RouterGacelaConfig
{
    public function __invoke(GacelaConfig $config): void
    {
        $router = new Router();

        $config->addBinding(RouterInterface::class, $router);
        $config->addBinding(Router::class, $router);
    }
}