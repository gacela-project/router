<?php

declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use Gacela\Router\Request;
use Gacela\Router\Route;

# php -S localhost:8081 example/example.php

$controller = new class() {
    public function __invoke(): string
    {
        $request = Request::fromGlobals();
        $number = $request->get('number');

        if (!empty($number)) {
            return "__invoke with GET 'number'={$number}";
        }

        return '__invoke';
    }

    public function customAction(int $number = 0): string
    {
        return "customAction(number: {$number})";
    }
};

# localhost:8081/custom/123
Route::get('custom/{number}', $controller, 'customAction');

# localhost:8081/custom
Route::get('custom', $controller);

# localhost:8081?number=456
Route::get('/', $controller);
