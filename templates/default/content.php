<?php

include 'header.php';

$mf2ish = $this->getFrontMatter();

?>
<article class="<?= implode(' ', $mf2ish['item']['type']) ?>">
	<?php

	if(isset($mf2ish['item']['properties']['name'])){

		?>
		<header>
			<h2><?= $mf2ish['item']['properties']['name'][0] ?></h2>
		</header>
		<?php

	}

	?>
	<div class="e-content">
		<?= $this->getContent() ?>
	</div>
	<footer>
		<?php

		if(isset($mf2ish['item']['properties']['category'])){

			?>
			<ul>
			<?php

			foreach($mf2ish['item']['properties']['category'] as $category){

				?>
				<li class="p-category"><?= $category ?></li>
				<?php

			}

			?>
			</ul>
			<?php

		}

		?>
	</footer>
</article>
<?php

include 'footer.php';
