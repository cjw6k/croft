<?php

include 'header.php';

?>
<a href="/">home</a>
<?php

if($this->getSession()->hasErrors()){

	?>
	<p>Log In Error</p>
	<ul>
	<?php

	foreach($this->getSession()->getErrors() as $error){

		?>
		<li><?= $error ?></li>
		<?php

	}

	?>
	</ul>
	<?php

}

?>
<form action="<?= filter_input(INPUT_SERVER, 'REQUEST_URI') ?>" method="POST">
	Username <input type="text" name="username">
	Password <input type="password" name="userkey">
	<button type="submit">Log In</button>
</form>
<?php

include 'footer.php';
