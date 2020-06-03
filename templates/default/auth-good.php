<?php

include 'header.php';

?>
<p>Do you want to log in to <?= $this->getIndieAuth()->getClientId() ?> as <strong><?= $this->getIndieAuth()->getMe() ?></strong>?</p>
<p>You will be redirected to <?= $this->getIndieAuth()->getRedirectUri() ?>.</p>
<form action="/auth/" method="POST">
	<input type="hidden" name="ssrv" value="<?= $this->getSession()->getSSRV() ?>">
	<input type="hidden" name="state" value="<?= $this->getIndieAuth()->getState() ?>">
	<input type="hidden" name="client_id" value="<?= $this->getIndieAuth()->getClientId() ?>">
	<input type="hidden" name="redirect_uri" value="<?= $this->getIndieAuth()->getRedirectUri() ?>">
	<button type="submit">Continue</button>
</form>
<?php

include 'footer.php';
