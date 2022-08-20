<?php

namespace a6a\a6a\Media;

use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Service\ServiceInterface;
use a6a\a6a\Storage\StorageInterface;

/**
 * The Media service interface
 */
interface MediaInterface extends ServiceInterface
{
    /**
     * Store a local reference to the response
     *
     * @param ResponseInterface $response The response.
     * @param StorageInterface $storage The storage service.
     */
    public function __construct(ResponseInterface $response, StorageInterface $storage);
}
