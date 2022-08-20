<?php

namespace a6a\a6a\Storage;

use a6a\a6a\Config\Config;
use a6a\a6a\Service\Service;
use Exception;

interface Storage extends Service
{
    /**
     * Store a local reference to the active configuration
     *
     * @param Config $config The active configuration.
     *
     * @throws Exception The method has not been implemented.
     */
    public function __construct(Config $config);

    /**
     * Register a prefix in for storage in segment
     *
     * @param Store $store The store definition.
     */
    public function register(Store $store): void;

    /**
     * Check if a storage segment has data at the given prefixed index.
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     *
     * @return bool True The index is set on this prefix in the storage segmeent.
 * False The index is not set on this prefix in the storage segment.
     */
    public function hasIndex(int $segment, string $prefix, string $index): bool;

    /**
     * Store data into a storage segment at the given prefixed index.
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     * @param mixed $data The data to store.
     */
    public function store(int $segment, string $prefix, string $index, mixed $data): void;

    /**
     * Load data from a storage segment at the given prefixed index.
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     *
     * @return mixed|null The data from storage or null if the prefixed index is not set.
     */
    public function load(int $segment, string $prefix, string $index): mixed;
}
