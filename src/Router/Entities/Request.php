<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

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

    private function __construct(
        private array $query,
        private array $request,
        private array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function isMethod(string $method): bool
    {
        /** @var string $requestMethod */
        $requestMethod = $this->server['REQUEST_METHOD'];

        return $requestMethod === $method;
    }

    public function path(): string
    {
        /** @var string $requestUri */
        $requestUri = $this->server['REQUEST_URI'];
        /** @var string $parsedUrl */
        $parsedUrl = parse_url($requestUri, PHP_URL_PATH);

        return $parsedUrl;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->request[$key]
            ?? $this->query[$key]
            ?? $default;
    }
}
