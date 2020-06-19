<?php
/**
 * The Micropub\Post class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub;

use \DateTime;
use \cjw6k\WebFoo\Exception\Redirect;

/**
 * The Micropub\Post class manages creation and storage of posts
 */
class Post
{

	use \cjw6k\WebFoo\Aether;

	/**
	 * Create a new post
	 *
	 * @param \cjw6k\WebFoo\Config  $config    The active configuration.
	 * @param \cjw6k\WebFoo\Request $request   The current request.
	 * @param string                $client_id The client_id of the posting micropub client.
	 *
	 * @throws \cjw6k\WebFoo\Exception\Redirect A HTTP redirect to the new post.
	 *
	 * @return void
	 */
	public function createPost(\cjw6k\WebFoo\Config $config, \cjw6k\WebFoo\Request $request, string $client_id)
	{
		$this->setConfig($config);
		$this->setRequest($request);
		$this->setClientId($client_id);

		if(!$this->_allocatePost()){
			return;
		}

		$this->_setFrontMatter();

		file_put_contents($this->getContentPath() . $this->getContentId() . '/web.foo', yaml_emit($this->getFrontMatter()) . $this->getRequest()->post('content'));

		http_response_code(201);
		throw new Redirect(rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/');
	}

	/**
	 * Make post directory in local storage based on publication date
	 *
	 * @return boolean True  If post directory has been made.
	 *                 False If post directory has not been made.
	 */
	private function _allocatePost()
	{
		$dt_published = null;
		if($this->getRequest()->post('published')){
			$dt_published = new DateTime($this->getRequest()->post('published'));
		}

		if(!$dt_published){
			$dt_published = new DateTime();
		}

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
	 * Build the post record front matter from POST parameters
	 *
	 * @return void
	 */
	private function _setFrontMatter()
	{
		$front_matter = array(
			'client_id' => $this->getClientId(),
			'media_type' => 'text/plain',
			'item' => array(
				'type' => array(
					'h-' . ($this->getRequest()->post('h') ? $this->getRequest()->post('h') : 'entry'),
				),
				'properties' => array(
					'published' => array(
						$this->getPublished()->format('c'),
					),
				),
			),
		);

		if($this->getRequest()->post('slug')){
			$front_matter['slug'] = $this->getRequest()->post('slug');
		}

		$this->setFrontMatter($front_matter);

		$this->_setFrontMatterProperties();
	}

	/**
	 * Capture optional front matter properties from the POST parameters
	 *
	 * @return void
	 */
	private function _setFrontMatterProperties()
	{
		$front_matter = $this->getFrontMatter();

		foreach($this->getRequest()->post() as $key => $value){
			switch($key){
				case 'access_token':
				case 'h':
				case 'slug':
				case 'published':
				case 'content':
					continue 2;
			}

			if(is_array($value)){
				if(!isset($front_matter['item']['properties'][$key])){
					$front_matter['item']['properties'][$key] = array();
				}
				$front_matter['item']['properties'][$key] = array_merge($front_matter['item']['properties'][$key], $value);
				continue;
			}

			$front_matter['item']['properties'][$key][] = $value;
		}

		$this->setFrontMatter($front_matter);
	}

}
