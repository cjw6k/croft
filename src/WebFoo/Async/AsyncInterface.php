<?php
/**
 * The AsyncInterface interface is herein defined.
 *
 * @package WebFoo\Async
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Async;

use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Async service interface.
 */
interface AsyncInterface extends ServiceInterface
{

    /**
     * Store a local reference to the storage service
     *
     * @param StorageInterface $storage The storage service.
     */
    public function __construct(StorageInterface $storage);

    /**
     * Register a service or extension that implements the async interface
     *
     * @param ServiceInterface|ExtensionInterface $extension A webfoo extension.
     *
     * @return void
     */
    public function register(object $extension);

    /**
     * Complete queued tasks from earlier, after the current request has completed
     *
     * @return void
     */
    public function run();

}
