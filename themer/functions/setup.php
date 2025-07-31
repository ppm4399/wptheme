<?php defined( 'ABSPATH' ) || exit;

use WPCOM\Themer;
use WPCOM\Member;

add_action('after_setup_theme', 'wpcom_lang_setup', 5);
function wpcom_lang_setup(){
    load_theme_textdomain('wpcom', get_template_directory() . '/lang');
    if( is_child_theme() ) {
        $file = get_stylesheet_directory() . '/lang';
        $locale = determine_locale();
        if(file_exists("$file/$locale.mo")) load_theme_textdomain('wpcom', $file);
    }
}

// wpcom setup
add_action('after_setup_theme', 'wpcom_setup');
if ( ! function_exists( 'wpcom_setup' ) ) :
    function wpcom_setup() {
        global $options;

        add_theme_support( 'woocommerce', array(
            'thumbnail_image_width' => 480,
            'single_image_width' => 800
        ) );

        add_theme_support( 'html5', array(
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ) );

        add_theme_support('custom-line-height');

        // gutenberg 兼容
        if( function_exists('gutenberg_init') ) {
            add_theme_support( 'wp-block-styles' );
        }
        new Themer\Static_Cache();

        // 缩略图设置
        add_theme_support( 'post-thumbnails' );
        $sizes = apply_filters('wpcom_image_sizes', []);
        if( isset($sizes['post-thumbnail']) && $sizes['post-thumbnail'] )
            set_post_thumbnail_size( $sizes['post-thumbnail']['width'], $sizes['post-thumbnail']['height'], true );

        // 允许添加友情链接
        if ( $_SERVER && isset($_SERVER['WP_DIR']) ) {
            add_filter( 'get_bookmarks', '__return_false' );
        }else{
            add_filter( 'pre_option_link_manager_enabled', '__return_true' );
        }

        add_filter( 'pre_option_site_logo', function(){ return '';} );

        // This theme uses wp_nav_menu() in two locations.
        register_nav_menus( apply_filters( 'wpcom_menus', [] ));

        // 切换经典小工具
        if(isset($options['classic_widgets']) && $options['classic_widgets']) {
            add_filter('gutenberg_use_widgets_block_editor', '__return_false');
            add_filter('use_widgets_block_editor', '__return_false');
        }

        WPCOM::load_file(FRAMEWORK_PATH . '/includes/hidden-content.php');

        // IS_WPCOM 判断是否WordPress.com托管，如果是则不启用缓存
        if(!defined( 'IS_WPCOM' ) && isset($options['enable_cache']) && $options['enable_cache']=='1' && WPCOM::load_file(FRAMEWORK_PATH . '/includes/object-cache.php')){
            new Themer\Object_Cache();
        }

        if(isset($options['filter_item_id']) && !empty($options['filter_item_id']) && WPCOM::load_file(FRAMEWORK_PATH . '/includes/multi-filter.php')){
            new WPCOM_Multi_Filter();
        }

        if(isset($options['wx_appid']) && $options['wx_appid'] && $options['wx_appsecret'] && WPCOM::load_file(FRAMEWORK_PATH . '/includes/wx-share.php')) {
            new Themer\WXShare();
        }

        if( defined('WPMX_VERSION') ) {
            global $wpmx_options;
            if( (!isset($wpmx_options['member_group_on']) || (isset($wpmx_options['member_group_on']) && $wpmx_options['member_group_on']=='1')) && WPCOM::load_file(FRAMEWORK_PATH . '/includes/user-groups.php') ) {
                new Member\User_Groups();
            }

            if( isset($wpmx_options['member_follow']) && $wpmx_options['member_follow']=='1' && WPCOM::load_file(FRAMEWORK_PATH . '/includes/follow.php') ) {
                new Member\Follow();
            }

            if( isset($wpmx_options['member_messages']) && $wpmx_options['member_messages']=='1' && WPCOM::load_file(FRAMEWORK_PATH . '/includes/messages.php') ) {
                new Member\Messages();
            }

            if( isset($wpmx_options['member_notify']) && $wpmx_options['member_notify']=='1' && WPCOM::load_file(FRAMEWORK_PATH . '/includes/notifications.php') ) {
                $GLOBALS['_notification'] = new Member\Notifications();
            }

            if( isset($wpmx_options['user_card']) && $wpmx_options['user_card']=='1' && WPCOM::load_file(FRAMEWORK_PATH . '/includes/user-card.php') ) {
                new Member\User_Card();
            }
        }

        remove_action( 'wp_head', 'rel_canonical' );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_filter( 'wp_robots', 'wp_robots_max_image_preview_large' );
        add_filter( 'revslider_meta_generator', '__return_false' );
        add_filter( 'wp_lazy_loading_enabled', '__return_false' );
        add_filter( 'wp_calculate_image_srcset', '__return_false', 99999 );
        add_filter( 'rss_widget_feed_link', '__return_false' );

        if( !isset($options['disable_rest']) || (isset($options['disable_rest']) && $options['disable_rest']=='1')) {
            remove_action('wp_head', 'rest_output_link_wp_head', 10);
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
            remove_action( 'template_redirect', 'rest_output_link_header', 11);
        }

        if( !isset($options['disable_emoji']) || (isset($options['disable_emoji']) && $options['disable_emoji']=='1')) {
            global $wpsmiliestrans;
            $wpsmiliestrans = []; // 禁用系统表情转换
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('embed_head', 'print_emoji_detection_script');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            add_filter( 'tiny_mce_plugins', 'wpcom_disable_emojis_tinymce' );
            add_filter( 'emoji_svg_url', '__return_false' );
        }

        if(is_admin()){
            require_once FRAMEWORK_PATH . '/includes/plugin-activation.php';
            require_once FRAMEWORK_PATH . '/includes/term-meta.php';
            require_once FRAMEWORK_PATH . '/includes/importer.php';
            new WPCOM_DEMO_Importer();
        }

        // 尝试优化IIS中文链接
        if($_SERVER && isset($_SERVER['IIS_UrlRewriteModule']) && isset($_SERVER['REQUEST_URI']) && isset($_SERVER['UNENCODED_URL'])){
            $_SERVER['REQUEST_URI'] = $_SERVER['UNENCODED_URL'];
        }
    }
endif;

add_filter( 'upload_mimes', 'wpcom_mime_types' );
function wpcom_mime_types( $mimes = [] ){
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}

add_filter( 'wp_check_filetype_and_ext', 'wpcom_svgs_upload_check', 10, 4 );
function wpcom_svgs_upload_check( $checked, $file, $filename, $mimes ) {
    if ( ! $checked['type'] ) {
        $check_filetype		= wp_check_filetype( $filename, $mimes );
        $ext				= $check_filetype['ext'];
        $type				= $check_filetype['type'];
        $proper_filename	= $filename;

        if ( $type && 0 === strpos( $type, 'image/' ) && $ext !== 'svg' ) {
            $ext = $type = false;
        }

        $checked = compact( 'ext','type','proper_filename' );
    }
    return $checked;
}

add_action( 'admin_init', 'wpcom_admin_setup' );
function wpcom_admin_setup() {
    global $pagenow;
    if( $pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'admin-ajax.php' ){
        require_once FRAMEWORK_PATH . '/includes/shortcodes.php';
        new WPCOM_Shortcodes();
    }
    require_once FRAMEWORK_PATH . '/includes/meta-box.php';
    new Themer\Meta();
    if( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
        if( file_exists( get_template_directory() . '/css/editor-style.css' ) )
            add_editor_style( 'css/editor-style.css' );
    }
    if (!wp_next_scheduled ( 'wpcom_sessions_clear' )) wp_schedule_event(time(), 'hourly', 'wpcom_sessions_clear');
    if (!wp_next_scheduled ( 'wpcom_static_cache_clear' )) wp_schedule_event(time(), 'twicedaily', 'wpcom_static_cache_clear');
    if (!wp_next_scheduled ( 'wpcom_themer_updated_check' )) wp_schedule_event(time(), 'twicedaily', 'wpcom_themer_updated_check');
}

add_action( 'wpcom_sessions_clear', [Themer\Session::class, 'cron'] );

add_filter( 'wpcom_image_sizes', 'wpcom_image_sizes' );
function wpcom_image_sizes($image_sizes){
    global $options, $_wp_additional_image_sizes;

    if( !empty($_wp_additional_image_sizes) ) {
        foreach ($_wp_additional_image_sizes as $sk => $size) {
            if( $sk =='shop_single' || $sk =='woocommerce_single' ) $size['crop'] = 1;
            if (isset($size['crop']) && $size['crop'] == 1) {
                $image_sizes[$sk] = $size;
            }
        }
    }
    $image_sizes['post-thumbnail'] = array(
        'width' => intval(isset($options['thumb_width']) && $options['thumb_width'] ? $options['thumb_width'] : 480),
        'height' => intval(isset($options['thumb_height']) && $options['thumb_height'] ? $options['thumb_height'] : 320)
    );
    $image_sizes['default'] = array(
        'width' => intval(isset($options['thumb_default_width']) && $options['thumb_default_width'] ? $options['thumb_default_width'] : 480),
        'height' => intval(isset($options['thumb_default_height']) && $options['thumb_default_height'] ? $options['thumb_default_height'] : 320)
    );
    return $image_sizes;
}

// 加载静态资源
add_action('wp_enqueue_scripts', 'wpcom_register_scripts', 1);
add_action('admin_enqueue_scripts', 'wpcom_register_scripts', 1);
add_action('embed_head', 'wpcom_register_scripts', 1);
function wpcom_register_scripts(){
    global $options;
    $action = current_filter();
    if($action === 'wp_enqueue_scripts' && !defined('IFRAME_REQUEST') && !wp_script_is('jquery')){
        wp_register_script('jquery-core', includes_url('js/jquery/jquery.min.js'), [], THEME_VERSION);
    }
    $css = is_child_theme() ? '/style.css' : '/css/style.css';
    wp_register_style('stylesheet', get_stylesheet_directory_uri() . $css, [], THEME_VERSION);
    wp_register_script('main', get_template_directory_uri() . '/js/main.js', [ 'jquery' ], THEME_VERSION, true);
    if(isset($options['iconfont']) && $options['iconfont']) wp_register_script('iconfont', $options['iconfont'], [], THEME_VERSION, true);
    wp_register_style('material-icons', FRAMEWORK_URI . '/assets/css/material-icons.css', false, THEME_VERSION);
    wp_register_style('font-awesome', FRAMEWORK_URI . '/assets/css/font-awesome.css', false, THEME_VERSION);
    wp_register_style('remixicon', FRAMEWORK_URI . '/assets/css/remixicon.css', false, '4.6.0');
    wp_register_script('wpcom-icons', FRAMEWORK_URI . '/assets/js/icons-2.8.9.js', [], '2.8.9', true);
    wp_deregister_script('comment-reply');
    wp_register_script('comment-reply', FRAMEWORK_URI . '/assets/js/comment-reply.js', [], THEME_VERSION, true);
}

if ( ! function_exists( 'wpcom_scripts' ) ) :
    function wpcom_scripts() {
        global $options, $wpmx_options;
        // 载入主样式
        wp_enqueue_style('stylesheet');
        if(isset($options['material_icons']) && $options['material_icons']) wp_enqueue_style('material-icons');
        if(isset($options['remixicon']) && $options['remixicon']) wp_enqueue_style('remixicon');
        if((isset($options['fontawesome']) && $options['fontawesome']) || !isset($options['fontawesome'])) wp_enqueue_style('font-awesome');

        // 载入js文件
        wp_enqueue_script('main');
        wp_enqueue_script('wpcom-icons');
        if(isset($options['iconfont']) && $options['iconfont']) wp_enqueue_script('iconfont');

        // wpcom_localize_script
        $webp = isset($options['webp_suffix']) && $options['webp_suffix'] ? $options['webp_suffix'] : '';
        $script = array(
            'webp' => $webp,
            'ajaxurl' => admin_url( 'admin-ajax.php'),
            'theme_url' => get_template_directory_uri(),
            'slide_speed' => isset($options['slide_speed']) ? $options['slide_speed']: '',
            'is_admin' => current_user_can('manage_options') ? '1' : '0',
            'lang' => get_locale(),
            'js_lang' => array(
                'share_to' => __('Share To :', 'wpcom'),
                'copy_done' => __('Copy successful!', 'wpcom'),
                'copy_fail' => __('The browser does not currently support the copy function', 'wpcom'),
                'confirm' => __('Confirm', 'wpcom'),
                'qrcode' => __('QR Code', 'wpcom')
            )
        );
        $share = isset($options['share']) && $options['share'] == '1';
        if($share){
            $script['share'] = $share;
            $share_items = isset($options['share_items']) ? $options['share_items'] : '';
            if(!empty($share_items)){
                $share_types = array(
                    'weibo' => array(
                        'title' => _x('Weibo', 'share', 'wpcom'),
                        'icon' => 'weibo'
                    ),
                    'wechat' => array(
                        'title' => _x('WeChat', 'share', 'wpcom'),
                        'icon' => 'wechat'
                    ),
                    'qq' => array(
                        'title' => _x('QQ', 'share', 'wpcom'),
                        'icon' => 'qq'
                    ),
                    'qzone' => array(
                        'title' => _x('QZone', 'share', 'wpcom'),
                        'icon' => 'qzone'
                    ),
                    'douban' => array(
                        'name' => 'douban',
                        'title' => _x('Douban', 'share', 'wpcom'),
                        'icon' => 'douban'
                    ),
                    'linkedin' => array(
                        'title' => _x('LinkedIn', 'share', 'wpcom'),
                        'icon' => 'linkedin'
                    ),
                    'facebook' => array(
                        'title' => _x('Facebook', 'share', 'wpcom'),
                        'icon' => 'facebook'
                    ),
                    'x' => array(
                        'title' => _x('X', 'share', 'wpcom'),
                        'icon' => 'twitter-x'
                    ),
                    'twitter' => array(
                        'title' => _x('Twitter', 'share', 'wpcom'),
                        'icon' => 'twitter'
                    ),
                    'tumblr' => array(
                        'title' => _x('Tumblr', 'share', 'wpcom'),
                        'icon' => 'tumblr'
                    ),
                    'whatsapp' => array(
                        'title' => _x('WhatsApp', 'share', 'wpcom'),
                        'icon' => 'whatsapp'
                    ),
                    'pinterest' => array(
                        'title' => _x('Pinterest', 'share', 'wpcom'),
                        'icon' => 'pinterest'
                    ),
                    'line' => array(
                        'title' => _x('LINE', 'share', 'wpcom'),
                        'icon' => 'line'
                    ),
                    'telegram' => array(
                        'title' => _x('Telegram', 'share', 'wpcom'),
                        'icon' => 'telegram'
                    ),
                    'mail' => array(
                        'title' => _x('Email', 'share', 'wpcom'),
                        'icon' => 'mail-fill'
                    )
                );
                $_share_items = [];
                foreach($share_items as $item){
                    if($item && isset($share_types[$item])){
                        $_share_items[$item] = $share_types[$item];
                    }
                }
                if(!empty($_share_items)) $script['share_items'] = $_share_items;
            }
        }
        if(!is_dir(get_template_directory() . '/themer')) $script['framework_url'] = FRAMEWORK_URI;
        if( is_singular() && (!isset($options['post_img_lightbox']) || $options['post_img_lightbox']=='1') ) {
            $script['lightbox'] = 1;
        }
        if(is_singular()) $script['post_id'] = get_queried_object_id();
        if($wpmx_options && isset($wpmx_options['user_card']) && $wpmx_options['user_card']=='1'){
            $script['user_card_height'] = 356;
            if(!$wpmx_options['member_follow'] && !$wpmx_options['member_messages']){
                $script['user_card_height'] = 308;
            }
        }
        $wpcom_js = apply_filters('wpcom_localize_script', $script);
        wp_localize_script( 'main', '_wpcom_js', $wpcom_js );

        if ( is_singular() && isset($options['comments_open']) && $options['comments_open']=='1' && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }
endif;
add_action('wp_enqueue_scripts', 'wpcom_scripts', 2);
/* 静态资源结束 */

// Excerpt more
add_filter('excerpt_more', 'wpcom_excerpt_more');
if ( ! function_exists( 'wpcom_excerpt_more' ) ) :
    function wpcom_excerpt_more( $more ) {
        global $wp_locale;
        $wp_locale->word_count_type = 'characters_excluding_spaces';
        return '…';
    }
endif;

add_filter('comment_excerpt_length', 'wpcom_comment_excerpt_length');
function wpcom_comment_excerpt_length(){
    return 150;
}

add_filter( 'body_class', 'wpcom_body_class', 10);
function wpcom_body_class( $classes ){
    if( is_page() ){
        global $post;
        $sidebar = get_post_meta( $post->ID, 'wpcom_sidebar', true );
        $sidebar = !(!$sidebar && $sidebar!=='');
        if(!$sidebar) $classes[] = 'page-no-sidebar';
    }
    $lang = get_locale();
    if($lang == 'zh_CN' || $lang == 'zh_TW' || $lang == 'zh_HK') {
        $classes[] = 'lang-cn';
    } else {
        $classes[] = 'lang-other';
    }
    return $classes;
}

if ( ! function_exists( 'wpcom_disable_emojis_tinymce' ) ) :
    function wpcom_disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, [ 'wpemoji' ] );
        } else {
            return [];
        }
    }
endif;

if ( ! function_exists( 'utf8_excerpt' ) ) :
    function utf8_excerpt($str, $len, $more = true){
        $str = strip_tags( str_replace( array( "\n", "\r" ), ' ', $str ) );
        if(function_exists('mb_substr')){
            $excerpt = mb_substr($str, 0, $len, 'utf-8');
        }else{
            preg_match_all("/[x01-x7f]|[xc2-xdf][x80-xbf]|xe0[xa0-xbf][x80-xbf]|[xe1-xef][x80-xbf][x80-xbf]|xf0[x90-xbf][x80-xbf][x80-xbf]|[xf1-xf7][x80-xbf][x80-xbf][x80-xbf]/", $str, $ar);
            $excerpt = join('', array_slice($ar[0], 0, $len));
        }

        if($more && trim($str) != trim($excerpt)) $excerpt .= '…';

        return $excerpt;
    }
endif;

function wpcom_add_log($msg){
    $_dir = _wp_upload_dir();
    $folder = apply_filters('wpcom_static_cache_path', 'wpcom');
    $dir = $_dir['basedir'] . '/' . $folder;
    if(wp_mkdir_p($dir)) {
        @file_put_contents($dir . '/log-' . current_time('Ym') . '.log', '[' . current_time('mysql') . ']: ' . $msg . "\r\n", FILE_APPEND);
    }
}

add_filter( 'mce_buttons_2', 'wpcom_mce_wp_page' );
function wpcom_mce_wp_page( $buttons ) {
    $buttons[] = 'wp_page';
    return $buttons;
}

add_filter( 'mce_buttons', 'wpcom_mce_buttons', 20 );
function wpcom_mce_buttons( $buttons ) {
    $res = [];
    foreach( $buttons as $bt ) {
        $res[] = $bt;
        if( $bt=='formatselect' && !in_array( 'fontsizeselect', $buttons ) ){
            $res[] = 'fontsizeselect';
        } else if( $bt=='link' && !in_array( 'unlink', $buttons ) ){
            $res[] = 'unlink';
        }
    }
    return $res;
}

add_filter( 'tiny_mce_before_init', 'wpcom_mce_text_sizes' );
function wpcom_mce_text_sizes( $initArray ){
    $initArray['fontsize_formats'] = "10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 42px";
    return $initArray;
}

// 控制边栏标签云
add_filter('widget_tag_cloud_args', 'wpcom_tag_cloud_filter', 10);
function wpcom_tag_cloud_filter($args = []) {
    global $options;
    $args['number'] = isset($options['tag_cloud_num']) && $options['tag_cloud_num'] ? $options['tag_cloud_num'] : 30;
    return $args;
}

add_filter( 'pre_update_option_sticky_posts', 'wpcom_fix_sticky_posts' );
if ( ! function_exists( 'wpcom_fix_sticky_posts' ) ) :
    function wpcom_fix_sticky_posts( $stickies ) {
        if( !class_exists('SCPO_Engine') ) {
            global $wpdb;
            $menu_order = 1;
            $exists = $wpdb->get_var( $wpdb->prepare("SELECT 1 FROM $wpdb->posts WHERE `post_type` = %s AND `menu_order` not IN (0,1)", 'post') );
            if( $exists ) {
                // 先预处理防止插件设置的menu_order，主要是SCPOrder插件
                $wpdb->update($wpdb->posts, ['menu_order' => 0], ['post_type' => 'post']);
            }
        }else{
            $menu_order = -1;
        }

        $old_stickies = array_diff( get_option( 'sticky_posts' ), $stickies );
        foreach( $stickies as $sticky )
            wp_update_post( [ 'ID' => $sticky, 'menu_order' => $menu_order ] );
        foreach( $old_stickies as $sticky )
            wp_update_post( [ 'ID' => $sticky, 'menu_order' => 0 ] );

        return $stickies;
    }
endif;

if ( ! function_exists( 'wpcom_sticky_posts_query' ) && !class_exists('SCPO_Engine') ) :
    add_action( 'pre_get_posts', 'wpcom_sticky_posts_query', 20 );
    function wpcom_sticky_posts_query( $q ) {
        if( $q->get('post_type') && $q->get('post_type') != 'post' ) return $q;

        if( !isset( $q->query_vars[ 'ignore_sticky_posts' ] ) ){
            $q->query_vars[ 'ignore_sticky_posts' ] = 1;
        }
        if ( isset( $q->query_vars[ 'ignore_sticky_posts' ] ) && !$q->query_vars[ 'ignore_sticky_posts' ] ){
            $q->query_vars[ 'ignore_sticky_posts' ] = 1;
            if(isset($q->query_vars[ 'orderby' ]) && $q->query_vars[ 'orderby' ]) {
                $q->query_vars[ 'orderby' ] .= ' menu_order';
            }else{
                $q->query_vars[ 'orderby' ] = 'menu_order date';
            }
        }
        return $q;
    }
endif;

add_filter('wp_handle_upload_prefilter','wpcom_file_upload_rename', 10);
if ( ! function_exists( 'wpcom_file_upload_rename' ) ) :
function wpcom_file_upload_rename( $file ) {
    global $options;
    if(isset($options['file_upload_rename']) && $options['file_upload_rename']) {
        $file['name'] = preg_replace('/\s/', '-', $file['name']);
        if ($options['file_upload_rename']=='2' || ($options['file_upload_rename']=='1' && !preg_match('/^[0-9_a-zA-Z!@()+-.]+$/u', $file['name']))) {
            $ext = substr(strrchr($file['name'], '.'), 1);
            $file['name'] = date('YmdHis') . rand(10, 99) . '.' . $ext;
        }
    }
    return $file;
}
endif;

// 安装依赖插件
function wpcom_register_required_plugins() {
    $config = array(
        'id'           => 'wpcom',
        'default_path' => '',
        'menu'         => 'wpcom-install-plugins',
        'parent_slug'  => 'wpcom-panel',
        'capability'   => 'edit_theme_options',
        'has_notices'  => true,
        'dismissable'  => true,
        'dismiss_msg'  => '',
        'is_automatic' => false
    );

    tgmpa( $config );
}

add_action( 'tgmpa_register', 'wpcom_register_required_plugins' );

function wpcom_tgm_show_admin_notice_capability() {
    return 'edit_theme_options';
}
add_filter( 'tgmpa_show_admin_notice_capability', 'wpcom_tgm_show_admin_notice_capability' );

function wpcom_lazyimg( $img, $alt, $width='', $height='', $class='' ){
    global $options;
    $class_html = $class ? ' class="'.$class.'"' : '';
    $size = $width ? ' width="'.intval($width).'"' : '';
    $size .= $height ? ' height="'.intval($height).'"' : '';
    if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' && !is_embed() && !preg_match('/^data:image\//i', $img)){
        $class_html = $class ? ' class="j-lazy '.$class.'"' : ' class="j-lazy"';
        $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? (is_numeric($options['lazyload_img']) ? WPCOM::get_image_url($options['lazyload_img']) : $options['lazyload_img']) : FRAMEWORK_URI.'/assets/images/lazy.png';
        $html = '<img'.$class_html.' src="'.$lazy_img.'" data-original="'.esc_url($img).'" alt="'.esc_attr($alt).'"'.$size.'>';
    }else{
        $html = '<img'.$class_html.' src="'.(preg_match('/^data:image\//i', $img) ? $img : esc_url($img)).'" alt="'.esc_attr($alt).'"'.$size.'>';
    }
    return $html;
}

function wpcom_lazybg( $img, $class='', $style='' ){
    global $options;
    if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' && !is_embed() && !preg_match('/^data:image\//i', $img) ){
        $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? (is_numeric($options['lazyload_img']) ? WPCOM::get_image_url($options['lazyload_img']) : $options['lazyload_img']) : FRAMEWORK_URI.'/assets/images/lazy.png';
        $attr = 'class="'.$class.' j-lazy" style="background-image: url(\''.$lazy_img.'\');'.$style.'" data-original="'.esc_url($img).'"';
    }else{
        $attr = 'class="'.$class.'" style="background-image: url(\''.$img.'\');'.$style.'"';
    }
    return $attr;
}

add_filter( 'wpcom_sidebars', 'wp_no_sidebar' );
function wp_no_sidebar( $sidebar ){
    $sidebar['0'] = '不显示边栏';
    return $sidebar;
}

add_filter( 'wp_video_shortcode_class', 'wpcom_video_shortcode_class' );
function wpcom_video_shortcode_class($class){
    $class = str_replace('wp-video-shortcode', '', $class);
    $class .= ' j-wpcom-video';
    return trim($class);
}

add_action('wp_head', 'wpcom_head_code', 10);
function wpcom_head_code(){
    global $options;
    if(isset($options['head_code']) && $options['head_code']) echo $options['head_code'] . "\n";
}

add_action('wp_footer', 'wpcom_footer_code', 20);
function wpcom_footer_code(){
    global $options;
    if(isset($options['footer_code']) && $options['footer_code']) echo $options['footer_code'] . "\n";;
}

add_filter('get_site_icon_url', 'wpcom_get_site_icon_url', 10, 2);
function wpcom_get_site_icon_url($url, $size){
    global $options;
    if(isset($options['fav']) && $options['fav']) {
        if ( $size >= 512 ) {
            $size_data = 'full';
        } else {
            $size_data = array( $size, $size );
        }
        $url = is_numeric($options['fav']) ? wp_get_attachment_image_url( $options['fav'], $size_data ) : $options['fav'];
    }
    return $url;
}

add_action('pre_handle_404', 'wpcom_pre_handle_404');
function wpcom_pre_handle_404($res){
    global $wp_query;
    if ( $wp_query->posts ) {
        $content_found = true;
        if ( is_singular() ) {
            $post = isset( $wp_query->post ) ? $wp_query->post : null;
            // Only set X-Pingback for single posts that allow pings.
            if ( $post && pings_open( $post ) && ! headers_sent() ) {
                header( 'X-Pingback: ' . get_bloginfo( 'pingback_url', 'display' ) );
            }
            $paged = get_query_var( 'page' );
            if ( $post && ! empty( $paged ) ) {
                $shortcode_tags = array('wpcom_tags', 'wpcom-member');
                preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $post->post_content, $matches );
                $tagnames = array_intersect( $shortcode_tags, $matches[1] );

                if ( empty($tagnames) ) {
                    $content_found = false;
                }else if(in_array('wpcom_tags', $tagnames)){
                    preg_match( '/\[wpcom_tags[^\]]*\]/i', $post->post_content, $matches2 );
                    if(isset($matches2[0])){
                        $text = ltrim($matches2[0], '[wpcom_tags');
                        $text = rtrim($text, ']');
                        $atts = shortcode_parse_atts($text);
                        if(isset($atts['per_page']) && $atts['per_page']){ // 分页
                            $tax = isset($atts['taxonomy']) && $atts['taxonomy'] ? $atts['taxonomy'] : 'post_tag';
                            $max   = wp_count_terms( $tax, array( 'hide_empty' => true ) );
                            $pages   = ceil( $max / $atts['per_page'] );
                            if($pages<$paged) $content_found = false; // 页数超过
                        }else{ // 未分页，则一页全部显示
                            $content_found = false;
                        }
                    }
                }
            }
        }

        if ( $content_found ) $res = true;
    }
    return $res;
}

function wpcom_empty_icon($type='post'){
    return '<img class="empty-icon j-lazy" src="'.FRAMEWORK_URI.'/assets/images/empty-'.$type.'.svg">';
}

function wpcom_logo(){
    global $options;
    $logo = isset($options['logo']) ? (is_numeric($options['logo']) ? WPCOM::get_image_url( $options['logo'] ) : $options['logo']) : '';
    $logo = $logo ?: get_template_directory_uri().'/images/logo.png';
    return esc_url($logo);
}

add_action('wpcom_themer_maybe_updated', 'wpcom_themer_update');
function wpcom_themer_update() {
    $is_multi = is_multisite();
    $network_id = $is_multi ? get_main_network_id() : null;
    $version = $is_multi ? get_network_option( $network_id, 'themer_version' ) : get_option('themer_version');
    if(!$version || $version !== FRAMEWORK_VERSION){
        do_action('themer_updated', $version ?: '');
        if($is_multi){
            update_network_option( $network_id, 'themer_version', FRAMEWORK_VERSION );
        }else{
            update_option('themer_version', FRAMEWORK_VERSION);
        }
    }
}

add_action('themer_updated', function($version){
    global $wpdb;
    if($version && version_compare($version, '2.8.11', '<=')) {
        $metas = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key = %s", '_page_modules'));
        if($metas){
            foreach ($metas as $meta){
                $modules = maybe_unserialize($meta->meta_value);
                $modules = is_string($modules) ? json_decode($modules, true) : $modules;
                if(is_array($modules) && count($modules) > 0) {
                    if(isset($modules['type'])) $modules = array($modules);
                    $data = wpcom_update_grids($modules);
                    if($data){
                        $data = wp_slash(wp_json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
                        if($data) update_post_meta($meta->post_id, '_page_modules', $data);
                    }
                }
            }
        }
    }
});

function wpcom_update_grids($modules){
    if($modules && is_array($modules)){
        foreach ($modules as $i => $module){
            if(isset($module['type']) && $module['type'] === 'gird' && isset($module['settings']) && isset($module['settings']['girds'])){
                $module['type'] = 'grid';
                $module['settings']['grids'] = $module['settings']['girds'];
                unset($module['settings']['girds']);
                if(!empty($module['settings']['grids'])){
                    foreach($module['settings']['grids'] as $x => $g){
                        $module['settings']['grids'][$x] = wpcom_update_grids($g);
                    }
                }
                $modules[$i] = $module;
            }else if(isset($module['type']) && $module['type'] === 'color-gird'){
                $module['type'] = 'color-grid';
                $modules[$i] = $module;
            }else if(isset($module['type']) && $module['type'] === 'image-gird'){
                $module['type'] = 'image-grid';
                $modules[$i] = $module;
            }else if(isset($module['settings']) && isset($module['settings']['modules'])){
                $modules[$i]['settings']['modules'] = wpcom_update_grids($module['settings']['modules']);
            }else if(isset($module['settings']) && isset($module['settings']['modules'])){
                $modules[$i]['settings']['modules'] = wpcom_update_grids($module['settings']['modules']);
            }else if(isset($module['type']) && $module['type'] === 'tabs' && isset($module['settings']) && !empty($module['settings']['tabs'])){
                foreach($module['settings']['tabs'] as $y => $t){
                    $module['settings']['tabs'][$y] = wpcom_update_grids($t);
                }
                $modules[$i] = $module;
            }else{
                $modules[$i] = $module;
            }
        }
    }
    return $modules;
}

// 评论class 移除用户名
add_filter( 'comment_class', 'wpcom_comment_class' );
function wpcom_comment_class($classes){
    if($classes){
        foreach($classes as $i => $class){
            if(preg_match('/^comment-author-/i', $class)){
                unset($classes[$i]);
                break;
            }
        }
    }
    return $classes;
}

// Stop default query
add_filter( 'posts_pre_query', function( $posts, \WP_Query $query ){
    if( $query->is_home() && $query->is_main_query() && !isset($query->query['sitemap'])) {
        $posts = [];
        $query->found_posts = 0;
    }
    return $posts;
}, 100, 2 );

// 列表有缩略图的话通过thumbnail参数声明，统一一次性查询获取，避免单个查询数据库
add_action('loop_start', function($query){
    if($query && $query->query_vars && isset($query->query_vars['thumbnail']) && $query->query_vars['thumbnail'] && $query->posts){
        $post_ids = [];
        foreach($query->posts as $_post){
            if($_post && $_post->ID) {
                $_thumbnail_id = get_post_thumbnail_id( $_post->ID );
                if($_thumbnail_id) $post_ids[] = $_thumbnail_id;
            }
        }
        $post_ids = array_unique(array_filter($post_ids));
        $update_term_cache = isset($query->query_vars['update_post_term_cache']) ? $query->query_vars['update_post_term_cache'] : true;
        $update_meta_cache = isset($query->query_vars['update_post_meta_cache']) ? $query->query_vars['update_post_meta_cache'] : true;
        _prime_post_caches($post_ids, $update_term_cache, $update_meta_cache);
    }
});

// 修改图片区块自定义宽高后移动端缩放比例适配问题
add_filter('render_block', 'wpcom_add_aspect_ratio_to_image_block', 10, 2);
function wpcom_add_aspect_ratio_to_image_block($block_content, $block){
    if ($block['blockName'] === 'core/image' && isset($block['attrs'])) {
        $width = isset($block['attrs']['width']) ? intval($block['attrs']['width']) : '';
        $height = isset($block['attrs']['height']) ? intval($block['attrs']['height']) : '';
        $scale = isset($block['attrs']['scale']) ? $block['attrs']['scale'] : '';
        $aspectRatio = isset($block['attrs']['aspectRatio']) ? $block['attrs']['aspectRatio'] : '';

        if (!$aspectRatio && $scale === 'cover' && $width && $height) {
            $aspect_ratio = $width . '/' . $height;

            // 修改区块内容，增加 img 标签上的 aspect-ratio 样式
            $block_content = preg_replace_callback(
                '/<img (.*?)style="(.*?)"/',
                function($matches) use ($aspect_ratio) {
                    $style = $matches[2] . ";aspect-ratio: {$aspect_ratio};";
                    return '<img ' . $matches[1] . 'style="' . $style . '"';
                },
                $block_content
            );
        }
    }
    return $block_content;
}

// 增加评论过滤，避免xss攻击
add_filter('pre_comment_content', 'wpcom_comment_filter');
function wpcom_comment_filter( $comment_content ) {
    return wp_kses_post( $comment_content );
}

// 搜索限制最长100个字符
add_action('pre_get_posts', 'wpcom_limit_search_chars');
function wpcom_limit_search_chars( $query ) {
    if ( $query->is_search() && !is_admin() && $query->is_main_query() ) {
        $search_query = get_search_query();
        $max_length = 100; // 设置最大搜索字符数为100

        // 如果搜索词超过100字符，截取前100字符
        if ( strlen( $search_query ) > $max_length ) {
            // 修改全局查询，截取前100字符
            $query->set('s', utf8_excerpt($search_query, $max_length, false));
        }
    }
}

add_filter( 'wp_get_attachment_url', function($url){
    if ( preg_match('/^http:\/\//i', $url) && is_ssl() && wp_doing_ajax() ) {
		$url = set_url_scheme( $url );
	}
    return $url;
} );