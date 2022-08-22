<?php

use Croft\Croft;

/** @var Croft $croft */
$croft = $this;

$mf2ish = $croft->getPost()->getFrontMatter();

if (isset($mf2ish['item']['properties']['visibility'][0])) {
    switch ($mf2ish['item']['properties']['visibility'][0]) {
        case 'private':
        case 'draft':
            if (! $croft->getSession()->isLoggedIn()) {
                $croft->sling404();

                return;
            }
    }
}

require 'header.php';

?>
<article class="<?= implode(' ', $mf2ish['item']['type']) ?>">
    <?php

    if (isset($mf2ish['item']['properties']['name'])) {
        ?>
        <header>
            <h2 class="p-name"><?= $mf2ish['item']['properties']['name'][0] ?></h2>
        </header>
        <?php
    }

    ?>
    <div class="e-content">
        <?= $croft->getPost()->getContent() ?>
    </div>
    <?php

    if (isset($mf2ish['item']['properties']['photo'])) {
        foreach ($mf2ish['item']['properties']['photo'] as $photo) {
            ?>
            <a href="<?= $photo ?>"><img class="u-photo" src="<?= $photo ?>"></a>
            <?php
        }
    }

    ?>
    <footer>
        <?php

        if (isset($mf2ish['item']['properties']['category'])) {
            ?>
            <ul>
            <?php

            foreach ($mf2ish['item']['properties']['category'] as $category) {
                ?>
                <li class="p-category"><?= $category ?></li>
                <?php
            }

            ?>
            </ul>
            <?php
        }

        $dt = new DateTime($mf2ish['item']['properties']['published'][0]);
        $udt = new DateTime();
        $udt->setTimestamp(filemtime($croft->getContentSource()));
        $edt = clone $dt;
        $updated = $edt->modify('+30 seconds') < $udt;

        ?>
        <p>Published: <a class="u-uid" href="<?= $mf2ish['item']['properties']['uid'][0] ?>"><time class="dt-published" datetime="<?= $dt->format('c') ?>"><?= $dt->format('c') ?></time></a><?= $updated
        ? ('&mdash; Updated: <time class="dt-updated" datetime="' . $udt->format('c') . '">' . $udt->format('c') . '</time>')
        : '' ?></p>
    </footer>
</article>
<?php

require 'footer.php';
