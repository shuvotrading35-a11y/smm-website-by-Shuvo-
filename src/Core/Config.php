<?php

declare(strict_types=1);

namespace SMMPanel\Core;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * Config — centralised application configuration loader.
 *
 * Reads .env once at boot and exposes typed helpers.
 */
final class Config
{
    private static array $cache = [];
    private static bool $booted = false;

    /** Boot the config system. Call once from index.php. */
    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }

        self::$booted = true;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key     Env key, e.g. 'DB_HOST'
     * @param mixed  $default Fallback when key is absent
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        self::$cache[$key] = self::cast($value);

        return self::$cache[$key];
    }

    /**
     * Require a value — throws if missing.
     */
    public static function required(string $key): mixed
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            throw new RuntimeException("Required config key [{$key}] is not set.");
        }

        return $value;
    }

    /** Return true only in production. */
    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }

    /** Return true only in debug mode. */
    public static function isDebug(): bool
    {
        return (bool) self::get('APP_DEBUG', false);
    }

    // ── Internal helpers ──────────────────────────────────────

    private static function cast(string $value): mixed
    {
        $lower = strtolower($value);

        if ($lower === 'true')  return true;
        if ($lower === 'false') return false;
        if ($lower === 'null')  return null;

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
