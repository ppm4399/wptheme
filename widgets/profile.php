<?php
namespace WPCOM\Widgets;

class Profile extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_profile';
        $this->widget_description = '边栏用户信息简介，只在文章详情页显示';
        $this->widget_id = 'profile';
        $this->widget_name = '#作者信息';
        $this->settings = [
            'number' => [
                'value'   => 5,
                'name' => '文章显示数量',
                'd' => '留空则默认显示5篇，设置0或小于0时则不展示用户近期文章'
            ]
        ];
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        global $post, $authordata;
        $num = !isset($instance['number']) || $instance['number'] === '' ? $this->settings['number']['value'] : absint( $instance['number'] );

        if ( is_singular('post') ) :
            if(!$authordata){
                $post = get_post(get_queried_object_id());
                setup_postdata($post);
            }
            $author = get_the_author_meta( 'ID' );
            $author_url = get_author_posts_url( $author );
            $cover_photo = function_exists('wpcom_get_cover_url') ? wpcom_get_cover_url( $author ) : '';
            $this->widget_start( $args, $instance );
            if($cover_photo) {
                echo '<div class="profile-cover">'.wpcom_lazyimg($cover_photo, get_the_author_meta('display_name')).'</div>';
            } else { ?>
                <div class="cover_photo"></div>
            <?php } ?>
            <div class="avatar-wrap">
                <a target="_blank" href="<?php echo $author_url; ?>" class="avatar-link"><?php echo get_avatar( $author, 120, '',  get_the_author());?></a></div>
            <div class="profile-info">
                <a target="_blank" href="<?php echo $author_url; ?>" class="profile-name"><?php echo apply_filters('wpcom_user_display_name', '<span class="author-name">' . get_the_author_meta('display_name') . '</span>', $author, 'full'); ?></a>
                <p class="author-description"><?php the_author_meta('description');?></p>
                <?php do_action('wpcom_profile_after_description', $author);?>
            </div>
            <?php if($num > 0){ ?>
            <div class="profile-posts">
                <h3 class="widget-title"><span><?php _e('Recent Posts', 'wpcom');?></span></h3>
                <?php
                global $post;
                $posts = get_posts( 'author='.$author.'&posts_per_page='.$num );
                if ($posts) : echo '<ul>'; foreach ( $posts as $post ) { setup_postdata( $post ); ?>
                    <li><a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>"><?php the_title();?></a></li>
                <?php } echo '</ul>'; else :?>
                    <p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;"><?php _e('No Posts', 'wpcom');?></p>
                <?php endif; wp_reset_postdata(); ?>
            </div>
            <?php } ?>
            <?php $this->widget_end( $args );
        endif;
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( Profile::class );
} );