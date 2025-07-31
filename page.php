<?php
get_header();
global $options, $post;
$sidebar = wpcom_get_sidebar();
$body_classes = implode(' ', apply_filters( 'body_class', array() ));
$show_indent = isset($options['show_indent']) ? $options['show_indent'] : get_post_meta($post->ID, 'wpcom_show_indent', true);
$hide_title = 0;
if(preg_match('/(qapress|member-profile|member-account|member-login|member-register|member-lostpassword)/i', $body_classes)) {
    $hide_title = 1;
}
if($sidebar && preg_match('/(member-account|member-login|member-register|member-lostpassword)/i', $body_classes)){
    $sidebar = 0;
    update_post_meta($post->ID, 'wpcom_sidebar', '0');
}
$content_width = wpcom_get_content_width();
$class = $content_width ? 'main main-' . $content_width : 'main';
$banner = get_post_meta( $post->ID, 'wpcom_banner', true );
if(!$hide_title && $banner){
    $banner_height = get_post_meta( $post->ID, 'wpcom_banner_height', true );
    $text_color = get_post_meta( $post->ID, 'wpcom_text_color', true );
    $bHeight = intval($banner_height ?: 300);
    $bColor = ($text_color ?: 0) ? ' banner-white' : ' banner-black';
    $description = term_description(); ?>
    <div <?php echo wpcom_lazybg($banner, 'banner'.$bColor, 'height:'.$bHeight.'px;');?>>
        <div class="banner-inner container">
            <h1><?php the_title(); ?></h1>
        </div>
    </div>
<?php } ?>
    <div class="wrap container">
        <?php if( isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) {
            $breadcrumb_class = $content_width ? 'breadcrumb breadcrumb-' . $content_width : 'breadcrumb';
            wpcom_breadcrumb($breadcrumb_class);
        } ?>
        <div class="<?php echo esc_attr($class);?>">
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class();?>>
                    <div class="entry-main">
                        <?php if(!$banner && !$hide_title){ ?>
                            <div class="entry-head">
                                <h1 class="entry-title"><?php the_title();?></h1>
                            </div>
                        <?php } ?>
                        <div class="entry-content<?php echo $show_indent?' text-indent':''?>">
                            <?php the_content();?>
                            <?php wpcom_pagination();?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php get_sidebar();?>
    </div>
<?php get_footer();?>