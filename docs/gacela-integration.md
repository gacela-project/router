# Use it within Gacela

The package ships a `RouterGacelaConfig` adapter that registers the router in the
[Gacela](https://gacela-project.com/) container. It binds both `Router` and
`RouterInterface` to a single shared `Router` instance, using `addBindingIf` — so if
your application already bound one of them, your binding wins.

## Bootstrapping

Extend the adapter from your Gacela bootstrap and resolve the router:

```php
use Gacela\Framework\Gacela;
use Gacela\Framework\Bootstrap\GacelaConfig;
use Gacela\Router\Config\RouterGacelaConfig;
use Gacela\Router\Router;

Gacela::bootstrap($appRootDir, static function (GacelaConfig $config): void {
    $config->extendGacelaConfig(RouterGacelaConfig::class);
});

Gacela::get(Router::class)->run();
```

## Adding routes from modules/plugins

Because the router is a single shared instance, different modules can contribute
routes independently via `RouterInterface::configure()`. A Gacela plugin that
receives the router and registers a route:

```php
use Gacela\Router\Configure\Routes;
use Gacela\Router\RouterInterface;

final class BlogRoutesPlugin
{
    public function __construct(private RouterInterface $router) {}

    public function __invoke(): void
    {
        $this->router->configure(static function (Routes $routes): void {
            $routes->get('blog', BlogController::class);
        });
    }
}
```

Register such plugins through Gacela's plugin mechanism (`GacelaConfig::addPlugins([...])`),
and each one adds its routes to the same router before `run()` is called.

## Overriding the binding

Since the adapter uses `addBindingIf`, binding your own `RouterInterface`/`Router`
before the adapter runs keeps your instance in place — handy for tests or a
customised router subclass.
