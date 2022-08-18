<?php
/**
 * The PostInterface interface is herein defined.
 *
 * @package WebFoo\Post
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Post;

use \DateTime;

use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Post service interface
 */
interface PostInterface extends ServiceInterface
{

    /**
     * Store a local reference to the active configuration
     *
     * @param ConfigInterface   $config   The active configuration.
     * @param ResponseInterface $response The response.
     * @param StorageInterface  $storage  The storage service.
     */
    public function __construct(ConfigInterface $config, ResponseInterface $response, StorageInterface $storage);

    /**
     * Make post directory in local storage based on publication date
     *
     * @param DateTime $dt_published The publication date.
     *
     * @return boolean True  If post directory has been made.
     *                 False If post directory has not been made.
     */
    public function allocate(DateTime $dt_published);

    /**
     * Store the post front matter and content into a post record on disk
     *
     * @param mixed  $front_matter The post front matter.
     * @param string $content      The post content.
     *
     * @return void
     */
    public function store($front_matter, string $content);

}
