<?php

namespace a6a\a6a\Storage;

/**
 * The Storable interface
 *
 * A storable class has an opportunity to register stores with the storage service and optionally
 * provide callbacks to pack and unpack data in transit.
 */
interface Storable
{
    /**
     * Provides a list of stores to register with the Storage service to be serviced by this
     * object.
     *
     * @return mixed|null The list of stores to register or null if there are none.
     */
    public function getStores(): mixed;
}
