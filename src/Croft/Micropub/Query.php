<?php

namespace Croft\Micropub;

use A6A\Aether\Aether;
use a6a\a6a\Request\Request;
use a6a\a6a\Response\Response;
use Croft\From;

use function preg_match;
use function yaml_parse;
use function trim;
use function substr;
use function strlen;
use function parse_url;
use function file_exists;
use function file_get_contents;
use function is_array;

/**
 * The Micropub\Query class handles the GET queries of Micropub
 */
class Query
{
    use Aether;

    /**
     * Store a local reference to the current request.
     *
     * @param Request $request The current request.
     * @param Response $response The response.
     */
    public function __construct(Request $request, Response $response)
    {
        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Handle a GET request
     */
    public function handleRequest(): void
    {
        switch ($this->getRequest()->get('q')) {
            case 'config':
                $this->configQuery();

                return;

            case 'source':
                $this->sourceQuery();

                return;
        }
    }

    /**
     * Respond to a configuration query
     */
    private function configQuery(): void
    {
        $this->setResponseBody(['syndicate-to' => [], 'media-endpoint' => '']);
    }

    /**
     * Respond to a source query
     */
    private function sourceQuery(): void
    {
        if (! $this->checkSourceQueryUrlContent()) {
            return;
        }

        $yaml = [];

        if (! preg_match('/^(?m)(---$.*^...)$/Us', $this->getContent(), $yaml)) {
            $this->getResponse()->setCode(500);
            $this->setResponseBody(
                [
                    'error' => 'broken',
                    'error_description' => "the server encountered an unspecified internal error and could not complete the request",
                ]
            );

            return;
        }

        $this->setFrontMatter(yaml_parse($yaml[1]));
        $this->setContent(trim(substr($this->getContent(), strlen($yaml[1]))));

        $this->fillSourceQueryResponseProperties();
    }

    /**
     * Ensure the source content query has a valid URL and matches available content
     *
     * @return bool True If the source query has a valid URL.
 * False If the source query does not have a valid URL.
     */
    private function checkSourceQueryUrlContent(): bool
    {
        if (! $this->getRequest()->get('url')) {
            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'the source content query must include a post URL',
                ]
            );

            return false;
        }

        $url_parts = parse_url($this->getRequest()->get('url'));

        if (! isset($url_parts['path'])) {
            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'there is no post at the requested URL',
                ]
            );

            return false;
        }

        $matches = [];

        if (! preg_match('/^\/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)/', $url_parts['path'], $matches)) {
            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'there is no post at the requested URL',
                    'url' => $this->getRequest()->get('url'),
                ]
            );

            return false;
        }

        if (! file_exists(From::CONTENT->dir() . $matches[1] . 'web.foo')) {
            $this->getResponse()->setCode(400);
            $this->setResponseBody(
                [
                    'error' => 'invalid_request',
                    'error_description' => 'there is no post at the requested URL',
                ]
            );

            return false;
        }

        $this->setContent(file_get_contents(From::CONTENT->dir() . $matches[1] . 'web.foo'));

        return true;
    }

    /**
     * Copy the requested properties into the response object
     */
    private function fillSourceQueryResponseProperties(): void
    {
        $front_matter = $this->getFrontMatter();

        if (! $this->getRequest()->get('properties')) {
            $response = [
                'type' => $front_matter['item']['type'],
                'properties' => $front_matter['item']['properties'],
            ];

            $response['properties']['content'][] = $this->getContent();

            $this->setResponseBody($response);

            return;
        }

        $response = [];

        if (is_array($this->getRequest()->get('properties'))) {
            foreach ($this->getRequest()->get('properties') as $property) {
                switch ($property) {
                    case 'content':
                        $response['properties']['content'][] = $this->getContent();
                        continue 2;

                    case 'type':
                        $response['type'] = $front_matter['item']['type'];
                        continue 2;
                }

                if (! isset($front_matter['item']['properties'][$property])) {
                    continue;
                }

                $response['properties'][$property] = $front_matter['item']['properties'][$property];
            }

            $this->setResponseBody($response);

            return;
        }

        if (isset($front_matter['item']['properties'][$this->getRequest()->get('properties')])) {
            $response['properties'][$this->getRequest()->get('properties')] = $front_matter['item']['properties'][$this->getRequest()->get('properties')];
        }

        $this->setResponseBody($response);
    }
}
