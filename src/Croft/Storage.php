<?php

namespace Croft;

use a6a\a6a\Exception\YagniException;
use A6A\Aether\Aether;
use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Storage\Segment;
use a6a\a6a\Storage\StorageInterface;
use a6a\a6a\Storage\Store;

use function file_exists;
use function is_null;
use function dirname;
use function mkdir;
use function file_put_contents;
use function file_get_contents;
use function defined;
use function fopen;
use function flock;
use function fclose;

use const LOCK_UN;

use function unlink;

/**
 * The Storage service loads and stores data.
 */
class Storage implements StorageInterface
{
    use Aether;

    /**
     * The registered stores.
     *
     * @var array<mixed>
     */
    private array $_stores = [];

    /**
     * Store a local reference to the active configuration
     *
     * @param ConfigInterface $config The active configuration.
     */
    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    /**
     * Register a prefix for the storage segment.
     *
     * @param Store $store The store definition.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function register(Store $store): void
    {
        [$segment, $prefix, $ingress, $egress] = $store->pack();

     // @phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
        if (! (Segment::hasValue($segment))) {
            // throw exception
        }

     // @phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedIf

        $this->_stores[$segment][$prefix] = [
            'ingress' => $ingress,
            'egress' => $egress,
        ];
    }

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
    public function hasIndex(int $segment, string $prefix, string $index): bool
    {
        if (! isset($this->_stores[$segment][$prefix])) {
            // throw exception
            return false;
        }

        $path = $this->_getPathFromSegment($segment);

        if (empty($path)) {
            // throw exception
            return false;
        }

        return file_exists($path . $prefix . '/' . $index);
    }

    /**
     * Store data into a storage segment at the given prefixed index
     *
     * WARNING: will overwrite stored data with no notice.
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     * @param mixed $data The data to store.
     */
    public function store(int $segment, string $prefix, string $index, mixed $data): void
    {
        // WARNING: will overwrite stored data without any notice
        $path = $this->_getPathFromSegment($segment);

        if (empty($path)) {
            // throw exception
            return;
        }

        $ingress = $this->_stores[$segment][$prefix]['ingress'];
        $content = is_null($ingress) ? $data : $ingress($data);

        $parent_path = dirname($path . $prefix . '/' . $index);

        if (! file_exists($parent_path)) {
            mkdir($parent_path, 0755, true);
        }

        file_put_contents($path . $prefix . '/' . $index, $content);
    }

    /**
     * Load data from a storage segment at the given prefixed index.
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     *
     * @return mixed|null The data from storage or null if the prefixed index is not set.
     */
    public function load(int $segment, string $prefix, string $index): mixed
    {
        if (! $this->hasIndex($segment, $prefix, $index)) {
            return null;
        }

        $path = $this->_getPathFromSegment($segment);

        if (empty($path)) {
            // throw exception
            return null;
        }

        $egress = $this->_stores[$segment][$prefix]['egress'];
        $content = file_get_contents($path . $prefix . '/' . $index);

        return is_null($egress) ? $content : $egress($content);
    }

    /**
     * Map a given storage segment to a path on disk.
     *
     * @param int $segment The storage segment.
     *
     * @return string|null The storage path on disk, or null on failure.
     */
    private function _getPathFromSegment(int $segment): ?string
    {
        switch ($segment) {
            case Segment::SYSTEM:
                // $path = $this->getConfig()->getStorage()['system'];
                return defined('VAR_ROOT') ? VAR_ROOT : null;

            case Segment::CONTENT:
                // $path = $this->getConfig()->getStorage()['content'];
                return defined('CONTENT_ROOT') ? CONTENT_ROOT : null;

            default:
                // throw exception
                return null;
        }
    }

    /**
     * Lock read/write operations on storage at the given index of the prefixed storage segment
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     * @param int $operation See flock().
     *
     * @return mixed|null A handle for the lock or null on failure.
     */
    public function lock(int $segment, string $prefix, string $index, int $operation): mixed
    {
        $path = $this->_getPathFromSegment($segment);

        if (empty($path)) {
            // throw exception
            return null;
        }

        $parent_path = dirname($path . $prefix . '/' . $index);

        if (! file_exists($parent_path)) {
            mkdir($parent_path, 0755, true);
        }

        $lock_file = fopen($path . $prefix . '/' . $index, 'c+');

        if (! flock($lock_file, $operation)) {
            fclose($lock_file);

            return null;
        }

        return $lock_file;
    }

    /**
     * Unock read/write operations on storage at the given index of the prefixed storage segment
     *
     * @param mixed $lock The handle for the lock.
     */
    public function unlock(mixed $lock): void
    {
        /**
         * This is actually needed lol. Remove the suppression if you don't believe it.
         *
         * @psalm-suppress UnusedFunctionCall
         */
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
     * Delete data from the prefix storage segment at the given index
     *
     * @param int $segment The storage segment.
     * @param string $prefix The prefix.
     * @param string $index The index.
     */
    public function purge(int $segment, string $prefix, string $index)
    {
        $path = $this->_getPathFromSegment($segment);

        if (empty($path)) {
            throw new YagniException();
        }

        unlink($path . $prefix . '/' . $index);
    }
}
