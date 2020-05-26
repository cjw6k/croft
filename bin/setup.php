<?php

ini_set('display_errors', '1');
error_reporting(-1);

require __DIR__ . '/../vendor/autoload.php';

$status = (function(cjw6k\WebFoo $thinger, array $argv){
	return $thinger->setup($argv);
})(new cjw6k\WebFoo(), $argv);

exit($status);
