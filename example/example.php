<?php

declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use GacelaRouter\Router;

# php -S localhost:8081 example/example.php

$controller = new class() {
    public function __invoke(): string
    {
        $request = \GacelaRouter\Request::fromGlobals();
        $number = $request->get('number');

        if ($number !== 0) {
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
Router::get('custom/{number}', $controller, 'customAction');

# localhost:8081/custom
Router::get('custom', $controller);

# localhost:8081?number=456
Router::get('/', $controller);
