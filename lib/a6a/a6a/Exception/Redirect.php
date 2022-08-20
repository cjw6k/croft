<?php

namespace a6a\a6a\Exception;

use Exception;

/**
 * The Redirect is an exceptional circumstance where the browser is redirected and execution stops
 */
class Redirect extends Exception implements ExceptionInterface
{
}
