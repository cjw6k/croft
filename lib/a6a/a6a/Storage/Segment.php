<?php

namespace a6a\a6a\Storage;

use ReflectionClass;

use function array_key_exists;
use function in_array;
use function array_values;
use function is_null;
use function get_called_class;

/**
 * The storage is segmented such that like purposes for data may be readily grouped by
 * implementation.
 *
 * Segment models an enum, with utility methods for checking that a given constant, by label or by
 * name, is valid.
 */
final class Segment
{
    /**
     * The zero value is unused to avoid edge case behaviours that originate with the falsity of 0
     * in PHP.
     */
    public const UNUSED = 0;

    /**
     * Data which has a transient nature and may be lost without consequence.
     */
    public const RUNTIME = 10;
    public const TEMP = 20;
    public const CACHE = 30;

    /**
     * Data which records various system state and may be lost with only minor inconvenience, e.g.
     * losing the value of an OAUTH bearer token.
     */
    public const SYSTEM = 50;
    public const LOG = 51;

    /**
     * Data which is the original work of the end-user of the software system, is subject to
     * copyright, is appropriate for a version control system, and should be treated in a backup
     * strategy outside the current software.
     */
    public const CONTENT = 60;
    public const WWW = 70;

    /**
     * A
     */
    public const EXTENSION = 80;

    /**
     * Cache the result of a reflection method
     *
     * @var array<string, mixed>|null
     */
    private static ?array $cache = null;

    /**
     * This private constructor prevents instantiation of the class, which has been declared final
     * to ensure the available segments are consistently applied.
     */
    private function __construct()
    {
        // prevents direct class instantiation
    }

    /**
     * Check if the constant, given by name, is defined here.
     *
     * @param string $segment The constant name.
     *
     * @return bool True The provided segment name is valid.
 * False The provided segment name is not valid.
     */
    public static function hasSegment(string $segment): bool
    {
        return array_key_exists($segment, self::getConstants());
    }

    /**
     * Check if the constant, given by value, is defined here.
     *
     * @param int $value The constant value.
     *
     * @return bool True The provided segment value is valid.
 * False The provided segment value is not valid.
     */
    public static function hasValue(int $value): bool
    {
        return in_array($value, array_values(self::getConstants()));
    }

    /**
     * Get the defined constants of this class
     *
     * @return array<string, mixed> The class contants.
     */
    private static function getConstants(): array
    {
        if (! is_null(self::$cache)) {
            return self::$cache;
        }

        $reflection = new ReflectionClass(self::class);
        self::$cache = $reflection->getConstants();

        return self::$cache;
    }
}
