<form class="search-form" action="<?php echo esc_url( home_url( '/' ) );?>" method="get" role="search">
    <input type="search" class="keyword" name="s" maxlength="100" placeholder="<?php _e('Type your search here ...', 'wpcom');?>" value="<?php echo get_search_query(); ?>">
    <button type="submit" class="submit"><?php WPCOM::icon('search');?></button>
</form>