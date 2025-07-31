<?php defined( 'ABSPATH' ) || exit;

add_action('admin_init', 'wpcom_gutenberg_blocks');
function wpcom_gutenberg_blocks() {
    wp_register_script('wpcom-blocks', FRAMEWORK_URI . '/assets/js/blocks.js', ['wp-blocks', 'wp-element', 'wp-components'], FRAMEWORK_VERSION, true);
    wp_register_style('wpcom-blocks', FRAMEWORK_URI . '/assets/css/blocks.css', ['wp-edit-blocks'], FRAMEWORK_VERSION);
    wp_localize_script('wpcom-blocks', '_wpcom_blocks', apply_filters('wpcom_blocks_script', ['exclude' => []]));

    register_block_type('wpcom/blocks', [
        'editor_script' => 'wpcom-blocks',
        'editor_style' => 'wpcom-blocks'
    ]);

    add_filter('block_categories_all', 'wpcom_gutenberg_block_categories', 5);
}

function wpcom_gutenberg_block_categories($categories) {
    return array_merge(
        $categories,
        [
            [
                'slug' => 'wpcom',
                'title' => __('WPCOM扩展区块', 'wpcom')
            ]
        ]
    );
}

add_filter('wpcom_blocks_script', 'wpcom_block_exclude_for_widget');
function wpcom_block_exclude_for_widget($blocks) {
    wp_enqueue_script('lodash');
    $blocks['exclude_widgets'] = isset($blocks['exclude_widgets']) ? $blocks['exclude_widgets'] : [];
    $blocks['exclude_widgets'] = array_merge($blocks['exclude_widgets'], ['wpcom/hidden-content']);
    return $blocks;
}

add_action('init', 'wpcom_blocks_render');
function wpcom_blocks_render(){
    register_block_type('wpcom/slider', [
        'render_callback' => 'wpcom_block_slider_render'
    ]);
}

if(!function_exists('wpcom_block_slider_render')){
    function wpcom_block_slider_render($attrs, $content){
        global $post;
        $title = $post && $post->post_title ? $post->post_title : '';
        extract($attrs, EXTR_SKIP);
        if(isset($preview) && $preview) $group = substr(md5(maybe_serialize($attrs)), 8 ,16);
        $autoplay = isset($autoplay) ? $autoplay : 5000;
        $autoplay = $autoplay == '0' ? 86400000 : $autoplay;
        ob_start(); ?>
        <div class="wp-block-wpcom-slider">
            <?php if(function_exists('WWA_is_rest') && WWA_is_rest() && (isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : ''))){ ?>
                <?php echo wp_json_encode($attrs);?>
            <?php }else{ ?>
                <div class="entry-content-slider swiper-container">
                    <ul class="swiper-wrapper">
                        <?php foreach($images as $i => $img){ ?>
                            <li class="swiper-slide" data-swiper-autoplay="<?php echo $autoplay;?>">
                                <?php if(isset($preview) && $preview){ ?>
                                    <a class="j-wpcom-lightbox" data-group="<?php echo  $group;?>" href="<?php echo esc_url($img['url']);?>">
                                        <img <?php echo $i===0?' class="j-lazy"':'';?> src="<?php echo esc_url($img['url']);?>" alt="<?php echo esc_attr($img['alt']?:($img['caption']?:$title));?>" />
                                        <?php if(isset($img['caption']) && $img['caption']){ ?>
                                            <p class="slide-title">
                                                <?php echo $img['caption'];?>
                                            </p>
                                        <?php } ?>
                                    </a>
                                <?php }else{ ?>
                                    <img class="no-lightbox<?php echo $i===0?' j-lazy':'';?>" src="<?php echo esc_url($img['url']);?>" alt="<?php echo esc_attr($img['alt']?:($img['caption']?:$title));?>" />
                                    <?php if(isset($img['caption']) && $img['caption']){ ?>
                                        <p class="slide-title">
                                            <?php echo $img['caption'];?>
                                        </p>
                                    <?php } ?>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                    <div class="swiper-pagination"></div>
                    <!-- Add Navigation -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            <?php } ?>
        </div>
        <?php
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}