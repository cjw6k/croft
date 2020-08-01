<?php
/**
 * The Async class is herein defined.
 *
 * @package	WebFoo\AsyncFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\AsyncFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Async\Asyncable;
use \cjw6k\WebFoo\Async\AsyncInterface;
use \cjw6k\WebFoo\Extension\ExtensionInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Storage\Segment;
use \cjw6k\WebFoo\Storage\Storable;
use \cjw6k\WebFoo\Storage\StorageInterface;
use \cjw6k\WebFoo\Storage\Store;

/**
 * The Async class runs additional code after the user request has completed and been closed
 */
class Async implements AsyncInterface, Storable
{

	use Aether;

	/**
	 * Store a local reference to the storage service
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
	public function getStores()
	{
		return array(
			new Store(Segment::SYSTEM, 'async'),
		);
	}

	/**
	 * Register a service or extension that implements the async interface
	 *
	 * @param ServiceInterface|ExtensionInterface $extension A webfoo extension.
	 *
	 * @return void
	 */
	public function register(object $extension)
	{
		if(!($extension instanceof Asyncable)){
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
	 *
	 * @return void
	 */
	public function run()
	{
		if($this->_hasStored('.async-active')){
			return;
		}

		$last_async = $this->_readStored('.async-last');
		if(is_numeric($last_async) && (30 > (now() - $last_async))){
			return;
		}

		$lock = $this->_lockStored('.async-active', LOCK_EX | LOCK_NB);
		if(!$lock){
			return;
		}

		$this->_writeStored('.async-last', time());

		$extensions = $this->getExtensions();
		if($extensions){
			foreach($extensions as $extension){
				$extension->async();
			}
		}

		$this->_unlockStored($lock);
		$this->_deleteStored('.async-active');
	}

	/**
	 * Check if the store has data at the given index
	 *
	 * @param string $index The index to the data store.
	 *
	 * @return boolean True  The index is set in storage.
	 *                 False The index is not set in storage.
	 */
	private function _hasStored(string $index)
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
	private function _readStored(string $index)
	{
		return $this->getStorage()->load(Segment::SYSTEM, 'async', $index);
	}

	/**
	 * Lock read/write operations from storage at the given index
	 *
	 * @param string  $index     The index to the data store.
	 * @param integer $operation See flock().
	 *
	 * @return mixed|null A handle for the lock or null on failure.
	 */
	private function _lockStored(string $index, int $operation = LOCK_EX)
	{
		return $this->getStorage()->lock(Segment::SYSTEM, 'async', $index, $operation);
	}

	/**
	 * Write data into storage at the given index
	 *
	 * @param string $index The index to the data store.
	 * @param mixed  $data  The data to store.
	 *
	 * @return void
	 */
	private function _writeStored(string $index, $data)
	{
		$this->getStorage()->store(Segment::SYSTEM, 'async', $index, $data);
	}

	/**
	 * Unock read/write operations from storage at the given index
	 *
	 * @param mixed $lock The handle for the lock.
	 *
	 * @return void
	 */
	private function _unlockStored($lock)
	{
		$this->getStorage()->unlock($lock);
	}

	/**
	 * Delete data from storage at the given index
	 *
	 * @param string $index The index to the data store.
	 *
	 * @return void
	 */
	private function _deleteStored(string $index)
	{
		$this->getStorage()->purge(Segment::SYSTEM, 'async', $index);
	}

}
