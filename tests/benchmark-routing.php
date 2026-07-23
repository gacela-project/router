<?php

/**
 * Routing matcher benchmark.
 *
 * Counts regex evaluations and wall time for a worst-case lookup: many routes
 * registered, the matching one registered last.
 *
 *   php tests/benchmark-routing.php [routeCount] [iterations]
 */

declare(strict_types=1);

namespace GacelaBenchmark {
    use Gacela\Router\Configure\Routes;
    use Gacela\Router\Entities\Request;

    use function dirname;
    use function GacelaTest\countedPregMatchCalls;
    use function GacelaTest\resetPregMatchCalls;

    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once __DIR__ . '/preg_match.php';

    final class BenchController
    {
        public function __invoke(): string
        {
            return 'ok';
        }
    }

    function request(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return Request::fromGlobals();
    }

    /**
     * The matcher as it was before the static fast path: scan every registered
     * route, in order, running a regex per candidate.
     */
    function linearScan(Routes $routes, Request $request): void
    {
        foreach ($routes->getAllRoutes() as $route) {
            if ($route->requestMatches($request)) {
                return;
            }
        }
    }

    /**
     * @return array{regex: float, ms: float}
     */
    function measure(callable $lookup, int $iterations): array
    {
        resetPregMatchCalls();

        $start = microtime(true);
        for ($i = 0; $i < $iterations; ++$i) {
            $lookup();
        }
        $ms = (microtime(true) - $start) * 1000;

        return [
            'regex' => countedPregMatchCalls() / $iterations,
            'ms' => $ms,
        ];
    }

    $routeCount = (int) ($argv[1] ?? 100);
    $iterations = (int) ($argv[2] ?? 10_000);

    // Worst case: the wanted route is registered last, behind $routeCount others.
    $staticRoutes = new Routes();
    for ($i = 0; $i < $routeCount; ++$i) {
        $staticRoutes->get("section-{$i}/page", BenchController::class);
    }
    $staticRoutes->get('wanted/page', BenchController::class);

    $dynamicRoutes = new Routes();
    for ($i = 0; $i < $routeCount; ++$i) {
        $dynamicRoutes->get("section-{$i}/{page}", BenchController::class);
    }
    $dynamicRoutes->get('wanted/{page}', BenchController::class);

    // Same static target, but every other route is registered under POST.
    $bucketedRoutes = new Routes();
    for ($i = 0; $i < $routeCount; ++$i) {
        $bucketedRoutes->post("section-{$i}/{page}", BenchController::class);
    }
    $bucketedRoutes->get('wanted/{page}', BenchController::class);

    $scenarios = [
        "static, match last of {$routeCount}" => [$staticRoutes, request('GET', '/wanted/page')],
        "dynamic, match last of {$routeCount}" => [$dynamicRoutes, request('GET', '/wanted/page')],
        "dynamic GET behind {$routeCount} POST" => [$bucketedRoutes, request('GET', '/wanted/page')],
        'no match (404)' => [$staticRoutes, request('GET', '/nothing/here')],
    ];

    printf("%d iterations, %d competing routes, PHP %s\n\n", $iterations, $routeCount, PHP_VERSION);
    printf(
        "%-34s %10s %9s %10s %9s\n",
        'scenario',
        'regex/before',
        'ms/before',
        'regex/after',
        'ms/after',
    );
    printf("%s\n", str_repeat('-', 76));

    foreach ($scenarios as $name => [$routes, $request]) {
        $before = measure(static fn () => linearScan($routes, $request), $iterations);
        $after = measure(static fn () => $routes->findMatching($request), $iterations);

        printf(
            "%-34s %10s %9.2f %10s %9.2f\n",
            $name,
            $before['regex'],
            $before['ms'],
            $after['regex'],
            $after['ms'],
        );
    }
}
