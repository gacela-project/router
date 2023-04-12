<?php

declare(strict_types=1);

namespace Gacela\Router;

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

    private static ?self $instance = null;

    private function __construct(
        private array $query,
        private array $request,
        private array $server,
    ) {
    }

    public static function resetCache(): void
    {
        self::$instance = null;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self($_GET, $_POST, $_SERVER);
        }
        return self::$instance;
    }

    public function isMethod(string $method): bool
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return (string)$this->server['REQUEST_METHOD'] === $method;
    }

    public function path(): string
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return (string)parse_url(
            (string)$this->server['REQUEST_URI'],
            PHP_URL_PATH,
        );
    }

    public function get(string $key): mixed
    {
        return $this->request[$key]
            ?? $this->query[$key]
            ?? null;
    }
}
