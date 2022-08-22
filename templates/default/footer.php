<?php

use Croft\Croft;

/** @var Croft $croft */
$croft = $this;

/*
 * Ensure this call is in the footer of the local template or the controls will be missing
 * even while logged in.
 */
$croft->webfooControls();

?>
</body>
</html>
