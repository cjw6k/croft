<?php

namespace Croft\Micropub;

use DateTime;

use function is_array;
use function array_merge;
use function array_keys;
use function is_uploaded_file;
use function mkdir;
use function pathinfo;
use function move_uploaded_file;
use function is_null;

use const PATHINFO_EXTENSION;
use const UPLOAD_ERR_OK;

/**
 * The Micropub\Form class handles post creation for www-form-url-encoded and multipart/form-data
 */
class FormPost extends Post
{
    /**
     * Get the publication date of this post from the micropub request.
     *
     * Defaults to the current time if no publication date has been provided.
     *
     * @return DateTime The publication date.
     */
    protected function getPublicationDateFromRequest(): DateTime
    {
        return $this->getPublicationDate($this->getRequest()->post('published'));
    }

    /**
     * Build the post record front matter from POST parameters
     */
    protected function buildPostFrontMatter(): void
    {
        $this->setPostType('h-' . ($this->getRequest()->post('h') ?: 'entry'));
        $this->setPostSlug($this->getRequest()->post('slug'));

        parent::buildPostFrontMatter();

        $this->embeddedMedia();
    }

    /**
     * Capture optional frontPos matter properties from the POST parameters
     */
    protected function setFrontMatterProperties(): void
    {
        $front_matter = $this->getFrontMatter();

        foreach ($this->getRequest()->post() as $key => $value) {
            if ($this->reservedPropertyKey($key)) {
                continue;
            }

            $this->setFrontMatterProperty($front_matter, $key, $value);
        }

        $this->setFrontMatter($front_matter);
    }

    /**
     * Capture one front matter property
     *
     * @param mixed $front_matter The front matter of the post.
     * @param string $key The index provided in POST.
     * @param mixed $value The value of POST at the given index.
     */
    protected function setFrontMatterProperty(mixed &$front_matter, string $key, mixed $value): void
    {
        if (is_array($value)) {
            $array_is_empty = true;

            foreach ($value as $one_of) {
                if (! empty($one_of)) {
                    $array_is_empty = false;

                    break;
                }
            }

            if ($array_is_empty) {
                return;
            }

            if (! isset($front_matter['item']['properties'][$key])) {
                $front_matter['item']['properties'][$key] = [];
            }

            $front_matter['item']['properties'][$key] = array_merge($front_matter['item']['properties'][$key], $value);

            return;
        }

        if (empty($value)) {
            return;
        }

        $front_matter['item']['properties'][$key][] = $value;
    }

    /**
     * Capture the embedded media in a post
     */
    private function embeddedMedia(): void
    {
        $files = $this->getRequest()->files();

        if (! $files) {
            return;
        }

        foreach ($files as $name => $set) {
            switch ($name) {
                case 'photo':
                case 'video':
                case 'audio':
                    break;

                default:
                    continue 2;
            }

            if (is_array($set['error'])) {
                $this->embeddedMediaHelper($name, $set);
                continue;
            }

            $this->storeMedia($name, $set);
        }
    }

    private function embeddedMediaHelper(string $name, mixed $set): void
    {
        foreach (array_keys($set['error']) as $key) {
            $this->storeMedia(
                $name,
                [
                    'error' => $set['error'][$key],
                    'name' => $set['name'][$key],
                    'tmp_name' => $set['tmp_name'][$key],
                    'size' => $set['size'][$key],
                ]
            );
        }
    }

    /**
     * Store one media item from the post locally
     *
     * @param string $name The property name of the media item.
     * @param mixed $file The relevant parameters from $_FILE.
     *
     * @return void
     *
     * This will suppress UndefinedVariable warnings. It is necessary because PHPMD misses that the
     * variable $counters has been defined with the static keyword at the top of this method.
     *
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     */
    private function storeMedia(string $name, mixed $file): void
    {
        static $counters = [
            'photo' => 1,
            'video' => 1,
            'audio' => 1,
        ];

        static $media_folder_made = false;

        if ($file['error'] != UPLOAD_ERR_OK) {
            return;
        }

        if (! is_uploaded_file($file['tmp_name'])) {
            return;
        }

        if (
            ! $media_folder_made
            && ! mkdir($this->getPost()->getContentPath() . $this->getPost()->getContentId() . '/media/', 0755, true)
        ) {
            return;
        }

        $media_folder_made = true;

        $this->storeMediaHelper($name, $counters[$name], $file);

        $counters[$name]++;
    }

    /** @param mixed $file The relevant parameters from $_FILE. */
    private function storeMediaHelper(string $name, string $counterName, mixed $file): void
    {
        $destination_file = $name . $counterName . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

        move_uploaded_file(
            $file['tmp_name'],
            $this->getPost()->getContentPath() . $this->getPost()->getContentId()
            . '/media/' . $destination_file
        );

        $frontMatter = $this->getFrontMatter();
        $frontMatter['item']['properties'][$name][] = $this->getPost()->getUid() . 'media/' . $destination_file;

        $this->setFrontMatter($frontMatter);
    }

    /**
     * Store the post front matter and content into a post record on disk
     */
    protected function storePost(): void
    {
        $content = $this->getRequest()->post('content');

        if (is_null($content)) {
            $content = '';
        }

        $this->setPostContent($content);

        parent::storePost();
    }
}
