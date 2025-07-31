<?php
global $options,$current_user;
$dashang_display = isset($options['dashang_display']) ? $options['dashang_display'] : 0;
$show_author = isset($options['show_author']) && $options['show_author']=='0' ? 0 : 1;
$about_author = isset($options['about_author']) && $options['about_author']=='1';
$zan = !isset($options['zan_open']) || $options['zan_open'] == '1';
$video = get_post_meta( $post->ID, 'wpcom_video', true );
$video = $video ?: '';
$content_width = wpcom_get_content_width();
$class = $content_width ? 'main main-' . $content_width : 'main';
$show_indent = isset($options['show_indent']) ? $options['show_indent'] : get_post_meta($post->ID, 'wpcom_show_indent', true);

if( $video!='' && preg_match('/^(http:\/\/|https:\/\/|\/\/).*/i', $video) ){
    $vthumb = get_the_post_thumbnail_url( $post->ID,'large' );
    $video = '<video id="wpcom-video" width="860" preload="none" src="'.$video.'" poster="'.$vthumb.'" playsinline></video>';
}
get_header();?>
    <div class="wrap container<?php echo $video!=='' ? ' has-video' : '';?>">
        <?php if( isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) {
            $breadcrumb_class = $content_width ? 'breadcrumb breadcrumb-' . $content_width : 'breadcrumb';
            wpcom_breadcrumb($breadcrumb_class);
        } ?>
        <main class="<?php echo esc_attr($class);?>">
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-main">
                        <?php
                        if( $video!='' ){ ?>
                            <div class="entry-video">
                                <?php echo do_shortcode($video); ?>
                            </div>
                        <?php } ?>
                        <?php do_action('wpcom_echo_ad', 'ad_single_0');?>
                        <div class="entry-head">
                            <h1 class="entry-title"><?php the_title();?></h1>
                            <div class="entry-info">
                                <?php
                                if($show_author) {
                                    $author = get_the_author_meta( 'ID' );
                                    $author_url = get_author_posts_url( $author );
                                    $author_name = get_the_author();
                                    ?>
                                    <span class="vcard">
                                        <a class="nickname url fn j-user-card" data-user="<?php echo $author;?>" href="<?php echo $author_url; ?>"><?php echo $author_name;?></a>
                                    </span>
                                    <span class="dot">•</span>
                                <?php }
                                if(isset($options['show_origin']) && $options['show_origin'] && $ori_title = get_post_meta($post->ID, 'wpcom_original_name', true)){
                                    $ori_url = get_post_meta($post->ID, 'wpcom_original_url', true);
                                    $ori_html = $ori_url ? '<a href="'.esc_url($ori_url).'" target="_blank" rel="nofollow">'.$ori_title.'</a>' : $ori_title;
                                    $pre_txt = $options['origin_title'] ?: '文章来源: ';?>
                                    <span class="origin"><?php echo $pre_txt . $ori_html;?></span>
                                    <span class="dot">•</span>
                                <?php } ?>
                                <time class="entry-date published" datetime="<?php echo get_post_time( DATE_W3C, false, $post );?>" pubdate>
                                    <?php echo wpcom_format_date(get_post_time( 'U', false, $post ));?>
                                </time>
                                <span class="dot">•</span>
                                <?php the_category( ', ', '', false ); ?>
                                <?php if(function_exists('the_views')) {
                                    $views = (int) get_post_meta( $post->ID, 'views', true );
                                    $views_options = get_option('views_options');
                                    if(is_array($views_options) && function_exists('should_views_be_displayed') && should_views_be_displayed($views_options)){ ?>
                                        <span class="dot">•</span>
                                        <span><?php echo sprintf( __('%s views', 'wpcom'), $views); ?></span>
                                    <?php }} ?>
                            </div>
                        </div>
                        <?php do_action('wpcom_echo_ad', 'ad_single_1');?>

                        <?php if($post->post_excerpt && ((isset($options['post_excerpt']) && $options['post_excerpt'] == '1') || !isset($options['post_excerpt']))){ ?>
                            <div class="entry-excerpt entry-summary">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php } ?>
                        <div class="entry-content<?php echo $show_indent?' text-indent':''?>">
                            <?php the_content();?>
                            <?php wpcom_pagination();?>
                            <?php wpcom_post_copyright();?>
                        </div>

                        <div class="entry-tag"><?php
                        the_tags('', '');
                        if(taxonomy_exists('special')) echo get_the_term_list($post, 'special', '<span class="entry-specials">', '', '</span>');
                        ?></div>
                        <div class="entry-action">
                            <?php if($zan){ ?><div class="btn-zan" data-id="<?php the_ID(); ?>"><?php WPCOM::icon('thumb-up-fill'); ?> <?php _e( 'Like', 'wpcom' );?> <span class="entry-action-num">(<?php $likes = get_post_meta($post->ID, 'wpcom_likes', true); echo $likes?$likes:0;?>)</span></div><?php } ?>

                            <?php if($dashang_display==1 && isset($options['dashang_1_img']) && ($options['dashang_1_img'] || $options['dashang_2_img'])){ ?>
                                <div class="btn-dashang">
                                    <?php WPCOM::icon('cny-circle-fill'); ?> <?php _e('Donate', 'wpcom');?>
                                    <span class="dashang-img<?php if($options['dashang_1_img']&&$options['dashang_2_img']){echo ' dashang-img2';}?>">
                                        <?php if($options['dashang_1_img']){ ?>
                                            <span>
                                                <img src="<?php echo esc_url($options['dashang_1_img'])?>" alt="<?php echo esc_attr($options['dashang_1_title'])?>"/>
                                                    <?php echo $options['dashang_1_title'];?>
                                            </span>
                                        <?php } ?>
                                        <?php if($options['dashang_2_img']){ ?>
                                            <span>
                                                <img src="<?php echo esc_url($options['dashang_2_img'])?>" alt="<?php echo esc_attr($options['dashang_2_title'])?>"/>
                                                    <?php echo $options['dashang_2_title'];?>
                                            </span>
                                        <?php } ?>
                                    </span>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="entry-bar">
                            <div class="entry-bar-inner">
                                <?php if($show_author && !$about_author) { ?>
                                    <div class="entry-bar-author">
                                        <?php
                                        $display_name = get_the_author_meta( 'display_name' );
                                        $display_name = get_avatar( $author, 60, '',  $display_name) . '<span class="author-name">' . $display_name . '</span>';
                                        $display_name = apply_filters('wpcom_user_display_name', $display_name, $author, 'full');
                                        ?>
                                        <a data-user="<?php echo $author;?>" target="_blank" href="<?php echo $author_url; ?>" class="avatar j-user-card">
                                            <?php echo $display_name; ?>
                                        </a>
                                    </div>
                                <?php } ?>
                                <div class="entry-bar-info<?php echo $show_author && !$about_author ? '' : ' entry-bar-info2';?>">
                                    <div class="info-item meta">
                                        <?php if(defined('WPMX_VERSION')){
                                            $u_favorites = get_user_meta($current_user->ID, 'wpcom_favorites', true);
                                            $u_favorites = $u_favorites && is_array($u_favorites) ? $u_favorites : array();
                                            $hearted = in_array(get_the_ID(), $u_favorites); ?>
                                            <a class="meta-item j-heart<?php echo $hearted ? ' hearted' : '';?>" href="javascript:;" data-id="<?php the_ID(); ?>"><?php WPCOM::icon( $hearted ? 'star-fill' : 'star'); ?> <span class="data"><?php $favorites = get_post_meta($post->ID, 'wpcom_favorites', true); echo $favorites ? $favorites:0;?></span></a><?php } ?>
                                        <?php if ( isset($options['comments_open']) && $options['comments_open']=='1' ) { ?><a class="meta-item" href="#comments"><?php WPCOM::icon('comment'); ?> <span class="data"><?php echo get_comments_number();?></span></a><?php } ?>
                                        <?php if($dashang_display==0 && isset($options['dashang_1_img']) && ($options['dashang_1_img'] || $options['dashang_2_img'])){ ?>
                                            <a class="meta-item dashang" href="javascript:;">
                                                <?php WPCOM::icon('cny-circle-fill'); ?> <?php _e('Donate', 'wpcom');?>
                                                <span class="dashang-img<?php if($options['dashang_1_img']&&$options['dashang_2_img']){echo ' dashang-img2';}?>">
                                                    <?php if($options['dashang_1_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_1_img'])?>" alt="<?php echo esc_attr($options['dashang_1_title'])?>"/>
                                                            <?php echo $options['dashang_1_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                    <?php if($options['dashang_2_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_2_img'])?>" alt="<?php echo esc_attr($options['dashang_2_title'])?>"/>
                                                            <?php echo $options['dashang_2_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                </span>
                                            </a>
                                        <?php } ?>
                                    </div>
                                    <div class="info-item share">
                                        <?php if( wpcom_show_poster() ) { ?>
                                            <a class="meta-item mobile j-mobile-share" href="javascript:;" data-id="<?php the_ID();?>" data-qrcode="<?php the_permalink();?>">
                                                <?php WPCOM::icon('share'); ?> <?php _e('Generate poster', 'wpcom');?>
                                            </a>
                                        <?php }

                                        if(isset($options['post_shares'])){ if($options['post_shares']){ foreach ($options['post_shares'] as $share){ ?>
                                            <a class="meta-item <?php echo $share;?>" data-share="<?php echo $share;?>" target="_blank" rel="nofollow noopener noreferrer" href="#">
                                                <?php WPCOM::icon($share); ?>
                                            </a>
                                        <?php } } }else{ ?>
                                            <a class="meta-item wechat" data-share="wechat" href="#"><?php WPCOM::icon('wechat'); ?></a>
                                            <a class="meta-item weibo" data-share="weibo" target="_blank" rel="nofollow noopener noreferrer" href="#"><?php WPCOM::icon('weibo'); ?></a>
                                            <a class="meta-item qq" data-share="qq" target="_blank" rel="nofollow noopener noreferrer" href="#"><?php WPCOM::icon('qq'); ?></a>
                                        <?php } ?>
                                    </div>
                                    <div class="info-item act">
                                        <a href="javascript:;" id="j-reading"><?php WPCOM::icon('article'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if($about_author) get_template_part('templates/entry', 'author');?>
                    <?php get_template_part('templates/entry', 'prev-next');?>
                    <?php do_action('wpcom_echo_ad', 'ad_single_2');?>
                    <?php
                    $type = isset($options['related_show_type']) && $options['related_show_type'] ? $options['related_show_type'] : 'default';
                    if($type=='1') {
                        $type = 'image';
                    } else if($type=='0'){
                        $type = 'list';
                    }
                    $related = wpcom_get_related_post( (isset($options['related_num'])?$options['related_num']:6), ($related_news=$options['related_news'])?$related_news:__('Related posts', 'wpcom'), 'templates/loop-'.$type, 'cols-3 post-loop post-loop-'.$type, $type=='image' || $type=='card');
                    if($related){ ?>
                        <div class="entry-related-posts">
                            <?php echo $related;?>
                        </div>
                    <?php }
                    if ( isset($options['comments_open']) && $options['comments_open']=='1' ) { comments_template(); } ?>
                </article>
            <?php endwhile; ?>
        </main>
        <?php get_sidebar();?>
    </div>
<?php get_footer();?>