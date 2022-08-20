<?php

namespace a6a\a6a\Async;

use a6a\a6a\Extension\ExtensionInterface;
use a6a\a6a\Service\ServiceInterface;
use a6a\a6a\Storage\StorageInterface;

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
     */
    public function register(object $extension): void;

    /**
     * Complete queued tasks from earlier, after the current request has completed
     */
    public function run(): void;
}
