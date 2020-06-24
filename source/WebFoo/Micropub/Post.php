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
		$this->_embeddedMedia();
		$this->_storePost();

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
		$dt_published = $this->_getPublicationDateFromRequest();

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
	 * Get the publication date of this post from the micropub request.
	 *
	 * Defaults to the current time if no publication date has been provided.
	 *
	 * @return DateTime The publication date.
	 */
	protected function _getPublicationDateFromRequest()
	{
		$dt_published = null;
		if($this->getRequest()->post('published')){
			$dt_published = new DateTime($this->getRequest()->post('published'));
		}

		if(!$dt_published){
			return new DateTime();
		}

		return $dt_published;
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
	protected function _setFrontMatter()
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
					'uid' => array(
						rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/',
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

			$this->_setFrontMatterProperty($front_matter, $key, $value);
		}

		$this->setFrontMatter($front_matter);
	}

	/**
	 * Capture one front matter property
	 *
	 * @param mixed  $front_matter The front matter of the post.
	 * @param string $key          The index provided in POST.
	 * @param mixed  $value        The value of POST at the given index.
	 *
	 * @return void
	 */
	protected function _setFrontMatterProperty(&$front_matter, string $key, $value)
	{
		if(is_array($value)){
			if(!empty($value)){
				if(!isset($front_matter['item']['properties'][$key])){
					$front_matter['item']['properties'][$key] = array();
				}
				$front_matter['item']['properties'][$key] = array_merge($front_matter['item']['properties'][$key], $value);
			}
			return;
		}

		if(!empty($value)){
			$front_matter['item']['properties'][$key][] = $value;
		}
	}

	/**
	 * Capture the embedded media in a post
	 *
	 * @return void
	 */
	private function _embeddedMedia()
	{
		$files = $this->getRequest()->files();
		if(!$files){
			return;
		}

		foreach($files as $name => $set){
			switch($name){
				case 'photo':
				case 'video':
				case 'audio':
					break;

				default:
					continue 2;
			}

			if(is_array($set['error'])){
				foreach(array_keys($set['error']) as $key){
					$this->_storeMedia(
						$name,
						array(
							'error' => $set['error'][$key],
							'name' => $set['name'][$key],
							'tmp_name' => $set['tmp_name'][$key],
							'size' => $set['size'][$key],
						)
					);
				}
				continue;
			}

			$this->_storeMedia($name, $set);
		}
	}

	/**
	 * Store one media item from the post locally
	 *
	 * @param string $name The property name of the media item.
	 * @param mixed  $file The relevant parameters from $_FILE.
	 *
	 * @return void
	 *
	 * This will suppress UndefinedVariable warnings. It is necessary because PHPMD misses that the
	 * variable $counters has been defined with the static keyword at the top of this method.
	 *
	 * @SuppressWarnings(PHPMD.UndefinedVariable)
	 */
	private function _storeMedia(string $name, $file)
	{
		static $counters = array(
			'photo' => 1,
			'video' => 1,
			'audio' => 1,
		);

		static $media_folder_made = false;

		$front_matter = $this->getFrontMatter();

		if(UPLOAD_ERR_OK != $file['error']){
			return;
		}

		if(!is_uploaded_file($file['tmp_name'])){
			return;
		}

		if(!$media_folder_made && !mkdir($this->getContentPath() . $this->getContentId() . '/media/')){
			return;
		}
		$media_folder_made = true;

		$destination_file = $name . $counters[$name]++ . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
		move_uploaded_file($file['tmp_name'], $this->getContentPath() . $this->getContentId() . '/media/' . $destination_file);

		$front_matter['item']['properties'][$name][] = rtrim($this->getConfig()->getMe(), '/') . '/' . $this->getUrlPath() . $this->getContentId() . '/media/' . $destination_file;

		$this->setFrontMatter($front_matter);
	}

	/**
	 * Store the post front matter and content into a post record on disk
	 *
	 * @return void
	 */
	protected function _storePost()
	{
		file_put_contents($this->getContentPath() . $this->getContentId() . '/web.foo', yaml_emit($this->getFrontMatter()) . $this->getRequest()->post('content'));
	}

}
