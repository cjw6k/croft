<?php

// Make various paths available in globally defined constants
const SOURCE_ROOT = __DIR__ . '/';
const PACKAGE_ROOT = SOURCE_ROOT . '../';
const TEMPLATES_DEFAULT = PACKAGE_ROOT . 'templates/default/';
const TEMPLATES_LOCAL = PACKAGE_ROOT . 'templates/local/';

/**
 * A debugging function which produces a var_dump of the argument, wrapped in <pre> for display on the web if not using the cli
 *
 * @param mixed	$data The data to display.
 *
 * @return void
 *
 * @psalm-suppress ForbiddenCode
 *
 * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
 */
function showMe($data){
	// Determine which function called this method
	$trace = debug_backtrace();

	// Determine appropriate newline character
	$newline = PHP_EOL;
	if('cli' != php_sapi_name()){
		$newline = '<br>';

		// If not in CLI, use HTML
		echo '<pre>';
	}

	if(isset($trace[0]['file']) && isset($trace[0]['line'])){
		echo 'DEBUG: ', $trace[0]['file'], ', Line ', $trace[0]['line'], $newline, $newline;
	}
	var_dump($data);
	echo $newline;

	// If not in CLI, use HTML
	if('cli' != php_sapi_name()){
		echo '</pre>';
	}
}

/**
 * Get the time the current request was made, or from the system clock if request time is not set
 *
 * @return integer The current time as a unix time stamp.
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 */
function now()
{
	if(isset($_SERVER['REQUEST_TIME'])){
		return $_SERVER['REQUEST_TIME'];
	}

	return time();
}
