<?php

/**
 * A debugging function which produces a var_dump of the argument, wrapped in <pre> for display on the web if not using the cli
 *
 * @param mixed	$data	the data to display
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

	echo 'DEBUG: ', $trace[0]['file'], ', Line ', $trace[0]['line'], $newline, $newline;
	var_dump($data);
	echo $newline;

	// If not in CLI, use HTML
	if('cli' != php_sapi_name()){
		echo '</pre>';
	}
}
