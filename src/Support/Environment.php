<?php

declare(strict_types=1);

namespace KilnCleanup\Support;

final class Environment
{
    public static function compatibilityIssue(
        string $minimumPhpVersion,
        string $minimumWordPressVersion,
        ?string $phpVersion = null,
        ?string $wordPressVersion = null,
    ): ?string {
        $phpVersion ??= PHP_VERSION;
        $wordPressVersion ??= $GLOBALS['wp_version'] ?? '0.0.0';

        if (version_compare($phpVersion, $minimumPhpVersion, '<')) {
            return sprintf(
                'Kiln Cleanup requires PHP %s or later; this site runs PHP %s.',
                $minimumPhpVersion,
                $phpVersion,
            );
        }

        if (version_compare($wordPressVersion, $minimumWordPressVersion, '<')) {
            return sprintf(
                'Kiln Cleanup requires WordPress %s or later; this site runs WordPress %s.',
                $minimumWordPressVersion,
                $wordPressVersion,
            );
        }

        return null;
    }
}
