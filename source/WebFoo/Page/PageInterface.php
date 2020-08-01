<?php
/**
 * The PageInterface interface is herein defined.
 *
 * @package	WebFoo\Page
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Page;

use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Page service interface
 */
interface PageInterface extends ServiceInterface
{

	/**
	 * Store a local reference to the response
	 *
	 * @param ResponseInterface $response The response.
	 * @param StorageInterface  $storage  The storage service.
	 */
	public function __construct(ResponseInterface $response, StorageInterface $storage);

}
