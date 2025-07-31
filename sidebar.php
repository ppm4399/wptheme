<?php
$sidebar = wpcom_get_sidebar();
if($sidebar) { ?>
    <aside class="<?php wpcom_sidebar_class();?>">
        <?php dynamic_sidebar($sidebar); ?>
    </aside>
<?php } ?>