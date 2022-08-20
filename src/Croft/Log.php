<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Storage\Segment;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\StorageInterface;
use a6a\a6a\Storage\Store;

/**
 * Log writes to the LOG storage segment.
 */
class Log implements Storable
{
    use Aether;

    /**
     * Store a local reference to storage.
     *
     * @param StorageInterface $storage The storage service.
     */
    public function __construct(StorageInterface $storage)
    {
        $this->setStorage($storage);
    }

    /**
     * Provides a list of stores to register with the Storage service to be serviced by this
     * object.
     *
     * @return mixed|null The list of stores to register or null if there are none.
     */
    public function getStores(): mixed
    {
        return [
            new Store(Segment::LOG, ''),
        ];
    }
}
