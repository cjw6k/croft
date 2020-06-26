<?php
/**
 * The Micropub\Post\Json class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub\Post;

use \DateTime;

/**
 * The Micropub\Json class handles post creationg for JSON-encoded posts
 */
class Json extends \cjw6k\WebFoo\Micropub\Post
{

	/**
	 * Create a new post
	 *
	 * @param \cjw6k\WebFoo\Request $request   The current request.
	 * @param string                $client_id The client_id of the posting micropub client.
	 *
	 * @return void
	 */
	public function createPost(\cjw6k\WebFoo\Request $request, string $client_id)
	{
		$this->setMf2(json_decode(file_get_contents('php://input')));
		parent::createPost($request, $client_id);
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
		$published = null;
		if(isset($this->getMf2()->properties->published[0])){
			$published = $this->getMf2()->properties->published[0];
		}

		return $this->_getPublicationDate($published);
	}

	/**
	 * Build the post record front matter from POST parameters
	 *
	 * @return void
	 */
	protected function _setFrontMatter()
	{
		$this->setPostType(isset($this->getMf2()->type[0]) ? $this->getMf2()->type[0] : 'h-entry');

		$post_slug = null;
		if(isset($this->getMf2()->properties->slug[0])){
			$post_slug = $this->getMf2()->properties->slug[0];
		}
		$this->setPostSlug($post_slug);

		parent::_setFrontMatter();
	}

	/**
	 * Capture optional front matter properties from the JSON
	 *
	 * @return void
	 */
	protected function _setFrontMatterProperties()
	{
		$front_matter = $this->getFrontMatter();

		foreach($this->getMf2()->properties as $key => $set){
			if($this->_reservedPropertyKey($key)){
				continue;
			}

			if(!empty($set)){
				$front_matter['item']['properties'][$key] = obj2arr($set);
			}
		}

		$this->setFrontMatter($front_matter);
	}

	/**
	 * Store the post front matter and content into a post record on disk_free_space
	 *
	 * @return void
	 */
	protected function _storePost()
	{
		$front_matter = $this->getFrontMatter();
		$content = $this->getMf2()->properties->content[0];
		if(is_object($this->getMf2()->properties->content[0])){
			if(isset($this->getMf2()->properties->content[0]->html)){
				$front_matter['media_type'] = 'text/html';
				$content = $this->getMf2()->properties->content[0]->html;
			}
		}

		if(!is_string($content)){
			$content = '';
		}

		$this->setFrontMatter($front_matter);
		$this->setPostContent($content);
		parent::_storePost();
	}

}
