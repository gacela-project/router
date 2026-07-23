<?php

/**
 * run-router driver.
 *
 * Two ways to poke this library:
 *
 *   call    in-process. Builds a Router from a route-definition file, fakes the
 *           superglobals, buffers the echoed body and records header() calls via
 *           the namespaced stubs in tests/Feature/header.php. Fast (~50ms), and
 *           the right layer for changes inside src/Router/**.
 *
 *   request real HTTP. Boots `php -S` on example/example.php, issues one request,
 *           prints the real status line + headers + body, tears the server down.
 *
 *   smoke   real HTTP, whole assertion table over example/example.php.
 *           Exit code 0 = all green, 1 = at least one check failed.
 *
 *   serve   foreground `php -S` (the human path), same as `composer serve`.
 *
 * Usage:
 *   php .claude/skills/run-router/driver.php call    <METHOD> <PATH> [--routes=FILE] [--data=k=v&k2=v2]
 *   php .claude/skills/run-router/driver.php request <METHOD> <PATH> [--routes=FILE] [--data=k=v&k2=v2] [--port=N]
 *   php .claude/skills/run-router/driver.php smoke   [--routes=FILE] [--port=N]
 *   php .claude/skills/run-router/driver.php serve   [--routes=FILE] [--port=8081]
 */

declare(strict_types=1);

// thecodingmachine/safe (a transitive dev dep of psalm) is eagerly loaded by
// composer's `files` autoloader and emits ~440KB of deprecation notices on
// PHP >= 8.4 -- with Xdebug on, each one carries a stack trace. Silence them
// before vendor/autoload.php is ever touched.
error_reporting(E_ALL & ~E_DEPRECATED);

const ROOT = __DIR__ . '/../../..';

/** @var resource|null */
$serverProcess = null;

// ---------------------------------------------------------------- entrypoint

$argvCopy = $argv;
array_shift($argvCopy);
$command = array_shift($argvCopy) ?? 'help';

$positional = [];
$options = [];
foreach ($argvCopy as $arg) {
    if (str_starts_with($arg, '--')) {
        $pair = explode('=', substr($arg, 2), 2);
        $options[$pair[0]] = $pair[1] ?? true;
    } else {
        $positional[] = $arg;
    }
}

exit(match ($command) {
    'call' => commandCall($positional, $options),
    'request' => commandRequest($positional, $options),
    'smoke' => commandSmoke($options),
    'serve' => commandServe($options),
    default => commandHelp(),
});

// ------------------------------------------------------------------ commands

function commandHelp(): int
{
    fwrite(STDERR, <<<TXT
    driver.php <command>

      call    <METHOD> <PATH> [--routes=FILE] [--data=k=v&k2=v2]
              In-process. Body + header() calls, no server. FILE must `return`
              a Closure (same shape you pass to `new Router(...)`).
              Default FILE: .claude/skills/run-router/routes.default.php

      request <METHOD> <PATH> [--routes=FILE] [--data=k=v&k2=v2] [--port=N]
              Real HTTP. Real status line + headers + body. Without --routes it
              serves example/example.php; with it, a throwaway front controller
              wrapping your Closure.

      smoke   [--routes=FILE] [--port=N]
              Real HTTP assertion table over example/example.php. Exit 1 on fail.

      serve   [--routes=FILE] [--port=8081]
              Foreground server. Ctrl-C to stop.

    TXT);

    return 2;
}

/**
 * @param list<string>          $positional
 * @param array<string, string> $options
 */
function commandCall(array $positional, array $options): int
{
    $method = strtoupper($positional[0] ?? 'GET');
    $path = $positional[1] ?? '/';
    $routesFile = $options['routes'] ?? __DIR__ . '/routes.default.php';

    if (!is_file($routesFile)) {
        fwrite(STDERR, "routes file not found: {$routesFile}\n");
        return 2;
    }

    require_once ROOT . '/vendor/autoload.php';
    // Namespaced header() stubs. Must be loaded before Router::run() so the
    // unqualified header() calls inside Gacela\Router\** resolve to them.
    require_once ROOT . '/tests/Feature/header.php';

    $query = [];
    $queryString = parse_url($path, PHP_URL_QUERY);
    if (is_string($queryString)) {
        parse_str($queryString, $query);
    }

    $body = [];
    if (isset($options['data']) && is_string($options['data'])) {
        parse_str($options['data'], $body);
        if ($method === 'GET') {
            $query = array_merge($query, $body);
            $body = [];
        }
    }

    // Set these before requiring the routes file: a file that runs the Router
    // itself (example/example.php does) would otherwise blow up on the missing
    // REQUEST_URI before we get a chance to reject it.
    $_GET = $query;
    $_POST = $body;
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $path;

    $GLOBALS['testHeaders'] = null;

    $fn = require $routesFile;

    if (!$fn instanceof Closure) {
        fwrite(STDERR, sprintf(
            "routes file must `return` a Closure, got: %s\n"
            . "(a front controller that calls \$router->run() itself cannot be reused here -- "
            . "see .claude/skills/run-router/routes.default.php for the shape)\n",
            get_debug_type($fn),
        ));
        return 2;
    }

    ob_start();
    try {
        (new Gacela\Router\Router($fn))->run();
    } catch (Throwable $throwable) {
        ob_end_clean();
        fwrite(STDERR, "uncaught " . $throwable::class . ": " . $throwable->getMessage() . "\n");
        fwrite(STDERR, $throwable->getTraceAsString() . "\n");
        return 1;
    }
    $output = (string) ob_get_clean();

    /** @var list<array{header: string, replace: bool, response_code: int}> $headers */
    $headers = $GLOBALS['testHeaders'] ?? [];

    echo "call {$method} {$path}\n";
    echo "routes: {$routesFile}\n";
    echo "headers (" . count($headers) . "):\n";
    foreach ($headers as $header) {
        $suffix = $header['response_code'] !== 0 ? "   [code={$header['response_code']}]" : '';
        echo "  {$header['header']}{$suffix}\n";
    }
    echo "body (" . strlen($output) . " bytes):\n";
    echo $output === '' ? "  <empty>\n" : $output . "\n";

    return 0;
}

/**
 * @param list<string>          $positional
 * @param array<string, string> $options
 */
function commandRequest(array $positional, array $options): int
{
    $method = strtoupper($positional[0] ?? 'GET');
    $path = $positional[1] ?? '/';
    $port = isset($options['port']) ? (int) $options['port'] : freePort();

    startServer($port, is_string($options['routes'] ?? null) ? $options['routes'] : null);

    $response = http($method, "http://127.0.0.1:{$port}{$path}", $options['data'] ?? null);

    echo "request {$method} {$path}  ->  {$response['status']}\n";
    foreach ($response['headers'] as $header) {
        echo "  {$header}\n";
    }
    echo "body (" . strlen($response['body']) . " bytes):\n";
    echo $response['body'] === '' ? "  <empty>\n" : $response['body'] . "\n";

    stopServer();

    return 0;
}

/**
 * @param array<string, string> $options
 */
function commandSmoke(array $options): int
{
    $port = isset($options['port']) ? (int) $options['port'] : freePort();

    startServer($port, is_string($options['routes'] ?? null) ? $options['routes'] : null);

    $base = "http://127.0.0.1:{$port}";

    // method, path, post-data, expected status, expected body, expected header substrings
    $checks = [
        ['GET', '/', null, 200, '__invoke', ['X-Response-Time:']],
        ['GET', '/?number=456', null, 200, "__invoke with 'number'=456", []],
        ['POST', '/', 'number=99', 200, "__invoke with 'number'=99", []],
        ['GET', '/custom/123', null, 200, 'customAction(number: 123)', []],
        ['GET', '/custom', null, 200, '__invoke', []],
        ['GET', '/headers', null, 200, '{"custom": "headers"}', [
            'Content-Type: application/json',
            'Access-Control-Allow-Origin: *',
        ]],
        ['GET', '/docs', null, 302, '', ['Location: https://gacela-project.com/']],
        ['GET', '/nope', null, 404, '', []],
    ];

    $failed = 0;
    foreach ($checks as [$method, $path, $data, $wantStatus, $wantBody, $wantHeaders]) {
        $response = http($method, $base . $path, $data);
        $problems = [];

        if ($response['status'] !== $wantStatus) {
            $problems[] = "status {$response['status']} != {$wantStatus}";
        }
        if ($response['body'] !== $wantBody) {
            $problems[] = "body " . var_export($response['body'], true) . ' != ' . var_export($wantBody, true);
        }
        foreach ($wantHeaders as $wantHeader) {
            $found = false;
            foreach ($response['headers'] as $header) {
                if (stripos($header, $wantHeader) === 0) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $problems[] = "missing header '{$wantHeader}'";
            }
        }

        $label = str_pad("{$method} {$path}", 22);
        if ($problems === []) {
            echo "  PASS  {$label}  {$response['status']}\n";
        } else {
            ++$failed;
            echo "  FAIL  {$label}  " . implode('; ', $problems) . "\n";
        }
    }

    stopServer();

    $total = count($checks);
    echo $failed === 0
        ? "\nsmoke: {$total}/{$total} passed\n"
        : "\nsmoke: {$failed}/{$total} FAILED\n";

    return $failed === 0 ? 0 : 1;
}

/**
 * @param array<string, string> $options
 */
function commandServe(array $options): int
{
    $port = isset($options['port']) ? (int) $options['port'] : 8081;
    $routesFile = is_string($options['routes'] ?? null) ? $options['routes'] : null;
    $command = serveCommand($port, 'localhost', $routesFile === null ? null : frontControllerFor($routesFile));

    echo "serving " . ($routesFile ?? 'example/example.php') . " on http://localhost:{$port}  (Ctrl-C to stop)\n";
    passthru($command, $exitCode);

    return $exitCode;
}

// ------------------------------------------------------------------- helpers

function serveCommand(int $port, string $host = 'localhost', ?string $frontController = null): string
{
    // -d error_reporting: the dev vendor tree (thecodingmachine/safe, pulled in by
    // psalm) emits a wall of deprecations on PHP >= 8.4 that would otherwise be
    // interleaved into every response.
    return sprintf(
        '%s -d error_reporting=%s -S %s:%d %s',
        escapeshellarg(PHP_BINARY),
        escapeshellarg('E_ALL & ~E_DEPRECATED'),
        $host,
        $port,
        escapeshellarg($frontController ?? absolutePath(ROOT . '/example/example.php')),
    );
}

function absolutePath(string $path): string
{
    $resolved = realpath($path);

    return $resolved === false ? $path : $resolved;
}

/**
 * php -S needs a file to serve. When the caller supplies their own route
 * definition (a file that `return`s a Closure), wrap it in a throwaway front
 * controller so the same routes can be driven over real HTTP.
 */
function frontControllerFor(string $routesFile): string
{
    if (!is_file($routesFile)) {
        fwrite(STDERR, "routes file not found: {$routesFile}\n");
        exit(2);
    }

    $file = (string) tempnam(sys_get_temp_dir(), 'gacela-front-') . '.php';

    file_put_contents($file, sprintf(
        "<?php\nerror_reporting(E_ALL & ~E_DEPRECATED);\nrequire %s;\n\$fn = require %s;\n(new Gacela\\Router\\Router(\$fn))->run();\n",
        var_export(absolutePath(ROOT . '/vendor/autoload.php'), true),
        var_export(absolutePath($routesFile), true),
    ));

    register_shutdown_function(static function () use ($file): void {
        @unlink($file);
    });

    return $file;
}

function freePort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        fwrite(STDERR, "cannot allocate a port: {$errstr}\n");
        exit(2);
    }
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);

    return (int) substr($name, (int) strrpos($name, ':') + 1);
}

function startServer(int $port, ?string $routesFile = null): void
{
    global $serverProcess;

    // Without this, an explicit --port that is already taken silently sends
    // every request to whatever else is listening there.
    $occupant = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($occupant !== false) {
        fclose($occupant);
        fwrite(STDERR, "port {$port} is already in use; drop --port to get a free one\n");
        exit(2);
    }

    $frontController = $routesFile === null ? null : frontControllerFor($routesFile);

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $serverProcess = proc_open(serveCommand($port, '127.0.0.1', $frontController), $descriptors, $pipes, ROOT);

    if (!is_resource($serverProcess)) {
        fwrite(STDERR, "failed to start php -S on port {$port}\n");
        exit(2);
    }

    register_shutdown_function('stopServer');

    // php -S needs a moment before it accepts connections.
    for ($attempt = 0; $attempt < 100; ++$attempt) {
        $probe = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
        if ($probe !== false) {
            fclose($probe);
            return;
        }
        usleep(50_000);
    }

    fwrite(STDERR, "server on port {$port} never became reachable\n");
    stopServer();
    exit(2);
}

function stopServer(): void
{
    global $serverProcess;

    if (is_resource($serverProcess)) {
        proc_terminate($serverProcess);
        proc_close($serverProcess);
        $serverProcess = null;
    }
}

/**
 * @return array{status: int, headers: list<string>, body: string}
 */
function http(string $method, string $url, ?string $data = null): array
{
    $options = [
        'http' => [
            'method' => $method,
            'follow_location' => 0,     // observe 302 instead of chasing it
            'ignore_errors' => true,    // observe 404 instead of warning
            'timeout' => 10,
        ],
    ];

    if ($data !== null && $method !== 'GET') {
        $options['http']['header'] = "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = $data;
    }

    $body = @file_get_contents($url, false, stream_context_create($options));

    /** @var list<string> $rawHeaders */
    $rawHeaders = $http_response_header ?? [];

    $status = 0;
    $headers = [];
    foreach ($rawHeaders as $header) {
        if (str_starts_with($header, 'HTTP/')) {
            // A redirect chain would emit several status lines; last one wins.
            $status = (int) (explode(' ', $header)[1] ?? 0);
            $headers = [];
            continue;
        }
        $headers[] = $header;
    }

    return [
        'status' => $status,
        'headers' => $headers,
        'body' => $body === false ? '' : $body,
    ];
}
