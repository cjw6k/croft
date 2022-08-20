<?php

namespace Croft\Micropub;

use A6A\Aether\Aether;
use a6a\a6a\Exception\Redirect;
use a6a\a6a\Post\PostInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Response\ResponseInterface;
use DateTime;

use function is_null;

/**
 * The Micropub\Post class handles post creation
 */
class Post
{
    use Aether;

    /**
     * Store a local reference to a model post
     *
     * @param PostInterface $post The model post.
     * @param RequestInterface $request The current request.
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
     */
    public function createPost(string $client_id): void
    {
        $this->setClientId($client_id);

        if (! $this->_allocate()) {
            return;
        }

        $this->buildPostFrontMatter();
        $this->storePost();

        $this->getResponse()->setCode(201);
        throw new Redirect($this->getPost()->getUid());
    }

    /**
     * Allocate a new post
     *
     * @return bool True If post allocation works.
 * False If post allocation does not work.
     */
    private function _allocate(): bool
    {
        return $this->getPost()->allocate(
            $this->getPublicationDateFromRequest()
        );
    }

    /**
     * Get the publication date of this post from the micropub request
     *
     * Defaults to the current time if no publication date has been provided.
     *
     * @return DateTime The publication date.
     */
    protected function getPublicationDateFromRequest(): DateTime
    {
        return $this->getPublicationDate();
    }

    /**
     * Get the publication date of this post as a DateTime from a string
     *
     * @param string|null $published The publication time.
     *
     * @return DateTime The publication time.
     */
    protected function getPublicationDate(?string $published = null): DateTime
    {
        $dt_published = new DateTime(is_null($published) ? 'now' : $published);
        $this->setPublished($dt_published);

        return $dt_published;
    }

    /**
     * Build the post record front matter
     */
    protected function buildPostFrontMatter(): void
    {
        $front_matter = [
            'client_id' => $this->getClientId(),
            'media_type' => 'text/plain',
            'item' => [
                'type' => [
                    $this->getPostType(),
                ],
                'properties' => [
                    'published' => [
                        $this->getPublished()->format('c'),
                    ],
                    'uid' => [
                        $this->getPost()->getUid(),
                    ],
                ],
            ],
        ];

        if (! is_null($this->getPostSlug())) {
            $front_matter['slug'] = $this->getPostSlug();
        }

        $this->setFrontMatter($front_matter);

        $this->setFrontMatterProperties();
    }

    /**
     * Check if the provided key is a micropub reserved key
     *
     * Reserved keys are not stored in the properties array of the post record.
     *
     * @param string $key The key to check against the list of reserved keys.
     *
     * @return bool True If the key is reserved.
 * False If the key is not reserved.
     */
    protected function reservedPropertyKey(string $key): bool
    {
        switch ($key) {
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
     */
    protected function storePost(): void
    {
        $this->getPost()->store($this->getFrontMatter(), $this->getPostContent());
    }
}
