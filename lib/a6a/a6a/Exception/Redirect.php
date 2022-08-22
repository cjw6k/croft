<?php

namespace a6a\a6a\Exception;

use Exception as GlobalException;

/**
 * The Redirect is an exceptional circumstance where the browser is redirected and execution stops
 */
class Redirect extends GlobalException implements Exception
{
}
