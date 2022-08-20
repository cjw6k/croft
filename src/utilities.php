<?php

/**
 * A debugging function which produces a var_dump of the argument, wrapped in <pre> for display on the web if not using the cli
 *
 * @param mixed $data The data to display.
 *
 * @psalm-suppress ForbiddenCode
 *
 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
 */
function showMe(mixed $data): void
{
    // Determine which function called this method
    $trace = debug_backtrace();

    // Determine appropriate newline character
    $newline = PHP_EOL;

    if (php_sapi_name() != 'cli') {
        $newline = '<br>';

        // If not in CLI, use HTML
        echo '<pre>';
    }

    if (isset($trace[0]['file']) && isset($trace[0]['line'])) {
        echo 'DEBUG: ', $trace[0]['file'], ', Line ', $trace[0]['line'], $newline, $newline;
    }

    var_dump($data);
    echo $newline;

    // If not in CLI, use HTML
    if (php_sapi_name() == 'cli') {
        return;
    }

    echo '</pre>';
}

/**
 * Get the time the current request was made, or from the system clock if request time is not set
 *
 * @return int The current time as a unix time stamp.
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 */
function now(): int
{
    if (isset($_SERVER['REQUEST_TIME'])) {
        return $_SERVER['REQUEST_TIME'];
    }

    return time();
}

/**
 * Convert all stdClass in a mixed data structure to array, recursively
 *
 * @param mixed $data The data to be converted.
 *
 * @return mixed The data with no nested objects.
 */
function obj2arr(mixed $data): mixed
{
    if (is_object($data)) {
        $data = get_object_vars($data);
    }

    if (is_array($data)) {
        return array_map('obj2arr', $data);
    }

    return $data;
}
