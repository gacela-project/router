<?php

declare(strict_types=1);

namespace GacelaTest\Feature;

use Gacela\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/header.php';

/**
 * @psalm-import-type CapturedHeader from HeaderTestHelper
 *
 * @phpstan-import-type CapturedHeader from HeaderTestHelper
 */
abstract class HeaderTestCase extends TestCase
{
    protected function setUp(): void
    {
        HeaderTestHelper::clear();
    }

    /**
     * @return list<CapturedHeader>
     */
    protected function headers(): array
    {
        return HeaderTestHelper::getHeaders();
    }

    protected function runRouter(RouterInterface $router): string
    {
        ob_start();
        $router->run();

        return (string)ob_get_clean();
    }
}
