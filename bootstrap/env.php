<?php

use Croft\From;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(From::___->dir());
$dotenv->safeLoad();
