<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Support\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function test_rejects_unsupported_php_before_wordpress(): void
    {
        self::assertSame(
            'Kiln Cleanup requires PHP 8.2.0 or later; this site runs PHP 8.1.99.',
            Environment::compatibilityIssue('8.2.0', '6.9', '8.1.99', '7.0'),
        );
    }

    public function test_rejects_unsupported_wordpress(): void
    {
        self::assertSame(
            'Kiln Cleanup requires WordPress 6.9 or later; this site runs WordPress 6.8.9.',
            Environment::compatibilityIssue('8.2.0', '6.9', '8.2.0', '6.8.9'),
        );
    }

    public function test_accepts_supported_environment(): void
    {
        self::assertNull(Environment::compatibilityIssue('8.2.0', '6.9', '8.3.0', '7.0'));
    }
}
