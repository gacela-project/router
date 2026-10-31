<?php

declare(strict_types=1);

namespace GacelaTest\Unit\Router\Validators;

use Gacela\Router\Validators\PathPatternGenerator;
use PHPUnit\Framework\TestCase;

final class PathPatternGeneratorTest extends TestCase
{
    public function test_empty_path_returns_root_pattern(): void
    {
        $pattern = PathPatternGenerator::generate('');

        self::assertSame('#^/?$#', $pattern);
    }

    public function test_simple_static_path(): void
    {
        $pattern = PathPatternGenerator::generate('users');

        self::assertSame('#^/users$#', $pattern);
    }

    public function test_single_mandatory_parameter(): void
    {
        $pattern = PathPatternGenerator::generate('users/{id}');

        self::assertSame('#^/users/([^\/]+)$#', $pattern);
    }

    public function test_single_optional_parameter(): void
    {
        $pattern = PathPatternGenerator::generate('users/{id?}');

        self::assertSame('#^/users(?:/([^\/]+))?$#', $pattern);
    }

    public function test_multiple_mandatory_parameters(): void
    {
        $pattern = PathPatternGenerator::generate('posts/{postId}/comments/{commentId}');

        self::assertSame('#^/posts/([^\/]+)/comments/([^\/]+)$#', $pattern);
    }

    public function test_mixed_mandatory_and_optional_parameters(): void
    {
        $pattern = PathPatternGenerator::generate('posts/{id}/comments/{commentId?}');

        self::assertSame('#^/posts/([^\/]+)/comments(?:/([^\/]+))?$#', $pattern);
    }

    public function test_only_optional_parameters_also_match_the_root(): void
    {
        $pattern = PathPatternGenerator::generate('{a?}/{b?}');

        self::assertSame('#^(?:/?|(?:/([^\/]+))?(?:/([^\/]+))?)$#', $pattern);
    }

    public function test_regex_metacharacters_in_a_static_segment_are_quoted(): void
    {
        self::assertSame('#^/v1\.0/([^\/]+)$#', PathPatternGenerator::generate('v1.0/{id}'));
        self::assertSame('#^/a\+b/([^\/]+)$#', PathPatternGenerator::generate('a+b/{id}'));
        self::assertSame('#^/api\(v1\)/([^\/]+)$#', PathPatternGenerator::generate('api(v1)/{id}'));
    }

    public function test_the_pattern_delimiter_in_a_static_segment_is_quoted(): void
    {
        self::assertSame('#^/a\#b/([^\/]+)$#', PathPatternGenerator::generate('a#b/{id}'));
    }
}
