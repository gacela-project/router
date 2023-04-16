<?php

declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use Gacela\Router\Entities\Request;
use Gacela\Router\Router;
use Gacela\Router\Routes;

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
            return "__invoke with GET 'number'={$number}";
        }

        return '__invoke';
    }

    public function customAction(int $number = 0): string
    {
        return "customAction(number: {$number})";
    }
}

Router::configure(static function (Routes $routes): void {
    # Try it out: http://localhost:8081/docs
    $routes->redirect('docs', 'https://gacela-project.com/');

    # Try it out: http://localhost:8081?number=456
    $routes->get('/', Controller::class);

    # Try it out: http://localhost:8081/custom/123
    $routes->get('custom/{number}', Controller::class, 'customAction');

    # Try it out: http://localhost:8081/custom
    $routes->any('custom', Controller::class);
});
