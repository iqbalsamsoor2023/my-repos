<?php

namespace App\Support;

final class TestExecutionGuard
{
    public const DEFAULT_BLOCKED_ENVIRONMENTS = 'production,staging';

    /**
     * @param  array<int, string>|string|null  $blockedEnvironments
     * @return array<int, string>
     */
    public static function blockedEnvironments(array|string|null $blockedEnvironments = null): array
    {
        if (is_array($blockedEnvironments)) {
            $environments = $blockedEnvironments;
        } else {
            $environments = explode(',', $blockedEnvironments ?? self::DEFAULT_BLOCKED_ENVIRONMENTS);
        }

        $normalized = array_map(
            static fn (string $environment): string => strtolower(trim($environment)),
            $environments
        );

        return array_values(array_filter($normalized, static fn (string $environment): bool => $environment !== ''));
    }

    /**
     * @param  array<int, string>|string|null  $blockedEnvironments
     */
    public static function shouldBlock(
        string $environment,
        array|string|null $blockedEnvironments = null,
        bool $allowStaging = false
    ): bool {
        $normalizedEnvironment = strtolower(trim($environment));

        if ($normalizedEnvironment === 'staging' && $allowStaging) {
            return false;
        }

        return in_array(
            $normalizedEnvironment,
            self::blockedEnvironments($blockedEnvironments),
            true,
        );
    }

    public static function resolveEnvironment(
        ?string $appEnvironment = null,
        ?string $environmentFromEnvFile = null
    ): string {
        foreach ([$appEnvironment, $environmentFromEnvFile] as $environment) {
            if (! is_string($environment)) {
                continue;
            }

            $normalized = strtolower(trim($environment));

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return 'production';
    }

    public static function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    public static function readAppEnvironmentFromEnvFile(string $basePath): ?string
    {
        $envFile = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.env';

        if (! is_file($envFile) || ! is_readable($envFile)) {
            return null;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            if (! str_starts_with($trimmedLine, 'APP_ENV=')) {
                continue;
            }

            $value = trim(substr($trimmedLine, strlen('APP_ENV=')));

            return trim($value, " \t\n\r\0\x0B'\"");
        }

        return null;
    }

    public static function blockedMessage(string $environment): string
    {
        if (strtolower($environment) === 'staging') {
            return 'Test execution is blocked in "staging". Set TEST_GUARD_ALLOW_STAGING=true to allow it.'.PHP_EOL;
        }

        return sprintf('Test execution is blocked in "%s" environment.', $environment).PHP_EOL;
    }
}
