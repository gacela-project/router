<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use GacelaRouter\Router;

final class CustomController
{
    public function __invoke(
        string $name = 'DefaultName',
        int $amount = 0
    ): string
    {
        return "Hello, $name. Amount=$amount";
    }
}

$router = Router::withServer([
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/foo/?amount=123000',
]);

$router->get('/', static function () {
    echo (new CustomController)->__invoke();
});

$router->get('/$name', static function (string $name = '') {
    $amount = (int)($_GET['amount'] ?? 0);
    echo (new CustomController)->__invoke($name, $amount);
});

$router->listen();
