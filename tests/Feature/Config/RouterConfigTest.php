<?php

declare(strict_types=1);

namespace GacelaTest\Feature\Config;

use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Framework\Gacela;
use Gacela\Router\Config\RouterGacelaConfig;
use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use GacelaTest\Feature\Config\Module\Plugin\NameRoutesPlugin;
use GacelaTest\Feature\Config\Module\Plugin\RootRoutesPlugin;
use GacelaTest\Feature\HeaderTestCase;

final class RouterConfigTest extends HeaderTestCase
{
    protected function setUp(): void
    {
        Gacela::bootstrap(__DIR__, static function (GacelaConfig $config): void {
            $config->resetInMemoryCache();
            $config->extendGacelaConfig(RouterGacelaConfig::class);

            $config->addPlugins([
                NameRoutesPlugin::class,
                RootRoutesPlugin::class,
            ]);
        });
    }

    public function test_root_route_plugin(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        /** @var \Gacela\Router\RouterInterface $gacelaRouter */
        $gacelaRouter = Gacela::get(Router::class);
        $gacelaRouter->run();

        $this->expectOutputString((string)json_encode(['hello' => 'bob?']));
    }

    public function test_name_route_plugin(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org/alice';
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;

        /** @var \Gacela\Router\RouterInterface $gacelaRouter */
        $gacelaRouter = Gacela::get(Router::class);
        $gacelaRouter->run();

        $this->expectOutputString((string)json_encode(['hello' => 'alice']));
    }
}
