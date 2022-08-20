<?php

namespace a6a\a6a\Request;

use a6a\a6a\Service\ServiceInterface;

/**
 * The Request service interface
 */
interface RequestInterface extends ServiceInterface
{
    /**
     * Parse the request URI
     */
    public function __construct();

    /**
     * Provide data from or set data into $_SESSION at specified index
     *
     * If no index is specified, returns the full $_SESSION array.
     *
     * @param string|null $index The index of the data in $_SESSION.
     * @param mixed $data The data to store in $_SESSION at specified index.
     *
     * @return mixed|null The data from $_SESSION or null if not set.
     */
    public function session(?string $index = null, mixed $data = null): mixed;

    /**
     * Provide data from $_SERVER at specified index
     *
     * If no index is specified, returns the full $_SERVER array.
     *
     * @param string|null $index The index of the data in $_SERVER.
     *
     * @return mixed|null The data from $_SERVER or null if not set.
     */
    public function server(?string $index = null): mixed;

    /**
     * Provide data from $_GET at specified index
     *
     * If no index is specified, returns the full $_GET array.
     *
     * @param string|null $index The index of the data in $_GET.
     *
     * @return mixed|null The data from $_GET or null if not set.
     */
    public function get(?string $index = null): mixed;

    /**
     * Provide data from $_POST at specified index
     *
     * If no index is specified, returns the full $_POST array.
     *
     * @param string|null $index The index of the data in $_POST.
     *
     * @return mixed|null The data from $_POST or null if not set.
     */
    public function post(?string $index = null): mixed;

    /**
     * Provide data from $_COOKIE at specified index
     *
     * If no index is specified, returns the full $_COOKIE array.
     *
     * @param string|null $index The index of the data in $_COOKIE.
     *
     * @return mixed|null The data from $_COOKIE or null if not set.
     */
    public function cookie(?string $index = null): mixed;

    /**
     * Provide data from $_FILES at specified index
     *
     * If no index is specified, returns the full $_FILES array.
     *
     * @param string|null $index The index of the data in $_FILES.
     *
     * @return mixed|null The data from $_FILES or null if not set.
     */
    public function files(?string $index = null): mixed;
}
