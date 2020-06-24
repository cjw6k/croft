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
 * The Micropub\Post\Json class manages creation and storage of JSON-encoded posts
 */
class Json extends \cjw6k\WebFoo\Micropub\Post
{

	/**
	 * Create a new post
	 *
	 * @param \cjw6k\WebFoo\Config  $config    The active configuration.
	 * @param \cjw6k\WebFoo\Request $request   The current request.
	 * @param string                $client_id The client_id of the posting micropub client.
	 *
	 * @return void
	 */
	public function createPost(\cjw6k\WebFoo\Config $config, \cjw6k\WebFoo\Request $request, string $client_id)
	{
		$this->setMf2(json_decode(file_get_contents('php://input')));
		parent::createPost($config, $request, $client_id);
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
		if(isset($this->getMf2()->properties->published[0])){
			$dt_published = new DateTime($this->getMf2()->properties->published[0]);
		}

		if(!$dt_published){
			return new DateTime();
		}

		return $dt_published;
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
					isset($this->getMf2()->type[0]) ? $this->getMf2()->type[0] : 'h-entry',
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

		if(isset($this->getMf2()->properties->slug[0])){
			$front_matter['slug'] = $this->getMf2()->properties->slug[0];
		}

		$this->setFrontMatter($front_matter);

		$this->_setFrontMatterProperties();
	}

	/**
	 * Capture optional front matter properties from the POST parameters
	 *
	 * @return void
	 */
	protected function _setFrontMatterProperties()
	{
		$front_matter = $this->getFrontMatter();

		foreach($this->getMf2()->properties as $key => $set){
			switch($key){
				case 'access_token':
				case 'h':
				case 'slug':
				case 'published':
				case 'content':
					continue 2;
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

		$content = str_replace("\r\n", "\n", $content);

		file_put_contents($this->getContentPath() . $this->getContentId() . '/web.foo', yaml_emit($front_matter) . $content);
	}

}
