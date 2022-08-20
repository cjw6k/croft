<?php

require 'header.php';

?>
<p>Do you want to log in to <?= $this->getIndieAuth()->getClientId() ?> as <strong><?= $this->getIndieAuth()->getMe() ?></strong>?</p>
<p>You will be redirected to <?= $this->getIndieAuth()->getRedirectUri() ?>.</p>
<form action="/auth/" method="POST">
    <input type="hidden" name="ssrv" value="<?= $this->getSession()->getSSRV() ?>">
    <input type="hidden" name="state" value="<?= $this->getIndieAuth()->getState() ?>">
    <input type="hidden" name="client_id" value="<?= $this->getIndieAuth()->getClientId() ?>">
    <input type="hidden" name="redirect_uri" value="<?= $this->getIndieAuth()->getRedirectUri() ?>">
    <?php

    if ($this->getIndieAuth()->getResponseType() == 'code') {
        ?>
        <p>The client requests the following access:</p>
        <ul>
            <?php

            foreach ($this->getIndieAuth()->getScopes() as $idx => $scope) {
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
