<?php defined( 'ABSPATH' ) || exit;

class WPCOM {
    public static function get_post($id, $type='post'){
        if(is_numeric($id)){
            return get_post($id);
        }else{
            $args = [
                'name'        => $id,
                'post_status' => 'any',
                'post_type' => $type,
                'posts_per_page' => 1
            ];
            $my_posts = get_posts($args);
            if($my_posts) return $my_posts[0];
        }
    }

    public static function category( $tax = 'category', $filter = false ){
        $args = [
            'taxonomy' => $tax,
            'hide_empty' => false
        ];
        if($filter) $args['suppress_filter'] = true;
        $categories = get_terms($args);

        $cats = [];
        if( $categories && !is_wp_error($categories) ) {
            foreach ($categories as $cat) {
                $cats[$cat->term_id] = $cat->name;
            }
        }

        return $cats;
    }

    public static function get_posts($args, $query = null){
        $defaults = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'no_found_rows' => 1,
            'thumbnail' => 1
        ];
        $parsed_args = wp_parse_args( $args, $defaults );
        if($query){
            $query->query($parsed_args);
            return $query;
        }else{
            return new WP_Query($parsed_args);
        }
    }

    public static function get_all_sliders(){
        $sliders = [];
        if(shortcode_exists("rev_slider")){
            $slider = new RevSlider();
            $revolution_sliders = $slider->getArrSliders();
            foreach ( $revolution_sliders as $revolution_slider ) {
                $alias = $revolution_slider->getAlias();
                $title = $revolution_slider->getTitle();
                $sliders[$alias] = $title.' ('.$alias.')';
            }
        }
        return $sliders;
    }

    public static function panel_script(){
        global $pagenow, $options;
        wp_enqueue_style('themer-panel', FRAMEWORK_URI . '/assets/css/panel.css', false, FRAMEWORK_VERSION, 'all');
        wp_dequeue_style('plugin-panel');
        wp_enqueue_style('material-icons');
        if((isset($options['fontawesome']) && $options['fontawesome']) || !isset($options['fontawesome'])) wp_enqueue_style('font-awesome');
        if(isset($options['remixicon']) && $options['remixicon']) wp_enqueue_style('remixicon');
        wp_enqueue_script('themer-panel', FRAMEWORK_URI . '/assets/js/panel.js', ['jquery', 'jquery-ui-core', 'jquery-ui-sortable'], FRAMEWORK_VERSION, true);
        if(isset($options['iconfont']) && $options['iconfont']) wp_enqueue_script('iconfont');
        if($pagenow!=='post.php' && $pagenow!=='post-new.php') wp_enqueue_media();

        if(wp_enqueue_code_editor([ 'type' => 'text/html' ]) === false){ // 兼容禁用语法高亮的情况
            $settings = wp_get_code_editor_settings( [ 'type' => 'text/html' ] );

            wp_enqueue_script( 'code-editor' );
            wp_enqueue_style( 'code-editor' );
            wp_enqueue_script( 'csslint' );
            wp_enqueue_script( 'htmlhint' );
            wp_enqueue_script( 'jshint' );
            wp_enqueue_script( 'jsonlint' );

            wp_add_inline_script( 'code-editor', sprintf( 'jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode( $settings ) ) );
        }
    }

    public static function editor_settings($args = []){
        add_filter( 'user_can_richedit' , '__return_true', 100 );
        return [
            'textarea_name' => $args['textarea_name'],
            'textarea_rows' => isset($args['textarea_rows']) ? $args['textarea_rows'] : 3,
            'quicktags' => false,
            'media_buttons' => false,
            'tinymce'       => [
                'wp_skip_init' => $args['skip_init'] ?? false,
                'height'        => 150,
                'toolbar1' => 'formatselect,fontsizeselect,bold,italic,blockquote,forecolor,alignleft,aligncenter,alignright,link,bullist,numlist,wpcomimg,wpcomdark,wpcomtext',
                'toolbar2' => '',
                'toolbar3' => '',
                'plugins' => 'colorpicker,hr,lists,media,paste,textcolor,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs,wptextpattern,image,wpcomimg,wpcomdark,wpcomtext',
                'statusbar' => false,
                'content_css' => FRAMEWORK_URI . '/assets/css/tinymce-style.css?ver=' . FRAMEWORK_VERSION,
                'external_plugins' => "{wpcomimg: '".FRAMEWORK_URI."/assets/js/tinymce-img.js',wpcomdark: '".FRAMEWORK_URI."/assets/js/tinymce-dark.js',wpcomtext: '".FRAMEWORK_URI."/assets/js/tinymce-text.js'}"
            ]
        ];
    }

    public static function _options(){
        $res = [];
        if( current_user_can( 'publish_posts' ) ){
            if(current_user_can( 'edit_theme_options' )) {
                global $wpcom_panel;
                $wpcom_panel->updated(0);
            }
            $res['o'] = get_option( THEME_ID . '_options' );
        }
        wp_send_json($res);
    }

    public static function _icons(){
        global $options;
        $icons = [];
        if( current_user_can( 'publish_posts' ) ){
            if((isset($options['fontawesome']) && $options['fontawesome']) || !isset($options['fontawesome'])){
                $icons_file = get_template_directory() . '/fonts/icons.json';
                if( file_exists($icons_file) ) {
                    $fa = @file_get_contents($icons_file);
                    $icons['fa'] = ['name' => 'FontAwesome', 'icons' => json_decode($fa)];
                }
            }
            if(isset($options['iconfont']) && $options['iconfont'])
                $icons['if'] = ['name' => 'Iconfont', 'icons' => json_decode(get_option('wpcom_iconfont'))];
            if(isset($options['material_icons']) && $options['material_icons']){
                $material = get_template_directory() . '/fonts/material-icons.json';
                if( file_exists($material) ) {
                    $mti = @file_get_contents($material);
                    $icons['mti'] = ['name' => 'Material Icons', 'icons' => json_decode($mti)];
                }
            }
            if(isset($options['remixicon']) && $options['remixicon']){
                $remixicon = get_template_directory() . '/fonts/remixicon.json';
                if( file_exists($remixicon) ) {
                    $ri = @file_get_contents($remixicon);
                    $icons['ri'] = ['name' => 'Remix Icon', 'icons' => json_decode($ri)];
                }
            }
        }
        wp_send_json($icons);
    }

    public static function update_icons($res, $options, $old_options){
        if($res['errcode'] == 0) {
            $icons = ['iconfont', 'fontawesome', 'material_icons', 'remixicon'];
            foreach($icons as $icon){
                $enable = isset($options[$icon]) && $options[$icon] !== '' ? trim($options[$icon]) : '';
                $enabled = isset($old_options[$icon]) && $old_options[$icon] !== '' ? trim($old_options[$icon]) : '';
                if($enable !== $enabled){
                    $res['icon'] = 1;
                    if($enable && $icon === 'iconfont'){
                        if(preg_match('/^\/\//i', $enable)) $enable = 'http:' . $enable;
                        $get = wp_remote_get($enable);
                        if(!is_wp_error($get) && $get['body']) {
                            preg_match_all( '/id="icon-([^"]+)"/i', $get['body'], $matches );
                            if($matches && isset($matches[1])){
                                update_option('wpcom_iconfont', wp_json_encode($matches[1]));
                            }
                        }
                    }
                    break;
                }
            }
        }
        return $res;
    }

    public static function load( $folder ){
        if( $globs = glob( "{$folder}/*.php" ) ) {
            $config_file = get_template_directory() . '/themer-config.json';
            if( file_exists($config_file) ) {
                $config = @file_get_contents($config_file);
                if( $config != '' ) $config = json_decode($config);
            }
            $except = isset($config) && isset($config->except) ? $config->except : [];
            foreach( $globs as $file ) {
                if(empty($except) || !in_array(str_replace(FRAMEWORK_PATH, 'themer', $file), $except)){
                    require_once $file;
                }
            }
        }
    }

    public static function load_file( $file ){
        if( $file && file_exists($file) ) {
            $config_file = get_template_directory() . '/themer-config.json';
            if( file_exists($config_file) ) {
                $config = @file_get_contents($config_file);
                if( $config != '' ) $config = json_decode($config);
            }
            $except = isset($config) && isset($config->except) ? $config->except : [];
            if(empty($except) || !in_array(str_replace(FRAMEWORK_PATH, 'themer', $file), $except)){
                require_once $file;
                return true;
            }
        }
        return false;
    }

    public static function thumbnail( $url, $width = null, $height = null, $crop = false, $img_id = 0, $size = '', $single = false, $upscale = true ) {
        /* WPML Fix */
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ){
            global $sitepress;
            $url = $sitepress->convert_url( $url, $sitepress->get_default_language() );
        }
        /* WPML Fix */
        require_once FRAMEWORK_PATH . '/includes/aq-resizer.php';
        $aq_resize = WPCOM_Resize::getInstance();
        return $aq_resize->process( $url, $width, $height, $crop, $img_id, $size, $single, $upscale );
    }

    public static function thumbnail_url($post_id='', $size='full', $local=false){
        global $options;

        $post = $post_id ? get_post($post_id) : get_post();
        if(!$post_id) $post_id = $post && isset($post->ID) && $post->ID ? $post->ID : '';

        $img = get_the_post_thumbnail_url($post_id, $size);
        if( $post_id && $post && !$img ){
            // 如果文章不存在或者是未发布状态，则直接返回不进行接下来的处理
            if($post->post_status !== 'publish') return $img;

            $auto_featured = isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1';
            $auto_thumb = !$auto_featured && (!isset($options['auto_get_thumb']) || (isset($options['auto_get_thumb']) && $options['auto_get_thumb'] == '1'));
            if($auto_featured || $auto_thumb){
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
                if(isset($matches[1]) && isset($matches[1][0])) { // 文章有图片
                    $img = $matches[1][0];
                    if(current_user_can( 'manage_options' ) && $auto_featured){
                        $img = self::save_remote_img($img, $post);
                        if (is_array($img) && isset($img['id'])) {
                            $post_thumbnail_id = $img['id'];
                            $img = $img['url'];
                        }
                        if (!isset($post_thumbnail_id)) $post_thumbnail_id = self::get_attachment_id($img);
                        if (isset($post_thumbnail_id) && $post_thumbnail_id) set_post_thumbnail($post_id, $post_thumbnail_id);
                    }
                }
            }

            if($img) {
                if(is_array($size) && count($size) === 2){
                    $width = $size[0];
                    $height = $size[1];
                }else if($size){
                    $image_sizes = apply_filters('wpcom_image_sizes', []);
                    $width = isset($image_sizes[$size]) && isset($image_sizes[$size]['width']) && $image_sizes[$size]['width'] ? $image_sizes[$size]['width'] : 0;
                    $height = isset($image_sizes[$size]) && isset($image_sizes[$size]['height']) && $image_sizes[$size]['height'] ? $image_sizes[$size]['height'] : 0;
                }
                if(isset($width) && $width && isset($height)){
                    $image = self::thumbnail($img, $width, $height, true, isset($post_thumbnail_id) ? $post_thumbnail_id : 0, $size);
                    if(isset($image[0])) {
                        $img = $image[0];
                    } else if($local) {
                        $img = '';
                    }
                }
            }
        }
        return $img;
    }

    public static function thumbnail_html($html, $post_id, $post_thumbnail_id, $size){
        global $options;
        $img_url = '';
        if( !$post_thumbnail_id ) $img_url = self::thumbnail_url($post_id, $size, true);
        $img_url = apply_filters('wpcom_thumbnail_url', $img_url, $post_id, $post_thumbnail_id, $size);
        if($img_url) {
            if(is_array($size) && count($size) === 2){
                $width = $size[0];
                $height = $size[1];
            }else if($size){
                $image_sizes = apply_filters('wpcom_image_sizes', []);
                $width = isset($image_sizes[$size]) && isset($image_sizes[$size]['width']) && $image_sizes[$size]['width'] ? $image_sizes[$size]['width'] : 480;
                $height = isset($image_sizes[$size]) && isset($image_sizes[$size]['height']) && $image_sizes[$size]['height'] ? $image_sizes[$size]['height'] : 0;
            }
            if( !self::is_spider() && (!isset($options['thumb_img_lazyload']) || $options['thumb_img_lazyload']=='1') && !is_embed()) { // 非蜘蛛，并且开启了延迟加载
                $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? (is_numeric($options['lazyload_img']) ? self::get_image_url($options['lazyload_img']) : $options['lazyload_img']) : FRAMEWORK_URI.'/assets/images/lazy.png';
                $lazy = self::thumbnail($lazy_img, $width, $height, true, 0, $size);
                if($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
                $html = '<img class="j-lazy" src="'.$lazy_img.'" data-original="' . $img_url . '" width="' . $width . '" height="' . $height . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
            } else {
                $html = '<img src="' . $img_url . '" width="' . $width . '" height="' . $height . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
            }
        }
        return $html;
    }

    public static function thumbnail_src($image, $attachment_id, $size, $icon){
        // 排除后台的ajax请求
        if( wp_doing_ajax() && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
            return $image;
        }

        // 如采用阿里云oss、腾讯云、七牛图片处理缩略图则直接返回
        if( isset($image[0]) && (preg_match( '/\?x-oss-process=/i', $image[0]) || preg_match( '/\?imageView2\//i', $image[0])) ){
            return $image;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', []);
        $res_image = '';

        if( is_array($size) ) {
            foreach ($image_sizes as $key => $sizes) {
                if ($sizes['width'] == $size[0] && $sizes['height'] == $size[1]) {
                    $size = $key;
                }
            }
        }

        if( !is_array($size) && isset($image_sizes[$size]) && !(is_admin() && !wp_doing_ajax() && !defined('IFRAME_REQUEST')) ){
            $img_url = self::get_image_url($attachment_id);
            $res_image = self::thumbnail($img_url, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, $attachment_id, $size);
            // 裁剪失败，则返回原数据
            if( isset($res_image[0]) && $res_image[0]==$img_url ) $res_image = $image;
        }
        return $res_image ?: $image;
    }

    public static function thumbnail_attr($attr, $attachment, $size){
        global $options, $post;

        if( self::is_spider() || (isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='0') ) {
            $attr['alt'] = isset($post->post_title) && $post->post_title ? $post->post_title : $attachment->post_title;
            return $attr;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', []);
        if( (!is_admin() || wp_doing_ajax()) && !is_embed() ) {
            // 排除后台的ajax请求
            if( wp_doing_ajax() && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
                return $attr;
            }

            $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? (is_numeric($options['lazyload_img']) ? self::get_image_url($options['lazyload_img']) : $options['lazyload_img']) : FRAMEWORK_URI . '/assets/images/lazy.png';
            if( !is_array($size) && isset($image_sizes[$size]) ) {
                $lazy = self::thumbnail($lazy_img, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, 0, $size);
                if ($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
            }
            $attr['data-original'] = $attr['src'];
            $attr['src'] = $lazy_img;
            $attr['class'] .= ' j-lazy';
            if(isset($attr['loading'])) unset($attr['loading']);
            if(!isset($attr['alt']) || !$attr['alt']) $attr['alt'] = isset($post->post_title) ? $post->post_title : $attachment->post_title;
        }
        return $attr;
    }

    public static function check_post_images( $new_status, $old_status, $_post ){
        global $wpcom_panel, $post;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            global $options, $wpdb;
            if ($new_status != 'publish') return false;
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
            if (wp_doing_ajax() && !(isset($_POST['action']) && $_POST['action'] === 'inline-save')) return false;
            if (!isset($post->ID)) $post = $_post;
            // 需要考虑投稿页面的情况
            if ($post->ID !== $_post->ID && !isset($_POST['wpcom_update_post_nonce'])) return false;

            // post 文章类型检查缩略图
            if ( (!isset($options['save_remote_img']) || $options['save_remote_img'] == '0') &&
                isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1' &&
                $_post->post_type == 'post') {
                $post_thumbnail_id = get_post_meta($_post->ID, '_thumbnail_id', true);
                if (!$post_thumbnail_id) {
                    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $_post->post_content, $matches);
                    if (isset($matches[1]) && isset($matches[1][0])) {
                        $img_url = $matches[1][0];
                        self::save_remote_img($img_url, $_post);
                    }
                }
            } else if (isset($options['save_remote_img']) && $options['save_remote_img'] == '1') {
                set_time_limit(0);
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $_post->post_content, $matches);

                $search = [];
                $replace = [];
                if (isset($matches[1]) && isset($matches[1][0])) {
                    $feature = 0;
                    $post_thumbnail_id = get_post_meta($_post->ID, '_thumbnail_id', true);

                    // 文章无特色图片，并开启了自动特色图片
                    if ($_post->post_type == 'post' && !$post_thumbnail_id && isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1') $feature = 1;

                    // 去重
                    $image_list = [];
                    foreach ($matches[1] as $item) {
                        if (!in_array($item, $image_list)) array_push($image_list, $item);
                    }

                    $i = 0;
                    foreach ($image_list as $img) {
                        $img_url = self::save_remote_img($img, $_post, $i == 0 && $feature);
                        $is_except = 0;

                        if( $i == 0 && $feature && isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){ // 第一张是白名单图片的话可以不用替换原文的图片地址
                            $excepts = explode("\r\n", trim($options['remote_img_except']) );
                            if( $excepts ) {
                                foreach ($excepts as $except) {
                                    if (trim($except) && false !== stripos($img, trim($except))) {
                                        $is_except = 1;
                                        break;
                                    }
                                }
                            }
                        }

                        if (!$is_except && is_array($img_url) && isset($img_url['id'])) {
                            array_push($search, $img);
                            array_push($replace, $img_url['url']);
                        }
                        $i++;
                    }

                    if ($search) {
                        $_post->post_content = str_replace($search, $replace, $_post->post_content);
                        // wp_update_post(array('ID' => $_post->ID, 'post_content' => $_post->post_content));
                        // wp_update_post会重复触发 transition_post_status hook
                        $data = ['post_content' => $_post->post_content];
                        $data = wp_unslash($data);
                        $wpdb->update($wpdb->posts, $data, ['ID' => $_post->ID]);
                        clean_post_cache( $_post->ID );
                    }
                }
            }
        }
    }

    public static function save_remote_img($img_url, $post=null, $feature = 1){
        if( $feature==0 ){ // 非特色图片的时候，需要另外判断白名单
            global $options;
            if( isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){
                $excepts = explode("\r\n", trim($options['remote_img_except']) );
                if($excepts) {
                    foreach ($excepts as $except) {
                        if (trim($except) && false !== stripos($img_url, trim($except))) {
                            return $img_url;
                        }
                    }
                }
            }
        }

        $upload_info = wp_upload_dir();
        $upload_url = $upload_info['baseurl'];

        $http_prefix = "http://";
        $https_prefix = "https://";
        $relative_prefix = "//"; // The protocol-relative URL

        if(preg_match('/^\/[^\/].*/i', $img_url) && preg_match('/(http:|https:)\/\/([^\/]+)/i', get_bloginfo('url'), $matches)){
            if($matches && $matches[0]){
                $img_url = $matches[0] . $img_url;
            }
        }

        /* if the $url scheme differs from $upload_url scheme, make them match
           if the schemes differe, images don't show up. */
        if(!strncmp($img_url, $https_prefix,strlen($https_prefix))){ //if url begins with https:// make $upload_url begin with https:// as well
            $upload_url = str_replace($http_prefix, $https_prefix, $upload_url);
        }elseif(!strncmp($img_url, $http_prefix, strlen($http_prefix))){ //if url begins with http:// make $upload_url begin with http:// as well
            $upload_url = str_replace($https_prefix, $http_prefix, $upload_url);
        }elseif(!strncmp($img_url, $relative_prefix, strlen($relative_prefix))){ //if url begins with // make $upload_url begin with // as well
            $upload_url = str_replace([ 0 => "$http_prefix", 1 => "$https_prefix" ], $relative_prefix, $upload_url);
        }

        // Check if $img_url is local.
        if ( false === strpos( $img_url, $upload_url ) ){ // 外链图片
            //Fetch and Store the Image
            $http_options = [
                'timeout' => 15,
                'sslverify' => false,
                'user-agent' => self::request_ua(),
                'headers' => [ 'Referer' => $img_url ]
            ];

            if( preg_match('/\/\/mmbiz\.qlogo\.cn/i', $img_url) || preg_match('/\/\/mmbiz\.qpic\.cn/i', $img_url) ){ // 微信公众号图片，webp格式图片处理
                $urlarr = parse_url( $img_url );
                if( isset($urlarr['query']) ) parse_str($urlarr['query'],$parr);
                if( isset($parr['wx_fmt']) ) $img_url = str_replace('tp=webp', 'tp='.$parr['wx_fmt'], $img_url);
            }

            if(preg_match('/^\/\//i', $img_url)) $img_url = 'http:' . $img_url;
            $img_url =  wp_specialchars_decode($img_url);
            $get = wp_remote_head( $img_url, $http_options );
            $response_code = wp_remote_retrieve_response_code ( $get );

            // 处理 301 / 302 状态
            if(($response_code == '301' || $response_code == '302') && $location = wp_remote_retrieve_header($get,'location')){
                $get = wp_remote_head( $location, $http_options );
                $response_code = wp_remote_retrieve_response_code ( $get );
            }

            if (200 == $response_code) { // 图片状态需为 200
                $type = strtolower($get['headers']['content-type']);

                // content-type 可能多个，一般分号分隔
                $types = $type ? explode(';', $type) : [];
                if(count($types) === 1){
                    $type = trim($types[0]);
                }else if(count($types) > 1){
                    foreach ($types as $t){
                        if(preg_match( '/^image\//', trim($t)) || preg_match( '/^application\/octet-stream/', trim($t))){
                            $type = $t;
                            break;
                        }
                    }
                }

                $mime_to_ext = [
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/bmp' => 'bmp',
                    'image/tiff' => 'tif',
                    'image/webp' => 'webp'
                ];

                $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

                if( $type == 'application/octet-stream' ){
                    $parse_url = parse_url($img_url);
                    $file_ext = pathinfo($parse_url['path'], PATHINFO_EXTENSION);
                    if($file_ext){
                        foreach ($mime_to_ext as $key => $value) {
                            if(strtolower($file_ext)==$value){
                                $type = $key;
                                break;
                            }
                        }
                    }
                }

                $allowed_filetype = ['jpg', 'gif', 'png', 'bmp', 'webp'];

                if (in_array ( $file_ext, $allowed_filetype )) { // 仅保存图片格式 'jpg','gif','png', 'bmp', 'webp'
                    $http = wp_remote_get ( $img_url, $http_options );
                    if (!is_wp_error ( $http ) && 200 === $http ['response'] ['code']) { // 请求成功
                        $filename = rawurldecode(wp_basename(parse_url($img_url,PHP_URL_PATH)));
                        $ext = substr(strrchr($filename, '.'), 1);
                        $filename = wp_basename($filename, "." . $ext) . '.' . $file_ext;
                        // 检测文件是否需要重命名
                        $_file = wpcom_file_upload_rename(['name' => $filename]);
                        $new_name = $_file && isset($_file['name']) ? $_file['name'] : $filename;
                        $time = $post ? date('Y/m', strtotime($post->post_date)) : date('Y/m');
                        $mirror = wp_upload_bits($new_name, '', $http ['body'], $time);

                        if(!isset($mirror['file'])) return $img_url;

                        // 保存到媒体库
                        $attachment = [
                            'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
                            'post_mime_type' => $type,
                            'guid' => $mirror['url']
                        ];

                        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post?$post->ID:0);

                        if($attach_id) {
                            $attach_data = self::generate_attachment_metadata($attach_id, $mirror['file']);
                            wp_update_attachment_metadata($attach_id, $attach_data);

                            if ($post && $feature) {
                                // 设置文章特色图片
                                set_post_thumbnail($post->ID, $attach_id);
                            }

                            $img_url = [
                                'id' => $attach_id,
                                'url' => $mirror['url']
                            ];
                        }else{ // 保存到数据库失败，则删除图片
                            @unlink($mirror['file']);
                        }
                    }
                }
            }
        }

        return $img_url;
    }

    public static function get_attachment_id( $url ) {
        $attachment_id = 0;
        $dir = wp_upload_dir();
        if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
            $file = wp_basename( parse_url($url, PHP_URL_PATH) );
            $query_args = [
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'no_found_rows' => 1,
                'meta_query'  => [
                    [
                        'value'   => $file,
                        'compare' => 'LIKE',
                        'key'     => '_wp_attachment_metadata',
                    ],
                ]
            ];
            $query = new WP_Query( $query_args );
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $meta = wp_get_attachment_metadata( $post_id );
                    $original_file       = isset($meta['file']) ? basename( $meta['file'] ) : '';
                    $cropped_image_files = isset($meta['sizes']) ? wp_list_pluck( $meta['sizes'], 'file' ) : [];
                    if ( $original_file === $file || ($cropped_image_files && in_array( $file, $cropped_image_files )) ) {
                        $attachment_id = $post_id;
                        break;
                    }
                }
            }
        }
        return $attachment_id;
    }

    public static function generate_attachment_metadata($attachment_id, $file) {
        $attachment = get_post ( $attachment_id );
        $metadata = array ();
        if (!function_exists('file_is_displayable_image')) include( ABSPATH . 'wp-admin/includes/image.php' );
        if (preg_match ( '!^image/!', get_post_mime_type ( $attachment ) ) && file_is_displayable_image ( $file )) {
            $imagesize = getimagesize ( $file );
            $metadata ['width'] = $imagesize [0];
            $metadata ['height'] = $imagesize [1];

            // Make the file path relative to the upload dir
            $metadata ['file'] = _wp_relative_upload_path ( $file );

            // Fetch additional metadata from EXIF/IPTC.
            $image_meta = wp_read_image_metadata( $file );
            if ( $image_meta )
                $metadata['image_meta'] = $image_meta;

            // 基于wp_generate_attachment_metadata钩子，兼容云储存插件同步
            $metadata = apply_filters ( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );
        }
        return $metadata;
    }

    public static function get_image_url($attachment_id = 0){
        global $pagenow;
        $attachment_id = (int) $attachment_id;
        // Get attached file.
        if ( $attachment_id && $file = get_post_meta( $attachment_id, '_wp_attached_file', true ) ) {
            // Get upload directory.
            $uploads = wp_get_upload_dir();
            if ( $uploads && false === $uploads['error'] ) {
                // Check that the upload base exists in the file location.
                if ( 0 === strpos( $file, $uploads['basedir'] ) ) {
                    // Replace file location with url location.
                    $url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
                } elseif ( false !== strpos( $file, 'wp-content/uploads' ) ) {
                    // Get the directory name relative to the basedir (back compat for pre-2.7 uploads).
                    $url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $file ) ) . wp_basename( $file );
                } else {
                    // It's a newly-uploaded file, therefore $file is relative to the basedir.
                    $url = $uploads['baseurl'] . "/$file";
                }
            }
        }

        if ( !isset($url) || !$url ) $url = get_the_guid( $attachment_id );
        if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow && $url) $url = set_url_scheme( $url );
        $url = apply_filters( 'wp_get_attachment_url', $url, $attachment_id );

        if ( !$url ) return false;
        return $url;
    }

    public static function wxmp_token($appid, $appsecret) {
        $wxmp_token = is_multisite() ? get_network_option( get_main_network_id(), 'wxmp_token' ) : get_option('wxmp_token');
        if($wxmp_token && isset($wxmp_token['access_token']) && $wxmp_token['access_token'] && $wxmp_token['appid'] === $appid && $wxmp_token['expires_in'] > time()+60){
            return $wxmp_token['access_token'];
        }else{
            $access_token = apply_filters('wpcom_wxmp_token', '', $appid, $appsecret);
            if($access_token && isset($access_token['access_token'])) {
                $res = $access_token;
            }else{
                $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
                $result = wp_remote_request($url, ['method' => 'get']);
                if(is_array($result) && isset($result['body'])){
                    $res = json_decode($result['body'], true);
                }
            }

            if(isset($res) && isset($res['access_token'])){
                $res['appid'] = $appid;
                $res['expires_in'] = time() + $res['expires_in'];
                if(is_multisite()) {
                    update_network_option( get_main_network_id(), 'wxmp_token', $res );
                } else {
                    update_option('wxmp_token', $res);
                }
                return $res['access_token'];
            }
            return '';
        }
    }

    public static function reg_module( $module ){
        add_action('wpcom_modules_'.$module, 'wpcom_modules_'.$module, 10, 2);
        add_filter('wpcom_modules', 'wpcom_'.$module);
    }

    public static function color( $color, $rgb = false ){
        if($rgb && preg_match('/^rgba\(([\d\s]+),([\d\s]+),([\d\s]+),([\d\s\.]+)\)/i', trim($color), $matches)){
            $color = [
                'r' => $matches[1],
                'g' => $matches[2],
                'b' => $matches[3],
                'a' => $matches[4]
            ];
            return $color;
        }
        if(preg_match('/^\{"c1":"[^\}]+\}$/i', trim($color), $matches)){
            $color = json_decode($color);
            $color = $color && isset($color) ? $color->c1 : '';
        }
        if($rgb){
            $color = str_replace('#', '', $color);
            if (strlen($color) > 3) {
                $rgb = [
                    'r' => hexdec(substr($color, 0, 2)),
                    'g' => hexdec(substr($color, 2, 2)),
                    'b' => hexdec(substr($color, 4, 2)),
                    'a' => 1
                ];
            } else {
                $r = substr($color, 0, 1) . substr($color, 0, 1);
                $g = substr($color, 1, 1) . substr($color, 1, 1);
                $b = substr($color, 2, 1) . substr($color, 2, 1);
                $rgb = [
                    'r' => hexdec($r),
                    'g' => hexdec($g),
                    'b' => hexdec($b)
                ];
            }
            return $rgb;
        }else{
            if(strlen($color) && substr($color, 0, 1) !== '#' && substr($color, 0, 3) !== 'rgb'){
                $color = '#'.$color;
            }
            return $color;
        }
    }

    public static function gradient_color($str, $array = false){
        $res = $array ? [] : '';
        if($str && $color = json_decode($str)){
            $type = $color->d == 4 ? 'radial' : 'linear';
            $angle = '';
            switch ($color->d) {
                default:
                case 0:
                    $angle = '90deg, ';
                    break;
                case 1:
                    $angle = '180deg, ';
                    break;
                case 2:
                    $angle = '45deg, ';
                    break;
                case 3:
                    $angle = '135deg, ';
                    break;
                case 4:
                    break;
            }

            $val = $type . '-gradient(' . $angle . self::color($color->c1 ? $color->c1 : '#fff') . ' 0%, ' . self::color($color->c2 ? $color->c2 : '#fff') . ' 100%)';

            if($array){
                $res['color'] = self::color($color->c1);
                $res['image'] = $val;
            }else{
                $res = 'background-color: ' . self::color($color->c1) . ';';
                $res .= 'background-image: ' . $val . ';';
            }
        }else{
            if($array){
                $res['color'] = self::color($str);
            }else{
                $res = 'background-color: ' . self::color($str) . ';';
            }
        }

        return $res;
    }

    public static function trbl($value, $name='margin', $use=''){
        $_value = $value!=='' ? preg_split('/\s+/', $value) : '';
        if($value!=='' && is_array($_value) && count($_value)){
            $use = $use ? $use : 'trbl';
            if($use==='trbl'){
                return $name . ': '.$value.';';
            }else if($use==='tb' && isset($_value[2])){
                return $name . '-top: ' . $_value[0] . ';' . $name . '-bottom: ' . $_value[2] . ';';
            }else if($use==='tb'){
                return $name . '-top: ' . $_value[0] . ';' . $name . '-bottom: ' . (isset($_value[1]) ? $_value[1] : $_value[0]) . ';';
            }else if($use==='rl' && isset($_value[3])){
                return $name . '-right: ' . $_value[1] . ';' . $name . '-left: ' . $_value[3] . ';';
            }else if($use==='rl'){
                return $name . '-right: ' . $_value[0] . ';' . $name . '-left: ' . (isset($_value[1]) ? $_value[1] : $_value[0]) . ';';
            }
        }
        return $value;
    }

    public static function url($value, $esc = true){
        if($value){
            $value = explode(', ', $value);
            $_url = $value && isset($value[0]) && $value[0] ? $value[0] : '';
            $_url = !$esc || preg_match('/^javascript:/i', $_url) ? $_url : esc_url($_url);
            $url = 'href="' . $_url . '"';
            $target = $value && isset($value[1]) && $value[1]==='_blank' ? ' target="_blank"' : '';
            $nofollow = $value && ( (isset($value[1]) && $value[1] === 'nofollow') || (isset($value[2]) && $value[2] === 'nofollow')) ? ' rel="nofollow"' : '';
            if($url) return $url . $target . $nofollow;
        }
    }

    public static function icon($name, $echo = true, $class = '', $alt = 'icon'){
        $name = sanitize_text_field($name);
        $class = esc_attr($class);
        $_name = explode(':', $name);
        switch ($_name[0]){
            case 'mti':
                $name = preg_replace('/^mti:/i', '', $name);
                $str = '<i class="wpcom-icon material-icons' . ($class ? ' ' . $class : '') . '">' . $name . '</i>';
                break;
            case 'if':
                $name = preg_replace('/^if:/i', '', $name);
                $str = '<i class="wpcom-icon' . ($class ? ' ' . $class : '') . '"><svg aria-hidden="true"><use xlink:href="#icon-' . esc_attr($name). '"></use></svg></i>';
                break;
            case 'fa':
                $name = preg_replace('/^fa:/i', '', $name);
                $str = '<i class="wpcom-icon fa fa-' . $name . ($class ? ' ' . $class : '') . '"></i>';
                break;
            case 'ri':
                $name = preg_replace('/^ri:/i', '', $name);
                $str = '<i class="wpcom-icon ri-' . $name . ($class ? ' ' . $class : '') . '"></i>';
                break;
            case 'http':
            case 'https':
                $str = '<i class="wpcom-icon' . ($class ? ' ' . $class : '') . '"><img class="j-lazy" src="' . esc_url($name) . '" alt="' . esc_attr($alt) . '" /></i>';
                break;
            default:
                if(preg_match('/^\/\//', $name)){ // "//"开头的地址需要单独匹配
                    $str = '<i class="wpcom-icon' . ($class ? ' ' . $class : '') . '"><img class="j-lazy" src="' . esc_url($name) . '" alt="' . esc_attr($alt) . '" /></i>';
                }else{
                    $str = '<i class="wpcom-icon wi' . ($class ? ' ' . $class : '') . '"><svg aria-hidden="true"><use xlink:href="#wi-' . esc_attr($name) . '"></use></svg></i>';
                }
        }

        if($echo) {
            echo $str;
        } else {
            return $str;
        }
    }

    public static function get_webp_url($url){
        global $options;
        $webp = isset($options['webp_suffix']) && $options['webp_suffix'] ? $options['webp_suffix'] : '';
        if($webp && $url){
            if( $url && preg_match('/\?/', $url) ){ // 有参数
                if( preg_match('/([&?]+)x-oss-process=/i', $url) ){ //阿里云
                    $url = preg_replace('/([&?]+)x-oss-process=/i', "$1x-oss-process=image/format,webp,", $url);
                }else if( preg_match('/([&?]+)imageMogr2/i', $url) ){ // 七牛、腾讯云cos
                    $url = preg_replace('/([&?]+)imageMogr2\//i', "$1imageMogr2/format/webp/", $url);
                }else{
                    $url = $url . str_replace('?', '&', $webp);
                }
            }else if($url){
                $url = $url . $webp;
            }
            return $url;
        }
    }

    public static function shortcode_render(){
        $shortcodes = ['btn', 'gird', 'icon', 'alert', 'panel', 'tabs', 'accordion', 'map'];
        foreach($shortcodes as $sc){
            add_shortcode($sc, 'wpcom_sc_'.$sc);
        }
    }

    public static function is_spider() {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $spiders = [
            'Googlebot', // Google
            'Baiduspider', // 百度
            '360Spider', // 360
            'bingbot', // Bing
            'Sogou web spider' // 搜狗
        ];

        foreach ($spiders as $spider) {
            $spider = strtolower($spider);
            //查找有没有出现过
            if (strpos($userAgent, $spider) !== false) {
                return $spider;
            }
        }
    }

    public static function meta_filter( $res, $object_id, $meta_key, $single, $meta_type){
        global $wpdb;
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        $filter = current_filter();
        if ( $key !== $meta_key ) {
            $metas_key = '_wpcom_metas';
            if( $filter === 'get_user_metadata' ) $metas_key = $wpdb->get_blog_prefix() . '_wpcom_metas';

            // 排除字段直接读取
            $exclude = apply_filters("wpcom_exclude_{$meta_type}_metas", []);
            if(in_array($key, $exclude)) {
                $meta_cache = wp_cache_get( $object_id,  $meta_type . '_meta' );
                if ( ! $meta_cache ) {
                    $meta_cache = update_meta_cache( $meta_type, [ $object_id ] );
                    $meta_cache = $meta_cache[ $object_id ];
                }
                if ( isset( $meta_cache[ $meta_key ] ) ) {
                    if ( $single ) {
                        return maybe_unserialize( $meta_cache[ $meta_key ][0] );
                    } else {
                        return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
                    }
                }
            }

            $metas = call_user_func("get_{$meta_type}_meta", $object_id, $metas_key, true);

            if( isset($metas) && isset($metas[$key]) ) {
                if(in_array($key, $exclude)) {
                    add_metadata($meta_type, $object_id, $meta_key, $metas[$key], $single);
                    unset($metas[$key]);
                }
                if( $single && is_array($metas[$key]) )
                    return [ $metas[$key] ];
                else if( !$single && empty($metas[$key]) )
                    return [];
                else
                    return [ $metas[$key] ];
            }
        } else if($meta_key === '_page_modules' && !$res && $filter === 'get_post_metadata') {
            $meta_cache = wp_cache_get( $object_id,  $meta_type . '_meta' );
            if ( ! $meta_cache ) {
                $meta_cache = update_meta_cache( $meta_type, [ $object_id ] );
                $meta_cache = $meta_cache[ $object_id ];
            }
            if ( isset( $meta_cache[ $meta_key ] ) ) {
                $_res = maybe_unserialize( $meta_cache[ $meta_key ][0] );
                $res = '';
                if($_res && is_string($_res)) {
                    $res = json_decode($_res, true);
                    $res = $res ?: json_decode(wp_unslash($_res), true);
                }
                if($res) $res = self::reset_module_value($res);
                $res = [$res];
            }
        }else if(($meta_key === '_wpcom_metas' || ($filter === 'get_user_metadata' && $meta_key === $wpdb->get_blog_prefix() . '_wpcom_metas')) && !$res){
            $meta_cache = wp_cache_get( $object_id,  $meta_type . '_meta' );
            if ( ! $meta_cache ) {
                $meta_cache = update_meta_cache( $meta_type, [ $object_id ] );
                $meta_cache = $meta_cache[ $object_id ];
            }
            if ( isset( $meta_cache[ $meta_key ] ) ) {
                $_res = maybe_unserialize( $meta_cache[ $meta_key ][0] );
                if($_res && is_string($_res)) {
                    $__res = json_decode($_res, true);
                    $_res = $__res === null ? json_decode(wp_unslash($_res), true) : $__res;
                }
                if(is_array($_res)) $res = [$_res];
            }
        }
        return $res;
    }

    private static function reset_module_value($modules){
        $_modules = [];
        if($modules) {
            foreach ($modules as $i => $module) {
                if (isset($module['settings']['margin-top']) && isset($module['settings']['margin-bottom'])) {
                    $module['settings']['margin'] = $module['settings']['margin-top'] . ' 0 ' . $module['settings']['margin-bottom'] . ' 0';
                    unset($module['settings']['margin-top']);
                    unset($module['settings']['margin-bottom']);
                }
                if (isset($module['settings']['padding-top']) && isset($module['settings']['padding-bottom'])) {
                    $module['settings']['padding'] = $module['settings']['padding-top'] . ' 0 ' . $module['settings']['padding-bottom'] . ' 0';
                    unset($module['settings']['padding-top']);
                    unset($module['settings']['padding-bottom']);
                }
                if(isset($module['settings']['modules']) && $module['settings']['modules']){
                    $module['settings']['modules'] = self::reset_module_value($module['settings']['modules']);
                }else if(isset($module['settings']['girds']) && $module['settings']['girds']){
                    foreach ($module['settings']['girds'] as $x => $gird){
                        $module['settings']['girds'][$x] = self::reset_module_value($gird);
                    }
                }
                $_modules[$i] = $module;
            }
        }
        return $_modules;
    }

    public static function add_metadata($check, $object_id, $meta_key, $meta_value){
        global $wpdb;
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        if ( $key !== $meta_key || (('_wpcom_metas' === $meta_key || $meta_key === $wpdb->get_blog_prefix() . '_wpcom_metas') && is_array($meta_value)) ) {
            $filter = current_filter();
            $pre_key = '_wpcom_metas';
            if( $filter === 'add_post_metadata' || $filter === 'update_post_metadata' ){
                $meta_type = 'post';
            }else if( $filter === 'add_term_metadata' || $filter === 'update_term_metadata' ){
                $meta_type = 'term';
            }else{
                $pre_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
                $meta_type = 'user';
            }
        }
        if ( $key !== $meta_key ) {
            $exclude = apply_filters("wpcom_exclude_{$meta_type}_metas", []);
            if(in_array($key, $exclude)) return $check;

            $metas = call_user_func("get_{$meta_type}_meta", $object_id, $pre_key, true);
            $pre_value = '';
            if( $metas ) {
                if( isset($metas[$key]) ) $pre_value = $metas[$key];
                $metas[$key] = $meta_value;
            } else {
                $metas = [
                    $key => $meta_value
                ];
            }

            if($meta_value === '') unset($metas[$key]);

            $_metas = wp_json_encode($metas, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $result = self::update_metadata($meta_type, $object_id, $pre_key, $_metas);

            if( $result && $meta_value !== $pre_value && ($filter === 'add_user_metadata' || $filter === 'update_user_metadata') ) {
                do_action( 'wpcom_user_meta_updated', $object_id, $meta_key, $meta_value, $pre_value );
            }

            if($result) {
                wp_cache_delete($object_id, $meta_type . '_meta');
                return true;
            }
        }else if(('_wpcom_metas' === $meta_key || $meta_key === $wpdb->get_blog_prefix() . '_wpcom_metas') && is_array($meta_value)){
            if(self::update_metadata( $meta_type, $object_id, $pre_key, wp_json_encode($meta_value, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) )) return true;
        }
        return $check;
    }

    static function update_metadata($type, $id, $key, $value){
        global $wpdb;
        $table = _get_meta_table($type);
        $column = sanitize_key($type . '_id');
        $value = maybe_serialize($value);
        if( $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
            $key, $id ) ) ){
            $where = [ $column => $id, 'meta_key' => $key ];
            $result = $wpdb->update( $table, ['meta_value' => $value], $where );
        }else{
            $result = $wpdb->insert( $table, [
                $column => $id,
                'meta_key' => $key,
                'meta_value' => $value
            ] );
        }
        if(isset($result)) return $result;
    }

    public static function kses_allowed_html( $html ){
        if(isset($html['img'])){
            $html['img']['data-original'] = 1;
        }
        return $html;
    }
    public static function input_time($time, $format = 'Y-m-d H:i:s'){
        if(empty($time)) return null;
        $time = str_replace('T', ' ', $time);
        if($time && preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $time)){
            $time .= '00:00:00';
        }else if($time && preg_match('/^\d{4}-\d{1,2}-\d{1,2}\s+\d{1,2}\:\d{1,2}$/', $time)){
            $time .= ':00';
        }
        if($format === 'Y-m-d H:i:s') return $time;

        $datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', $time, wp_timezone() );
        if ( false === $datetime ) return null;
        if($format === 'U') return $datetime->getTimestamp();
        return $datetime->format( $format );
    }

    public static function request_ua(){
        return apply_filters('wpcom_request_user_agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36');
    }
}


add_filter( 'post_thumbnail_html', [ 'WPCOM', 'thumbnail_html' ], 10, 4 );
add_filter( 'wp_get_attachment_image_src', [ 'WPCOM', 'thumbnail_src' ], 10, 4 );
add_filter( 'wp_get_attachment_image_attributes', [ 'WPCOM', 'thumbnail_attr' ], 20, 3 );
add_filter( 'wp_kses_allowed_html', [ 'WPCOM', 'kses_allowed_html' ], 20 );

add_action( 'init', [ 'WPCOM', 'shortcode_render' ] );
add_action( 'wp_ajax_wpcom_options', [ 'WPCOM', '_options' ] );
add_action( 'wp_ajax_wpcom_icons', [ 'WPCOM', '_icons' ] );
add_filter( 'wpcom_options_update_output', [ 'WPCOM', 'update_icons' ], 10, 3);
add_filter( 'get_post_metadata', [ 'WPCOM', 'meta_filter' ], 20, 5 );
add_filter( 'add_post_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );
add_filter( 'update_post_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );
add_filter( 'get_user_metadata', [ 'WPCOM', 'meta_filter' ], 20, 5 );
add_filter( 'add_user_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );
add_filter( 'update_user_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );
add_filter( 'get_term_metadata', [ 'WPCOM', 'meta_filter' ], 20, 5 );
add_filter( 'add_term_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );
add_filter( 'update_term_metadata', [ 'WPCOM', 'add_metadata' ], 20, 4 );

add_action( 'transition_post_status', [ 'WPCOM', 'check_post_images' ], 10, 3 );

$tpl_dir = get_template_directory();
$sty_dir = get_stylesheet_directory();

require FRAMEWORK_PATH . '/core/panel.php';
require FRAMEWORK_PATH . '/core/visual-editor.php';
require FRAMEWORK_PATH . '/core/module.php';
require FRAMEWORK_PATH . '/core/widget.php';

if(is_dir($tpl_dir . '/widgets')) WPCOM::load($tpl_dir . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/functions');
WPCOM::load(FRAMEWORK_PATH . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/modules');
WPCOM::load($tpl_dir . '/modules');
if($tpl_dir !== $sty_dir) {
    WPCOM::load($sty_dir . '/modules');
}