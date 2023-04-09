<?php

declare(strict_types=1);

namespace GacelaRouter;

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

    public function __construct(
        private array $globalGet,
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self($_GET);
    }

    /**
     * @return self::METHOD_*
     */
    public static function method(): string
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function path(): string
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return (string)parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH,
        );
    }

    public function get(string $key): mixed
    {
        return $this->globalGet[$key] ?? null;
    }
}
