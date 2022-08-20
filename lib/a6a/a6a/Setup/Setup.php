<?php

namespace a6a\a6a\Setup;

use A6A\Aether\Aether;
use League\CLImate\CLImate;

/**
 * @see Aether
 * @method string getUrl()
 */
interface Setup
{
    /**
     * Configure WebFoo using parameters provided on the command line
     *
     * @return int The return code.
     */
    public function configure(CLImate $cli): int;
}
