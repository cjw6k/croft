<?php

use Croft\Croft;

/** @var Croft $croft */
$croft = $this;

require 'header.php';

?>
<p>Nope</p>
<p>The authentication request was missing some stuff that makes it good.</p>
<ul>
<?php

foreach ($croft->getIndieAuth()->getErrors() as $error) {
    ?>
    <li><?= $error ?></li>
    <?php
}

?>
</ul>
<?php

require 'footer.php';
