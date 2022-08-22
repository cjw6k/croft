<?php

namespace a6a\a6a\Async;

use a6a\a6a\Extension\Extension;
use a6a\a6a\Service\Service;
use a6a\a6a\Storage\Storage;

/**
 * The Async service interface.
 */
interface Async extends Service
{
    /**
     * Store a local reference to the storage service
     *
     * @param Storage $storage The storage service.
     */
    public function __construct(Storage $storage);

    /**
     * Register a service or extension that implements the async interface
     *
     * @param Service|Extension $extension A webfoo extension.
     */
    public function register(object $extension): void;

    /**
     * Complete queued tasks from earlier, after the current request has completed
     */
    public function run(): void;
}
