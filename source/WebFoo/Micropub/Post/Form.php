<?php
/**
 * The Micropub\Post\Form class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub\Post;

use \DateTime;

/**
 * The Micropub\Form class handles post creation for www-form-url-encoded and multipart/form-data
 */
class Form extends \cjw6k\WebFoo\Micropub\Post
{

	use \cjw6k\WebFoo\Aether;

	/**
	 * Get the publication date of this post from the micropub request.
	 *
	 * Defaults to the current time if no publication date has been provided.
	 *
	 * @return DateTime The publication date.
	 */
	protected function _getPublicationDateFromRequest()
	{
		return $this->_getPublicationDate($this->getRequest()->post('published'));
	}

	/**
	 * Build the post record front matter from POST parameters
	 *
	 * @return void
	 */
	protected function _setFrontMatter()
	{
		$this->setPostType('h-' . ($this->getRequest()->post('h') ? $this->getRequest()->post('h') : 'entry'));
		$this->setPostSlug($this->getRequest()->post('slug'));

		parent::_setFrontMatter();

		$this->_embeddedMedia();
	}

	/**
	 * Capture optional front matter properties from the POST parameters
	 *
	 * @return void
	 */
	protected function _setFrontMatterProperties()
	{
		$front_matter = $this->getFrontMatter();

		foreach($this->getRequest()->post() as $key => $value){
			if($this->_reservedPropertyKey($key)){
				continue;
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
			$array_is_empty = true;

			foreach($value as $one_of){
				if(!empty($one_of)){
					$array_is_empty = false;
					break;
				}
			}

			if($array_is_empty){
				return;
			}

			if(!isset($front_matter['item']['properties'][$key])){
				$front_matter['item']['properties'][$key] = array();
			}

			$front_matter['item']['properties'][$key] = array_merge($front_matter['item']['properties'][$key], $value);

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

		if(!$media_folder_made && !mkdir($this->getPost()->getContentPath() . $this->getPost()->getContentId() . '/media/')){
			return;
		}
		$media_folder_made = true;

		$destination_file = $name . $counters[$name]++ . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
		move_uploaded_file($file['tmp_name'], $this->getPost()->getContentPath() . $this->getPost()->getContentId() . '/media/' . $destination_file);

		$front_matter['item']['properties'][$name][] = $this->getPost()->getUid() . 'media/' . $destination_file;

		$this->setFrontMatter($front_matter);
	}

	/**
	 * Store the post front matter and content into a post record on disk
	 *
	 * @return void
	 */
	protected function _storePost()
	{
		$content = $this->getRequest()->post('content');
		if(is_null($content)){
			$content = '';
		}
		$this->setPostContent($content);
		parent::_storePost();
	}

}
