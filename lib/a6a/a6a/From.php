<?php

namespace a6a\a6a;

use A6a\FromDir\AsDirectoryInPackageRootWithBackedEnum;
use A6a\FromDir\NamesDirectoryInPackageRoot;

enum From: string implements NamesDirectoryInPackageRoot
{
    use asDirectoryInPackageRootWithBackedEnum;

    case ___ = '___';
    case ART = 'art';
    case BOOTSTRAP = 'bootstrap';
    case CONFIG = 'config';
    case CONTENT = 'content';
    case SRC = 'src';
    case TEMPLATES___DEFAULT = 'templates___default';
    case TEMPLATES___LOCAL = 'templates___local';
    case VAR = 'var';
}
