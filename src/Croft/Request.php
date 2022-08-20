<?php

namespace Croft;

use A6A\Aether\Aether;
use a6a\a6a\Request\RequestInterface;

use function is_null;
use function parse_url;
use function rawurldecode;
use function array_keys;
use function is_array;
use function filter_input;

use const INPUT_GET;
use const FILTER_DEFAULT;
use const FILTER_REQUIRE_ARRAY;
use const INPUT_POST;
use const INPUT_COOKIE;

/**
 * The Request class provides an interface to the current HTTP request
 */
class Request implements RequestInterface
{
    use Aether;

    /**
     * Parse the request URI
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        if ($this->server('REQUEST_URI') == null) {
            return;
        }

        $method = $this->server('REQUEST_METHOD');

        if (! is_null($method)) {
            $this->setMethod($method);
        }

        $uri = $this->server('REQUEST_URI');

        if (is_null($uri)) {
            return;
        }

        $url_parts = parse_url($uri);

        if (isset($url_parts['path'])) {
            $this->setPath(rawurldecode($url_parts['path']));
        }

        if (! isset($url_parts['query'])) {
            return;
        }

        $this->setQuery($url_parts['query']);
    }

    /**
     * Provide data from $_SESSION at specified index
     *
     * If no index is specified, returns the full $_SESSION array.
     *
     * @param string|null $index The index of the data in $_SESSION.
     * @param mixed $data The data to store in $_SESSION at specified index.
     *
     * @return mixed|null The data from $_SESSION or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function session(?string $index = null, mixed $data = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_SESSION ?? []) as $key) {
                $set[$key] = $this->session($key);
            }

            return $set;
        }

        if ($data !== null) {
            $_SESSION[$index] = $data;

            return $data;
        }

        if (! isset($_SESSION[$index])) {
            return null;
        }

        return $_SESSION[$index];
    }

    /**
     * Provide data from $_SERVER at specified index
     *
     * If no index is specified, returns the full $_SERVER array.
     *
     * @param string|null $index The index of the data in $_SERVER.
     *
     * @return mixed|null The data from $_SERVER or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function server(?string $index = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_SERVER) as $key) {
                $set[$key] = $this->server($key);
            }

            return $set;
        }

        if (! isset($_SERVER[$index])) {
            return null;
        }

        return $_SERVER[$index];
    }

    /**
     * Filter and provide data from $_GET at specified index
     *
     * If no index is specified, returns the full $_GET array.
     *
     * @param string|null $index The index of the data in $_GET.
     *
     * @return mixed|null The data from $_GET or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function get(?string $index = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_GET) as $key) {
                $set[$key] = $this->get($key);
            }

            return $set;
        }

        if (! isset($_GET[$index])) {
            return null;
        }

        return is_array($_GET[$index])
            ? filter_input(INPUT_GET, $index, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)
            : filter_input(INPUT_GET, $index);
    }

    /**
     * Filter and provide data from $_POST at specified index
     *
     * If no index is specified, returns the full $_POST array.
     *
     * @param string|null $index The index of the data in $_POST.
     *
     * @return mixed|null The data from $_POST or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function post(?string $index = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_POST) as $key) {
                $set[$key] = $this->post($key);
            }

            return $set;
        }

        if (! isset($_POST[$index])) {
            return null;
        }

        return is_array($_POST[$index])
            ? filter_input(INPUT_POST, $index, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)
            : filter_input(INPUT_POST, $index);
    }

    /**
     * Filter and provide data from $_COOKIE at specified index
     *
     * If no index is specified, returns the full $_COOKIE array.
     *
     * @param string|null $index The index of the data in $_COOKIE.
     *
     * @return mixed|null The data from $_COOKIE or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function cookie(?string $index = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_COOKIE) as $key) {
                $set[$key] = $this->cookie($key);
            }

            return $set;
        }

        if (! isset($_COOKIE[$index])) {
            return null;
        }

        return filter_input(INPUT_COOKIE, $index);
    }

    /**
     * Provide data from $_FILES at specified index
     *
     * If no index is specified, returns the full $_FILES array.
     *
     * @param string|null $index The index of the data in $_FILES.
     *
     * @return mixed|null The data from $_FILES or null if not set.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function files(?string $index = null): mixed
    {
        if (is_null($index)) {
            $set = [];

            foreach (array_keys($_FILES) as $key) {
                $set[$key] = $this->files($key);
            }

            return $set;
        }

        if (! isset($_FILES[$index])) {
            return null;
        }

        return $_FILES[$index];
    }
}
