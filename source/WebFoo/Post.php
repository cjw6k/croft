<?php
/**
 * The Post class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Post class is the data model for all content in WebFoo
 */
class Post
{

	use Aether;

	/**
	 * Store a local reference to the active configuration
	 *
	 * @param Config $config The active configuration.
	 */
	public function __construct(Config $config)
	{
		$this->setConfig($config);
	}

	/**
	 * Make post directory in local storage based on publication date
	 *
	 * @param \DateTime $dt_published The publication date.
	 *
	 * @return boolean True  If post directory has been made.
	 *                 False If post directory has not been made.
	 */
	public function allocate(\DateTime $dt_published)
	{
		$this->setPublished($dt_published);

		if(!$this->_makeContentPath($dt_published->getTimestamp())){
			return false;
		}

		if(!$this->_takeNextPostId()){
			return false;
		}

		if(!mkdir($this->getContentPath() . $this->getContentId() . '/')){
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
			http_response_code(500);
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

		if(file_exists($this->getContentPath())){
			return true;
		}

		if(!mkdir($this->getContentPath(), 0755, true)){
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		$yaml = fopen($this->getContentPath() . 'thisday.yml', 'c+');
		if(!flock($yaml, LOCK_EX)){
			fclose($yaml);
			http_response_code(500);
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

		ftruncate($yaml, 0);
		rewind($yaml);
		fwrite($yaml, yaml_emit($this_day));
		fflush($yaml);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($yaml, LOCK_UN);
		fclose($yaml);

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
		$yaml = fopen($this->getContentPath() . 'thisday.yml', 'c+');
		if(!$yaml){
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		if(!flock($yaml, LOCK_EX)){
			fclose($yaml);
			http_response_code(500);
			$this->setResponse(
				array(
					'error' => 'broken',
					'error_description' => "the server encountered an unspecified internal error and could not complete the request"
				)
			);
			return false;
		}

		rewind($yaml);
		$this_day_raw = fread($yaml, filesize($this->getContentPath() . 'thisday.yml'));
		$this_day = yaml_parse($this_day_raw);
		ftruncate($yaml, 0);
		rewind($yaml);

		$this->setContentId($this_day['next_id']++);

		fwrite($yaml, yaml_emit($this_day));
		fflush($yaml);

		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($yaml, LOCK_UN);
		fclose($yaml);

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
		file_put_contents($this->getContentPath() . $this->getContentId() . '/web.foo', yaml_emit($front_matter) . $content);
	}

}
