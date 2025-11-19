<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Validators;

use Gacela\Router\Validators\PathValidator;
use Generator;
use PHPUnit\Framework\TestCase;

final class PathValidatorTest extends TestCase
{
    /**
     * @dataProvider validPathProvider
     */
    public function test_valid_paths(string $path): void
    {
        self::assertTrue(PathValidator::isValid($path));
    }

    /**
     * @dataProvider invalidPathProvider
     */
    public function test_invalid_paths(string $path): void
    {
        self::assertFalse(PathValidator::isValid($path));
    }

    public static function validPathProvider(): Generator
    {
        yield 'root path' => ['/'];

        yield 'single segment' => ['users'];
        yield 'multiple segments' => ['api/v1/users'];
        yield 'single character' => ['a'];
        yield 'two segments' => ['a/b'];
        yield 'three segments' => ['x/y/z'];

        yield 'single mandatory param' => ['users/{id}'];
        yield 'multiple mandatory params' => ['posts/{postId}/comments/{commentId}'];
        yield 'only mandatory param' => ['{id}'];

        yield 'single optional param' => ['users/{id?}'];
        yield 'multiple optional params' => ['archive/{year?}/{month?}'];
        yield 'only optional param' => ['{id?}'];

        yield 'mandatory then optional' => ['posts/{id}/comments/{commentId?}'];
        yield 'complex path with mixed params' => ['api/v1/users/{userId}/posts/{postId}/comments/{commentId?}'];

        yield 'hyphens in path' => ['api-v2/user-profile'];
        yield 'underscores in path' => ['api_v2/user_profile'];
        yield 'numbers in path' => ['api/v123/users'];
    }

    public static function invalidPathProvider(): Generator
    {
        yield 'empty string' => [''];

        yield 'leading slash - simple' => ['/users'];
        yield 'leading slash - with segments' => ['/api/v1'];
        yield 'leading slash - single char' => ['/a'];
        yield 'leading slash - with param' => ['/{id}'];
        yield 'leading slash - with multiple segments' => ['/users/{id}'];

        yield 'trailing slash - simple' => ['users/'];
        yield 'trailing slash - multiple segments' => ['api/v1/'];
        yield 'trailing slash - single char' => ['a/'];
        yield 'trailing slash - with param' => ['{id}/'];
        yield 'trailing slash - with multiple segments' => ['users/{id}/'];

        yield 'double slash in middle' => ['users//posts'];
        yield 'multiple double slashes' => ['a//b//c'];
        yield 'double slash at end' => ['api//'];
        yield 'triple slash' => ['a///b'];
        yield 'simple double slash' => ['a//b'];
        yield 'double slash with more segments' => ['x//y//z'];

        yield 'mandatory after optional' => ['users/{id?}/{name}'];
        yield 'multiple optional then mandatory' => ['archive/{year?}/{month?}/{day}'];
        yield 'static after optional' => ['users/{id?}/posts'];
        yield 'static after multiple optional' => ['archive/{year?}/latest'];
    }
}
