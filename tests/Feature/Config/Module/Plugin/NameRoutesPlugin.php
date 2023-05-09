<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Config\Module\Plugin;

use Gacela\Router\Configure\Routes;
use Gacela\Router\RouterInterface;
use GacelaTest\Feature\Config\Module\Controller\HelloController;

final class NameRoutesPlugin
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function __invoke(): void
    {
        $this->router->configure(static function (Routes $routes): void {
            $routes->get('{name}', HelloController::class);
        });
    }
}
