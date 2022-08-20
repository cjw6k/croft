<?php

ini_set('display_errors', '1');
error_reporting(-1);

require __DIR__ . '/../vendor/autoload.php';

$status = (static fn (cjw6k\Croft\Croft $thinger, array $argv) => $thinger->setup($argv))(
    new croft\Croft(
    // Core Services
        [
            $config = new cjw6k\Croft\ConfigFoo\Config(),
            $request = new cjw6k\Croft\RequestFoo\Request(),
            $response = new cjw6k\Croft\ResponseFoo\Response(),
            new cjw6k\Croft\RouterFoo\Router($config, $request),
            $session = new cjw6k\Croft\SessionFoo\Session($config, $request),
            $storage = new cjw6k\Croft\StorageFoo\Storage($config),
            new cjw6k\Croft\AsyncFoo\Async($storage),
            new cjw6k\Croft\MediaFoo\Media($response, $storage),
            $post = new cjw6k\Croft\PostFoo\Post($config, $response, $storage),
            new cjw6k\Croft\PageFoo\Page($response, $storage),
        ],
        // Extensions
        [
            new cjw6k\Croft\IndieAuth\IndieAuth($config, $request, $response, $session, $storage),
            new cjw6k\Croft\Micropub\Micropub($config, $post, $request, $response),
            new cjw6k\Croft\Webmention\Webmention($config, $request, $response, $storage),
        ]
    ),
    $argv
);

exit($status);
