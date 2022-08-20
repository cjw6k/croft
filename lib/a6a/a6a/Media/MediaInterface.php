<?php
/**
 * The MediaInterface interface is herein defined.
 *
 * @package WebFoo\Media
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Media;

use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Media service interface
 */
interface MediaInterface extends ServiceInterface
{

    /**
     * Store a local reference to the response
     *
     * @param ResponseInterface $response The response.
     * @param StorageInterface  $storage  The storage service.
     */
    public function __construct(ResponseInterface $response, StorageInterface $storage);

}
