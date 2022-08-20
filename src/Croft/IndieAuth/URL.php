<?php

namespace Croft\IndieAuth;

use A6A\Aether\Aether;
use a6a\a6a\Config\ConfigInterface;

use function filter_var;

use const FILTER_VALIDATE_URL;

use function parse_url;
use function strtolower;
use function in_array;
use function trim;
use function strpos;

use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_IP;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;

/**
 * The URL class provides data validation methods to match the IndieAuth spec's URL treatments
 */
class URL
{
    use Aether;

    /**
     * Accept the Config and make it local expressly to permit exceptions to the indieauth spec.
     *
     * @param ConfigInterface $config The active configuration.
     */
    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    /**
     * Ensure the URL has an acceptable format according to the spec.
     *
     * @param string $url The URL to validate.
     * @param string $name The name of the parameter for use in error messages.
     *
     * @return bool True If the URL is acceptable.
 * False If the URL is not acceptable.
     */
    public function common(string $url, string $name): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->mergeErrors($name . ' must be a URL');

            return false;
        }

        $url_parts = parse_url($url);

        if (
            ! $url_parts
            || ! isset($url_parts['scheme'])
            || ! isset($url_parts['host'])
        ) {
            $this->mergeErrors($name . ' must be a URL');

            return false;
        }

        if (
            strtolower($url_parts['scheme']) != 'https'
            && strtolower($url_parts['scheme']) != 'http'
        ) {
            $this->mergeErrors($name . ' must use HTTP or HTTPS');

            return false;
        }

        return true;
    }

    /**
     * Ensure the provided URL matches the simple URL requirements of the spec
     *
     * @param string $url The URL to validate.
     * @param string $name The name of the parameter for use in error messages.
     * @param bool $allow_loopback Allow loopback IP addresses in domain.
     *
     * @return bool True The URL is valid according to the spec.
 * False The URL is not valid according to the spec.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function simple(string $url, string $name, bool $allow_loopback = true): bool
    {
        $url_parts = parse_url($url);

        if (isset($url_parts['fragment'])) {
            $this->mergeErrors("$name must not contain a fragment");
        }

        if (isset($url_parts['user']) || isset($url_parts['pass'])) {
            $this->mergeErrors("$name must not contain a username or password");
        }

        if (! $this->domain($url_parts['host'] ?? '', $name, $allow_loopback)) {
            return false;
        }

        $this->path($url_parts, $name);

        if ($this->hasErrors()) {
            return false;
        }

        return true;
    }

    /**
     * Ensure the path part of a simple URL is acceptable according to the spec.
     *
     * @param mixed $url_parts The URL parts from parse_url.
     * @param string $name The name of the parameter for use in error messages.
     */
    private function path(mixed $url_parts, string $name): void
    {
        if (! isset($url_parts['path'])) {
            $config_indieauth = $this->getConfig()->getIndieauth();

            if (isset($config_indieauth['exceptions']['client_id']['missing_path_component'])) {
                if (in_array($url_parts['host'], $config_indieauth['exceptions']['client_id']['missing_path_component'])) {
                    return;
                }
            }

            $this->mergeErrors("$name must include a path");

            return;
        }

        $path = '/' . trim($url_parts['path'], '/') . '/';

        if (
            strpos($path, '/./') === false
            && strpos($path, '/../') === false
        ) {
            return;
        }

        $this->mergeErrors("$name must not include relative components in the path");
    }

    /**
     * Ensure the domain part of a simple URL is acceptable according to the spec.
     *
     * @param string $host The host part of the URL.
     * @param string $name The name of the parameter for use in error messages.
     * @param bool $allow_loopback Allow loopback IP addresses in domain.
     *
     * @return bool True If the domain is acceptable.
 * False If the domain is not acceptable.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function domain(string $host, string $name, bool $allow_loopback = true): bool
    {
        if (filter_var($host, FILTER_VALIDATE_DOMAIN) === false) {
            $this->mergeErrors("$name must have a valid domain name");
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            if (! $allow_loopback || $host != '127.0.0.1') {
                $this->mergeErrors("$name must not be an IPV4 address");
            }
        }

        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            if (! $allow_loopback || $host != '[::1]') {
                $this->mergeErrors("$name must not be an IPV6 address");
            }
        }

        if ($this->hasErrors()) {
            return false;
        }

        return true;
    }
}
