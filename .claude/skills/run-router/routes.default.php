<?php

/**
 * Default route definition used by `driver.php call` when --routes is not given.
 *
 * It mirrors example/example.php, but returns the configure Closure instead of
 * running the Router, so the driver can invoke it in-process.
 *
 * Write your own copy of this file to exercise a change you are working on:
 *   php .claude/skills/run-router/driver.php call GET /foo --routes=/tmp/my-routes.php
 */

declare(strict_types=1);

namespace GacelaDriver\DefaultRoutes;

use Closure;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Response;
use Gacela\Router\Middleware\MiddlewareInterface;

final class Controller
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

final class TimingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string
    {
        return $next($request);
    }
}

return static function (Routes $routes, Middlewares $middlewares): void {
    $middlewares->add(new TimingMiddleware());

    $routes->redirect('docs', 'https://gacela-project.com/');
    $routes->match(['GET', 'POST'], '/', Controller::class);
    $routes->get('custom/{number}', Controller::class, 'customAction');
    $routes->any('custom', Controller::class);
    $routes->any('headers', Controller::class, 'customHeaders');
};
