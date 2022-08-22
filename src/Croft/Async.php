<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Async\Asyncable;
use a6a\a6a\Async\Async as AsyncA6a;
use a6a\a6a\Extension\Extension;
use a6a\a6a\Service\Service;
use a6a\a6a\Storage\Segment;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\Storage;
use a6a\a6a\Storage\Store;

use function is_numeric;
use function now;
use function time;

use const LOCK_EX;
use const LOCK_NB;

/**
 * The Async class runs additional code after the user request has completed and been closed
 */
class Async implements AsyncA6a, Storable
{
    use Aether;

    /**
     * Store a local reference to the storage service
     *
     * @param Storage $storage The storage service.
     */
    public function __construct(Storage $storage)
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
            new Store(Segment::SYSTEM, 'async'),
        ];
    }

    /**
     * Register a service or extension that implements the async interface
     *
     * @param Service|Extension $extension A webfoo extension.
     */
    public function register(object $extension): void
    {
        if (! ($extension instanceof Asyncable)) {
            return;
        }

        $this->mergeExtensions($extension);
    }

    /**
     * Complete queued tasks from earlier, after the current request has completed
     *
     * Ensures that only one queue is being processed a time, a least 30 seconds between
     * queue processing tasks. Web-site visitors won't notice this extra processing, because the
     * response has already been sent and the connection has already been closed.
     *
     * Doing it this way allows to skip setting up cron-jobs to run on regular intervals, making
     * the software a little more portable, user-wise.
     */
    public function run(): void
    {
        if ($this->hasStored('.async-active')) {
            return;
        }

        $last_async = $this->readStored('.async-last');

        if (is_numeric($last_async) && (30 > (now() - $last_async))) {
            return;
        }

        $lock = $this->lockStored('.async-active', LOCK_EX | LOCK_NB);

        if (! $lock) {
            return;
        }

        $this->writeStored('.async-last', time());

        $extensions = $this->getExtensions();

        if ($extensions) {
            foreach ($extensions as $extension) {
                $extension->async();
            }
        }

        $this->unlockStored($lock);
        $this->deleteStored('.async-active');
    }

    /**
     * Check if the store has data at the given index
     *
     * @param string $index The index to the data store.
     *
     * @return bool True The index is set in storage.
 * False The index is not set in storage.
     */
    private function hasStored(string $index): bool
    {
        return $this->getStorage()->hasIndex(Segment::SYSTEM, 'async', $index);
    }

    /**
     * Read data from storage at the given index
     *
     * @param string $index The index to the data store.
     *
     * @return mixed|null The data from storage or null if the index is not in use.
     */
    private function readStored(string $index): mixed
    {
        return $this->getStorage()->load(Segment::SYSTEM, 'async', $index);
    }

    /**
     * Lock read/write operations from storage at the given index
     *
     * @param string $index The index to the data store.
     * @param int $operation See flock().
     *
     * @return mixed|null A handle for the lock or null on failure.
     */
    private function lockStored(string $index, int $operation = LOCK_EX): mixed
    {
        return $this->getStorage()->lock(Segment::SYSTEM, 'async', $index, $operation);
    }

    /**
     * Write data into storage at the given index
     *
     * @param string $index The index to the data store.
     * @param mixed $data The data to store.
     */
    private function writeStored(string $index, mixed $data): void
    {
        $this->getStorage()->store(Segment::SYSTEM, 'async', $index, $data);
    }

    /**
     * Unock read/write operations from storage at the given index
     *
     * @param mixed $lock The handle for the lock.
     */
    private function unlockStored(mixed $lock): void
    {
        $this->getStorage()->unlock($lock);
    }

    /**
     * Delete data from storage at the given index
     *
     * @param string $index The index to the data store.
     */
    private function deleteStored(string $index): void
    {
        $this->getStorage()->purge(Segment::SYSTEM, 'async', $index);
    }
}
