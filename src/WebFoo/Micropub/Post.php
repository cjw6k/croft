<?php
/**
 * The Post class is herein defined.
 *
 * @package WebFoo\Micropub
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Micropub;

use \DateTime;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Exception\Redirect;
use \cjw6k\WebFoo\Post\PostInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;

/**
 * The Micropub\Post class handles post creation
 */
class Post
{

    use Aether;

    /**
     * Store a local reference to a model post
     *
     * @param PostInterface     $post     The model post.
     * @param RequestInterface  $request  The current request.
     * @param ResponseInterface $response The response.
     */
    public function __construct(PostInterface $post, RequestInterface $request, ResponseInterface $response)
    {
        $this->setPost($post);
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Create a new post
     *
     * @param string $client_id The client_id of the posting micropub client.
     *
     * @throws Redirect A HTTP redirect to the new post.
     *
     * @return void
     */
    public function createPost(string $client_id)
    {
        $this->setClientId($client_id);

        if(!$this->_allocate()) {
            return;
        }

        $this->_setFrontMatter();
        $this->_storePost();

        $this->getResponse()->setCode(201);
        throw new Redirect($this->getPost()->getUid());
    }

    /**
     * Allocate a new post
     *
     * @return boolean True  If post allocation works.
     *                 False If post allocation does not work.
     */
    private function _allocate()
    {
        return $this->getPost()->allocate(
            $this->_getPublicationDateFromRequest()
        );
    }

    /**
     * Get the publication date of this post from the micropub request
     *
     * Defaults to the current time if no publication date has been provided.
     *
     * @return DateTime The publication date.
     */
    protected function _getPublicationDateFromRequest()
    {
        return $this->_getPublicationDate();
    }

    /**
     * Get the publication date of this post as a DateTime from a string
     *
     * @param string|null $published The publication time.
     *
     * @return DateTime The publication time.
     */
    protected function _getPublicationDate($published = null)
    {
        $dt_published = new DateTime(is_null($published) ? 'now' : $published);
        $this->setPublished($dt_published);
        return $dt_published;
    }

    /**
     * Build the post record front matter
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
        $this->getPostType(),
        ),
        'properties' => array(
                    'published' => array(
                        $this->getPublished()->format('c'),
        ),
                    'uid' => array(
                        $this->getPost()->getUid(),
        ),
        ),
        ),
        );

        if(!is_null($this->getPostSlug())) {
            $front_matter['slug'] = $this->getPostSlug();
        }

        $this->setFrontMatter($front_matter);

        $this->_setFrontMatterProperties();
    }

    /**
     * Check if the provided key is a micropub reserved key
     *
     * Reserved keys are not stored in the properties array of the post record.
     *
     * @param string $key The key to check against the list of reserved keys.
     *
     * @return boolean True  If the key is reserved.
     *                 False If the key is not reserved.
     */
    protected function _reservedPropertyKey(string $key)
    {
        switch($key){
        case 'access_token':
        case 'h':
        case 'slug':
        case 'published':
        case 'content':
            return true;
        }

        return false;
    }

    /**
     * Store the post front matter and content into a post record on disk
     *
     * @return void
     */
    protected function _storePost()
    {
        $this->getPost()->store($this->getFrontMatter(), $this->getPostContent());
    }

}
