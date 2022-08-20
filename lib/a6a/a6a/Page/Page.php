<?php

namespace a6a\a6a\Page;

use a6a\a6a\Response\Response;
use a6a\a6a\Service\Service;
use a6a\a6a\Storage\Storage;

/**
 * The Page service interface
 */
interface Page extends Service
{
    /**
     * Store a local reference to the response
     *
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Response $response, Storage $storage);
}
