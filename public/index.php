<?php

ini_set('display_errors', 1);
error_reporting(-1);

require __DIR__ . '/../vendor/autoload.php';

(function($thinger){
	$thinger->sling();
})(new cjw6k\WebFoo());
