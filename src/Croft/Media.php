<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Media\Media as MediaA6a;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Storage\Storage;

use function array_pop;
use function implode;
use function file_exists;
use function mime_content_type;
use function readfile;

/**
 * The Media class slings multimedia content
 */
class Media implements MediaA6a, Routable
{
    use Aether;

    /**
     * Store a local reference to the response
     *
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Response $response, Storage $storage)
    {
        $this->setResponse($response);
        $this->setStorage($storage);
    }

    /**
     * Provides a list of routes to register with the Router to be serviced by this service.
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed
    {
        return [
            new Route('GET', '/{year:[0-9]{4}}/{month:0[0-9]|1[0-2]}/{day:(?:[012][0-9])|3[0-1]}/{post_id:[0-9]+}/media/{media:.*}', 'sling', ['use_vars' => true]),
        ];
    }

    /**
     * Control content-media requests
     *
     * @param array<string> $vars The hash of path components in the content request.
     *
     * @return array<string>|null The template to render, with alternate, or null to skip rendering.
     */
    public function sling(array $vars): ?array
    {
        $filename = array_pop($vars);
        $post_record_path = implode('/', $vars) . '/';
        $path = From::CONTENT->dir() . $post_record_path . 'media/' . $filename;

        if (! file_exists($path)) {
            $this->getResponse()->setCode(404);

            return ['404.php', 'default.php'];
        }

        $this->getResponse()->mergeHeaders('Content-Type: ' . mime_content_type($path));
        readfile($path);

        return null;
    }
}
