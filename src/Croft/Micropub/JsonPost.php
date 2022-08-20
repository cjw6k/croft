<?php

namespace Croft\Micropub;

use DateTime;

use function json_decode;
use function file_get_contents;
use function obj2arr;
use function is_object;
use function is_string;

/**
 * The Micropub\Json class handles post creationg for JSON-encoded posts
 */
class JsonPost extends Post
{
    /**
     * Create a new post
     *
     * @param string $client_id The client_id of the posting micropub client.
     */
    public function createPost(string $client_id): void
    {
        $this->setMf2(json_decode(file_get_contents('php://input')));

        parent::createPost($client_id);
    }

    /**
     * Get the publication date of this post from the micropub request.
     *
     * Defaults to the current time if no publication date has been provided.
     *
     * @return DateTime The publication date.
     */
    protected function _getPublicationDateFromRequest(): DateTime
    {
        $published = null;

        if (isset($this->getMf2()->properties->published[0])) {
            $published = $this->getMf2()->properties->published[0];
        }

        return $this->_getPublicationDate($published);
    }

    /**
     * Build the post record front matter from POST parameters
     */
    protected function _setFrontMatter(): void
    {
        $this->setPostType($this->getMf2()->type[0] ?? 'h-entry');

        $post_slug = null;

        if (isset($this->getMf2()->properties->slug[0])) {
            $post_slug = $this->getMf2()->properties->slug[0];
        }

        $this->setPostSlug($post_slug);

        parent::_setFrontMatter();
    }

    /**
     * Capture optional front matter properties from the JSON
     */
    protected function _setFrontMatterProperties(): void
    {
        $front_matter = $this->getFrontMatter();

        foreach ($this->getMf2()->properties as $key => $set) {
            if ($this->_reservedPropertyKey($key)) {
                continue;
            }

            if (empty($set)) {
                continue;
            }

            $front_matter['item']['properties'][$key] = obj2arr($set);
        }

        $this->setFrontMatter($front_matter);
    }

    /**
     * Store the post front matter and content into a post record on disk_free_space
     */
    protected function _storePost(): void
    {
        $front_matter = $this->getFrontMatter();
        $content = $this->getMf2()->properties->content[0];

        if (is_object($this->getMf2()->properties->content[0])) {
            if (isset($this->getMf2()->properties->content[0]->html)) {
                $front_matter['media_type'] = 'text/html';
                $content = $this->getMf2()->properties->content[0]->html;
            }
        }

        if (! is_string($content)) {
            $content = '';
        }

        $this->setFrontMatter($front_matter);
        $this->setPostContent($content);

        parent::_storePost();
    }
}
