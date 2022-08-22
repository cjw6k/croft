<?php

namespace Croft\Webmention;

use A6A\Aether\Aether;
use a6a\a6a\Config\Config;
use a6a\a6a\Request\Request;

use function parse_url;
use function is_null;

/**
 * The Validation class provides data validation methods to match the Webmention spec
 */
class Validation
{
    use Aether;

    /**
     * Store a local reference to the current request.
     *
     * @param Config $config The active configuration.
     * @param Request $request The current request.
     */
    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->setRequest($request);
    }

    /**
     * Ensure the webmention request matches requirements of the spec
     *
     * @return bool True If the request meets requirements.
 * False If the request does not meet requirements.
     */
    public function request(): bool
    {
        if (! $this->hasRequiredParams()) {
            return false;
        }

        if (! $this->hasValidUrls()) {
            return false;
        }

        if ($this->getTarget() == $this->getSource()) {
            $this->setResponseBody('Error: the target URL and source URL must not be the same');

            return false;
        }

        $url_parts = parse_url($this->getConfig()->getMe());

        if (
            ! isset($url_parts['host'])
            || $url_parts['host'] != $this->getTargetParts()['host']
        ) {
            $this->setResponseBody('Error: the target URL is not valid at this domain');

            return false;
        }

        return true;
    }

    /**
     * Ensure the request has the required target and source parameters
     *
     * @return bool True If the request has required parameters.
 * False If the request does not have required parameters.
     */
    private function hasRequiredParams(): bool
    {
        $this->setTarget($this->getRequest()->post('target'));
        $this->setSource($this->getRequest()->post('source'));

        if (! $this->getTarget()) {
            if (is_null($this->getTarget())) {
                $this->setResponseBody('Error: target parameter is required');

                return false;
            }

            $this->setResponseBody('Error: target parameter must not be empty');

            return false;
        }

        if (! $this->getSource()) {
            if (is_null($this->getSource())) {
                $this->setResponseBody('Error: source parameter is required');

                return false;
            }

            $this->setResponseBody('Error: source parameter must not be empty');

            return false;
        }

        return true;
    }

    /**
     * Ensure the target and source parameters are valid URLs
     *
     * @return bool True If the request parameters are valid URLs.
 * False If the request parameters are not valid URLs.
     */
    private function hasValidUrls(): bool
    {
        if (! $this->hasValidTargetUrl()) {
            $this->setResponseBody('Error: the target URL is invalid');

            return false;
        }

        if (! $this->hasValidSourceUrl()) {
            $this->setResponseBody('Error: the source URL is invalid');

            return false;
        }

        return true;
    }

    /**
     * Ensure the target parameter is a valid URL
     *
     * @return bool True If the target parameter is a valid URL.
 * False If the target parameter is not a valid URL.
     */
    private function hasValidTargetUrl(): bool
    {
        $this->setTargetParts(parse_url($this->getTarget()));

        if (! $this->getTargetParts()) {
            return false;
        }

        $target_parts = $this->getTargetParts();

        if (! isset($target_parts['scheme'])) {
            return false;
        }

        return $target_parts['scheme'] == 'http'
            || $target_parts['scheme'] == 'https';
    }

    /**
     * Ensure the source parameter is a valid URL
     *
     * @return bool True If the source parameter is a valid URL.
 * False If the source parameter is not a valid URL.
     */
    private function hasValidSourceUrl(): bool
    {
        $this->setSourceParts(parse_url($this->getSource()));

        if (! $this->getSourceParts()) {
            return false;
        }

        $source_parts = $this->getSourceParts();

        if (! isset($source_parts['scheme'])) {
            return false;
        }

        return $source_parts['scheme'] == 'http'
            || $source_parts['scheme'] == 'https';
    }
}
