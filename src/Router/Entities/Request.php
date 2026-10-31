<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use function is_string;

final class Request
{
    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_GET = 'GET';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_TRACE = 'TRACE';

    public const ALL_METHODS = [
        self::METHOD_CONNECT,
        self::METHOD_DELETE,
        self::METHOD_GET,
        self::METHOD_HEAD,
        self::METHOD_OPTIONS,
        self::METHOD_PATCH,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_TRACE,
    ];

    /** Where an absent or unusable REQUEST_URI resolves to. */
    private const DEFAULT_PATH = '/';

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $server
     */
    private function __construct(
        private array $query,
        private array $request,
        private array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        /** @var array<string, mixed> $get */
        $get = $_GET;
        /** @var array<string, mixed> $post */
        $post = $_POST;
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        return new self($get, $post, $server);
    }

    /**
     * The empty string when REQUEST_METHOD is absent or not a string, which
     * matches no route rather than crashing on the way in.
     */
    public function method(): string
    {
        return $this->serverString('REQUEST_METHOD');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === $method;
    }

    /**
     * The path of REQUEST_URI, or '/' when there is nothing usable to read.
     *
     * A uri starting with '/' is origin-form, which is what a server receives, so
     * its path is everything before '?' or '#', untouched. Handing it to
     * parse_url() would read '//host/admin' as a host plus '/admin' and serve a
     * route the front proxy never saw under that path. Dot segments and repeated
     * slashes are left alone for the same reason: the proxy decides those.
     *
     * Anything else (an absolute-form 'https://host/path') goes through
     * parse_url(), which returns null when the uri carries no path ('https://host',
     * '?a=1') and false when it cannot parse it at all ('http://:80'), so neither
     * can be handed back through a string return type.
     */
    public function path(): string
    {
        $uri = $this->serverString('REQUEST_URI');

        $path = str_starts_with($uri, '/')
            ? substr($uri, 0, strcspn($uri, '?#'))
            : parse_url($uri, PHP_URL_PATH);

        // A run of slashes names no resource ('//' is a bare authority marker).
        return is_string($path) && trim($path, '/') !== ''
            ? $path
            : self::DEFAULT_PATH;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        /** @var mixed $result */
        $result = $this->request[$key] ?? $this->query[$key] ?? null;

        if ($result !== null) {
            return $result;
        }

        return $default;
    }

    /**
     * A $_SERVER value, or '' when the key is absent or does not hold a string.
     *
     * @psalm-suppress MixedAssignment narrowed on the next line
     */
    private function serverString(string $key): string
    {
        $value = $this->server[$key] ?? null;

        return is_string($value) ? $value : '';
    }
}
