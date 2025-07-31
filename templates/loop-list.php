<li class="item">
    <a href="<?php echo esc_url( get_permalink() );?>"<?php echo wpcom_post_target();?> rel="bookmark">
        <span><?php the_title();?></span>
    </a>
    <span class="date"><?php the_time(get_option('date_format'));?></span>
</li>