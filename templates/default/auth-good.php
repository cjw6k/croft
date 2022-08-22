<?php

use Croft\Croft;

/** @var Croft $croft */
$croft = $this;

require 'header.php';

?>
<p>Do you want to log in to <?= $croft->getIndieAuth()->getClientId() ?> as <strong><?= $croft->getIndieAuth()->getMe() ?></strong>?</p>
<p>You will be redirected to <?= $croft->getIndieAuth()->getRedirectUri() ?>.</p>
<form action="/auth/" method="POST">
    <input type="hidden" name="ssrv" value="<?= $croft->getSession()->getSSRV() ?>">
    <input type="hidden" name="state" value="<?= $croft->getIndieAuth()->getState() ?>">
    <input type="hidden" name="client_id" value="<?= $croft->getIndieAuth()->getClientId() ?>">
    <input type="hidden" name="redirect_uri" value="<?= $croft->getIndieAuth()->getRedirectUri() ?>">
    <?php

    if ($croft->getIndieAuth()->getResponseType() == 'code') {
        ?>
        <p>The client requests the following access:</p>
        <ul>
            <?php

            foreach ($croft->getIndieAuth()->getScopes() as $idx => $scope) {
                ?>
                <li><label for="scope_<?= $idx ?>"><input id="scope_<?= $idx ?>" type="checkbox" name="scopes[]" checked="checked" value="<?= $scope ?>"><?= $scope ?></label></li>
                <?php
            }

            ?>
        </ul>
        <?php
    }

    ?>
    <button type="submit">Continue</button>
</form>
<?php

require 'footer.php';
