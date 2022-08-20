<?php

namespace a6a\a6a\Post;

use a6a\a6a\Config\Config;
use a6a\a6a\Response\Response;
use a6a\a6a\Service\Service;
use a6a\a6a\Storage\Storage;
use DateTime;

/**
 * The Post service interface
 */
interface Post extends Service
{
    /**
     * Store a local reference to the active configuration
     *
     * @param Config $config The active configuration.
     * @param Response $response The response.
     * @param Storage $storage The storage service.
     */
    public function __construct(Config $config, Response $response, Storage $storage);

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
