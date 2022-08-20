<?php

namespace a6a\a6a\Media;

use a6a\a6a\Response\Response;
use a6a\a6a\Service\Service;
use a6a\a6a\Storage\Storage;

/**
 * The Media service interface
 */
interface Media extends Service
{
    /**
     * Store a local reference to the response
     *
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Response $response, Storage $storage);
}
