<?php

declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Router;

# To run this example locally, you can run in your terminal:
# $ composer serve

class Controller
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function __invoke(): string
    {
        $number = $this->request->get('number');

        if (!empty($number)) {
            return \sprintf("__invoke with 'number'=%d", $number);
        }

        return '__invoke';
    }

    public function customAction(int $number = 0): string
    {
        return "customAction(number: {$number})";
    }

    public function customHeaders(): Response
    {
        return new Response('{"custom": "headers"}', [
            'Access-Control-Allow-Origin: *',
            'Content-Type: application/json',
        ]);
    }
}

// Example middleware that adds a custom header to all responses
class TimingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string
    {
        $start = microtime(true);
        $response = $next($request);
        $time = round((microtime(true) - $start) * 1000, 2);
        header("X-Response-Time: {$time}ms");
        return $response;
    }
}

$router = new Router(static function (Routes $routes, Middlewares $middlewares): void {
    // Add a global middleware that applies to all routes
    $middlewares->add(new TimingMiddleware());

    # Try it out: http://localhost:8081/docs
    $routes->redirect('docs', 'https://gacela-project.com/');

    # Try it out: http://localhost:8081?number=456
    $routes->match(['GET', 'POST'], '/', Controller::class);

    # Try it out: http://localhost:8081/custom/123
    $routes->get('custom/{number}', Controller::class, 'customAction');

    # Try it out: http://localhost:8081/custom
    $routes->any('custom', Controller::class);

    # Try it out: http://localhost:8081/headers
    $routes->any('headers', Controller::class, 'customHeaders');
});

$router->run();
