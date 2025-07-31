<?php
global $options;
$author = get_the_author_meta( 'ID' );
$author_url = get_author_posts_url( $author );
$display_name = get_the_author_meta( 'display_name' );
$show_description = !function_exists('wpmx_description_length') || wpmx_description_length() > 0;
$description = $show_description ? get_the_author_meta( 'user_description' ) : '';
$about_author_title = isset($options['about_author_title']) && trim($options['about_author_title']) !== '' ? $options['about_author_title'] : __('About Author', 'wpcom');
?>
<div class="entry-author">
    <?php if($about_author_title != '0'){ ?><h3 class="entry-author-title"><?php echo $about_author_title;?></h3><?php } ?>
    <div class="entry-author-inner">
        <div class="entry-author-avatar">
            <a class="avatar j-user-card" href="<?php echo esc_url($author_url);?>" target="_blank" data-user="<?php echo $author;?>"><?php echo get_avatar( $author, 120, '',  $display_name);?></a>
        </div>
        <div class="entry-author-content">
            <div class="entry-author-info">
                <h4 class="entry-author-name">
                    <a class="j-user-card" href="<?php echo esc_url($author_url);?>" target="_blank" data-user="<?php echo $author;?>"><?php echo apply_filters('wpcom_user_display_name', $display_name, $author, 'full');?></a>
                </h4>
                <div class="entry-author-action">
                    <?php do_action('wpcom_follow_item_action', $author);?>
                </div>
            </div>
            <?php if(defined('WPMX_VERSION') && apply_filters( 'wpcom_member_show_profile' , true )){ ?>
                <div class="entry-author-stats"><?php do_action('wpcom_user_data_stats', $author, false);?></div>
            <?php } ?>
            <?php if($description !== '') { ?><div class="entry-author-description"><?php echo wp_kses($description, 'user_description');?></div><?php } ?>
        </div>
    </div>
</div>