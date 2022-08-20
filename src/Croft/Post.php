<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Config\Config;
use a6a\a6a\Post\Post as PostA6a;
use a6a\a6a\Response\Response;
use a6a\a6a\Router\Routable;
use a6a\a6a\Router\Route;
use a6a\a6a\Storage\Segment;
use a6a\a6a\Storage\Storable;
use a6a\a6a\Storage\Storage;
use a6a\a6a\Storage\Store;
use DateTime;
use DateTimeImmutable;

use function implode;
use function str_replace;
use function yaml_emit;
use function preg_match;
use function yaml_parse;
use function trim;
use function substr;
use function strlen;
use function rtrim;

use const LOCK_EX;

/**
 * The Post class is the data model for all lexical content in WebFoo
 */
class Post implements PostA6a, Routable, Storable
{
    use Aether;

    /**
     * Store a local reference to the active configuration
     *
     * @param Config $config The active configuration.
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Config $config, Response $response, Storage $storage)
    {
        $this->setConfig($config);
        $this->setResponse($response);
        $this->setStorage($storage);
    }

    /**
     * Provides a list of routes to register with the Router to be serviced by this service.
     *
     * @return mixed|null The list of routes to register or null if there are none.
     */
    public function getRoutes(): mixed
    {
        return [
            new Route(
                'GET',
                '/{year:[0-9]{4}}/{month:(?:0[0-9])|(?:1[0-2])}/{day:(?:[012][0-9])|(?:3[0-1])}/{post_id:[0-9]+}/',
                'sling',
                ['use_vars' => true]
            ),
        ];
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
            new Store(Segment::CONTENT, '', [$this, 'pack'], [$this, 'unpack']),
            new Store(Segment::SYSTEM, 'post'),
        ];
    }

    /**
     * Control content requests
     *
     * @param array<string> $vars The hash of path components in the content request.
     *
     * @return string|array<string> The template to render, optionally with alternate.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    public function sling(array $vars): string|array
    {
        /**
         * The Router will not start this action unless year is set
         *
         * @psalm-suppress PossiblyUndefinedStringArrayOffset
         */
        $this->setYear($vars['year']);

        /**
         * The Router will not start this action unless month is set
         *
         * @psalm-suppress PossiblyUndefinedStringArrayOffset
         */
        $this->setMonth($vars['month']);

        /**
         * The Router will not start this action unless day is set
         *
         * @psalm-suppress PossiblyUndefinedStringArrayOffset
         */
        $this->setDay($vars['day']);

        /**
         * The Router will not start this action unless post_id is set
         *
         * @psalm-suppress PossiblyUndefinedStringArrayOffset
         */
        $this->setPostId($vars['post_id']);

        $index = implode('/', $vars) . '/web.foo';
        $path = From::CONTENT->dir() . $index;

        if (! $this->hasStored($index)) {
            $this->getResponse()->setCode(404);

            return ['404.php', 'default.php'];
        }

        $this->setContentSource($path);
        $this->readStored($index);

        if ($this->isLoadError()) {
            // 500?
            $this->getResponse()->setCode(404);

            return ['404.php', 'default.php'];
        }

        return 'content.php';
    }

    /**
     * Pack data into a form fit for storage.
     *
     * @param mixed $data The data to pack.
     *
     * @return string The packed content.
     */
    public function pack(mixed $data): string
    {
        [$front_matter, $content] = $data;
        $content = str_replace("\r\n", "\n", $content);

        return yaml_emit($front_matter) . $content;
    }

    /**
     * Parse data from post storage
     *
     * @param string $content The packed content.
     */
    public function unpack(string $content): void
    {
        $yaml = [];

        if (! preg_match('/^(?m)(---$.*^...)$/Us', $content, $yaml)) {
            $this->isLoadError(true);

            return;
        }

        $this->setFrontMatter(yaml_parse($yaml[1]));
        $this->setContent(trim(substr($content, strlen($yaml[1]))));
    }

    /**
     * Make post directory in local storage based on publication date
     *
     * @param DateTime $dt_published The publication date.
     *
     * @return bool True If post directory has been made.
 * False If post directory has not been made.
     */
    public function allocate(DateTime $dt_published): bool
    {
        $this->setPublished($dt_published);

        if (! $this->makeContentPath($dt_published->getTimestamp())) {
            return false;
        }

        if (! $this->takeNextPostId()) {
            return false;
        }

        $this->setUid(rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/');

        return true;
    }

    /**
     * Create a path in content/ for year/month/day of the new post if it doesn't exist
     *
     * @param int $pub_ts The publication date of this post as unix timestamp.
     *
     * @return bool True If the path exists or has been created.
 * False If the path does not exist and could not be created.
     */
    private function makeContentPath(int $pub_ts): bool
    {
        $publicationDateTime = new DateTimeImmutable((string)$pub_ts);
        $this->setUrlPath($publicationDateTime->format('Y/m/d/'));
        $this->setContentPath(From::CONTENT->dir() . $this->getUrlPath());

        $storage = $this->getStorage();

        if ($storage->hasIndex(Segment::SYSTEM, 'post', $this->getUrlPath())) {
            return true;
        }

        $lock_file = $storage->lock(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', LOCK_EX);

        if (! $lock_file) {
            $this->getResponse()->setCode(500);
            $this->setResponse(
                [
                    'error' => 'broken',
                    'error_description' => "the server encountered an unspecified internal error and could not complete the request",
                ]
            );

            return false;
        }

        $this_day = [
            'next_id' => 1,
        ];

        $storage->store(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', yaml_emit($this_day));
        $storage->unlock($lock_file);

        return true;
    }

    /**
     * Get the next id from the record of this day's posts and increment the next_id in the record
     *
     * @return bool True If the claim on the next id succeeded.
 * False If the claim on the next id failed.
     */
    private function takeNextPostId(): bool
    {
        $storage = $this->getStorage();

        if (! $storage->hasIndex(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml')) {
            $this->setResponse(
                [
                    'error' => 'broken',
                    'error_description' => "the server encountered an unspecified internal error and could not complete the request",
                ]
            );

            return false;
        }

        $file_lock = $storage->lock(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', LOCK_EX);

        if (! $file_lock) {
            $this->getResponse()->setCode(500);
            $this->setResponse(
                [
                    'error' => 'broken',
                    'error_description' => "the server encountered an unspecified internal error and could not complete the request",
                ]
            );

            return false;
        }

        $this_day = yaml_parse($storage->load(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml'));
        $this->setContentId($this_day['next_id']);
        $this_day['next_id']++;
        $storage->store(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', yaml_emit($this_day));

        $storage->unlock($file_lock);

        return true;
    }

    /**
     * Store the post front matter and content into a post record on disk
     *
     * @param mixed $front_matter The post front matter.
     * @param string $content The post content.
     */
    public function store(mixed $front_matter, string $content): void
    {
        $content = str_replace("\r\n", "\n", $content);
        $this->writeStored($this->getUrlPath() . $this->getContentId() . '/web.foo', [$front_matter, $content]);
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
        return $this->getStorage()->hasIndex(Segment::CONTENT, '', $index);
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
        return $this->getStorage()->load(Segment::CONTENT, '', $index);
    }

    /**
     * Write data into storage at the given index
     *
     * @param string $index The index to the data store.
     * @param mixed $data The data to store.
     */
    private function writeStored(string $index, mixed $data): void
    {
        $this->getStorage()->store(Segment::CONTENT, '', $index, $data);
    }
}
