<?php

include 'header.php';

?>
<p>Do you want to log in to <?= $this->getIndieAuth()->getClientId() ?> as <strong><?= $this->getIndieAuth()->getMe() ?></strong>?</p>
<p>You will be redirected to <?= $this->getIndieAuth()->getRedirectUri() ?>.</p>
<form action="/auth/" method="POST">
	<input type="hidden" name="state" value="<?= $this->getIndieAuth()->getState() ?>">
	<button type="submit">Continue</button>
</form>
<?php

include 'footer.php';
