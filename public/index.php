<?php

ini_set('display_errors', '1');
error_reporting(-1);

require __DIR__ . '/../vendor/autoload.php';

(function(cjw6k\WebFoo\WebFoo $thinger){

	$thinger->sling();

})(new cjw6k\WebFoo\WebFoo(

	// Core Services
	array(
		$config = new cjw6k\WebFoo\ConfigFoo\Config(),
		$request = new cjw6k\WebFoo\RequestFoo\Request(),
		$response = new cjw6k\WebFoo\ResponseFoo\Response(),
		new cjw6k\WebFoo\RouterFoo\Router($config, $request),
		$session = new cjw6k\WebFoo\SessionFoo\Session($config, $request),
		$storage = new cjw6k\WebFoo\StorageFoo\Storage($config),
		new cjw6k\WebFoo\AsyncFoo\Async($storage),
		new cjw6k\WebFoo\MediaFoo\Media($response, $storage),
		$post = new cjw6k\WebFoo\PostFoo\Post($config, $response, $storage),
		new cjw6k\WebFoo\PageFoo\Page($response, $storage),
	),

	// Extensions
	array(
		new cjw6k\WebFoo\IndieAuth\IndieAuth($config, $request, $response, $session, $storage),
		new cjw6k\WebFoo\Micropub\Micropub($config, $post, $request, $response),
		new cjw6k\WebFoo\Webmention\Webmention($config, $request, $response, $storage),
	)

));
