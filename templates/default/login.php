<?php

require 'header.php';

?>
<a href="/">home</a>
<?php

if ($this->getSession()->hasErrors()) {
    ?>
    <p>Log In Error</p>
    <ul>
    <?php

    foreach ($this->getSession()->getErrors() as $error) {
        if (! is_string($error)) {
            continue;
        }

        ?>
        <li><?= $error ?></li>
        <?php
    }

    ?>
    </ul>
    <?php
}

/** @var string|false|null $url */
$url = filter_input(INPUT_SERVER, 'REQUEST_URI');

if (! is_string($url)) {
    $url = 'SELF';
}

?>
<form action="<?= $url ?>" method="POST">
    Username <input type="text" name="username">
    Password <input type="password" name="userkey">
    <button type="submit">Log In</button>
</form>
<?php

require 'footer.php';
