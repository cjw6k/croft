<?php

include 'header.php';

?>
<p>Nope</p>
<p>The authentication request was missing some stuff that makes it good.</p>
<ul>
<?php

foreach($this->getIndieAuth()->getErrors() as $error){

	?>
	<li><?= $error ?></li>
	<?php

}

?>
</ul>
<?php

include 'footer.php';
