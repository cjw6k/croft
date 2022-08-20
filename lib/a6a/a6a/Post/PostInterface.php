<?php

namespace a6a\a6a\Post;

use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Response\ResponseInterface;
use a6a\a6a\Service\ServiceInterface;
use a6a\a6a\Storage\StorageInterface;
use DateTime;

/**
 * The Post service interface
 */
interface PostInterface extends ServiceInterface
{
    /**
     * Store a local reference to the active configuration
     *
     * @param ConfigInterface $config The active configuration.
     * @param ResponseInterface $response The response.
     * @param StorageInterface $storage The storage service.
     */
    public function __construct(ConfigInterface $config, ResponseInterface $response, StorageInterface $storage);

    /**
     * Make post directory in local storage based on publication date
     *
     * @param DateTime $dt_published The publication date.
     *
     * @return bool True If post directory has been made.
 * False If post directory has not been made.
     */
    public function allocate(DateTime $dt_published): bool;

    /**
     * Store the post front matter and content into a post record on disk
     *
     * @param mixed $front_matter The post front matter.
     * @param string $content The post content.
     */
    public function store(mixed $front_matter, string $content): void;
}
