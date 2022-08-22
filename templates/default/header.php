<?php

use Croft\Croft;

/** @var Croft $croft */
$croft = $this;

?>
<!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="utf-8">
    <title><?= $croft->getConfig()->getTitle() ?></title>
    <link rel="authorization_endpoint" href="/auth/">
    <link rel="token_endpoint" href="/token/">
    <link rel="micropub" href="/micropub/">
    <link rel="webmention" href="/webmention/">
</head>
<body>
