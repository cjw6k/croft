<?php
/**
 * The Post class is herein defined.
 *
 * @package	WebFoo\PostFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\PostFoo;

use \DateTime;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Post\PostInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Storage\Segment;
use \cjw6k\WebFoo\Storage\Storable;
use \cjw6k\WebFoo\Storage\StorageInterface;
use \cjw6k\WebFoo\Storage\Store;

/**
 * The Post class is the data model for all lexical content in WebFoo
 */
class Post implements PostInterface, Routable, Storable
{

	use Aether;

	/**
	 * Store a local reference to the active configuration
	 *
	 * @param ConfigInterface   $config   The active configuration.
	 * @param ResponseInterface $response The response.
	 * @param StorageInterface  $storage  The storage service.
	 */
	public function __construct(ConfigInterface $config, ResponseInterface $response, StorageInterface $storage)
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
	public function getRoutes()
	{
		return array(
			new Route('GET', '/{year:[0-9]{4}}/{month:(?:0[0-9])|(?:1[0-2])}/{day:(?:[012][0-9])|(?:3[0-1])}/{post_id:[0-9]+}/', 'sling', array('use_vars' => true)),
		);
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
			new Store(Segment::CONTENT, '', array($this, 'pack'), array($this, 'unpack')),
			new Store(Segment::SYSTEM, 'post'),
		);
	}

	/**
	 * Control content requests
	 *
	 * @param string[] $vars The hash of path components in the content request.
	 *
	 * @return string|string[] The template to render, optionally with alternate.
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	public function sling(array $vars)
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
		$path = CONTENT_ROOT . $index;

		if(!$this->_hasStored($index)){
			$this->getResponse()->setCode(404);
			return array('404.php', 'default.php');
		}

		$this->setContentSource($path);
		$this->_readStored($index);

		if($this->isLoadError()){
			// 500?
			$this->getResponse()->setCode(404);
			return array('404.php', 'default.php');
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
	public function pack($data)
	{
		list($front_matter, $content) = $data;
		$content = str_replace("\r\n", "\n", $content);
		return yaml_emit($front_matter) . $content;
	}

	/**
	 * Parse data from post storage
	 *
	 * @param string $content The packed content.
	 *
	 * @return void
	 */
	public function unpack(string $content)
	{
		$yaml = array();
		if(!preg_match('/^(?m)(---$.*^...)$/Us', $content, $yaml)){
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
	 * @return boolean True  If post directory has been made.
	 *                 False If post directory has not been made.
	 */
	public function allocate(DateTime $dt_published)
	{
		$this->setPublished($dt_published);

		if(!$this->_makeContentPath($dt_published->getTimestamp())){
			return false;
		}

		if(!$this->_takeNextPostId()){
			return false;
		}

		$this->setUid(rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/');

		return true;
	}

	/**
	 * Create a path in content/ for year/month/day of the new post if it doesn't exist
	 *
	 * @param integer $pub_ts The publication date of this post as unix timestamp.
	 *
	 * @return boolean True  If the path exists or has been created.
	 *                 False If the path does not exist and could not be created.
	 */
	private function _makeContentPath(int $pub_ts)
	{
		$pub_dt = getdate($pub_ts);

		if(!$pub_dt || !isset($pub_dt['year']) || !isset($pub_dt['mon']) || !isset($pub_dt['mday'])){
			$this->getResponse()->setCode(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$this->setUrlPath($pub_dt['year'] . '/' . str_pad($pub_dt['mon'], 2, '0', STR_PAD_LEFT) . '/' . str_pad($pub_dt['mday'], 2, '0', STR_PAD_LEFT) . '/');
		$this->setContentPath(CONTENT_ROOT . $this->getUrlPath());

		$storage = $this->getStorage();
		if($storage->hasIndex(Segment::SYSTEM, 'post', $this->getUrlPath())){
			return true;
		}

		$lock_file = $storage->lock(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', LOCK_EX);

		if(!$lock_file){
			$this->getResponse()->setCode(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$this_day = array(
			'next_id' => 1
		);

		$storage->store(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', yaml_emit($this_day));
		$storage->unlock($lock_file);

		return true;
	}

	/**
	 * Get the next id from the record of this day's posts and increment the next_id in the record
	 *
	 * @return boolean True  If the claim on the next id succeeded.
	 *                 False If the claim on the next id failed.
	 */
	private function _takeNextPostId()
	{
		$storage = $this->getStorage();
		if(!$storage->hasIndex(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml')){
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$file_lock = $storage->lock(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', LOCK_EX);
		if(!$file_lock){
			$this->getResponse()->setCode(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$this_day = yaml_parse($storage->load(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml'));
		$this->setContentId($this_day['next_id']++);
		$storage->store(Segment::SYSTEM, 'post', $this->getUrlPath() . 'thisday.yml', yaml_emit($this_day));

		$storage->unlock($file_lock);

		return true;
	}

	/**
	 * Store the post front matter and content into a post record on disk
	 *
	 * @param mixed  $front_matter The post front matter.
	 * @param string $content      The post content.
	 *
	 * @return void
	 */
	public function store($front_matter, string $content)
	{
		$content = str_replace("\r\n", "\n", $content);
		$this->_writeStored($this->getUrlPath() . $this->getContentId() . '/web.foo', array($front_matter, $content));
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
		return $this->getStorage()->hasIndex(Segment::CONTENT, '', $index);
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
		return $this->getStorage()->load(Segment::CONTENT, '', $index);
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
		$this->getStorage()->store(Segment::CONTENT, '', $index, $data);
	}

}
