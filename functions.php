<?php defined('ABSPATH') || exit;

define( 'THEME_ID', '5b4220be66895b87' ); // 主题ID，请勿修改！！！
define( 'THEME_VERSION', '6.21.1' ); // 主题版本号，请勿修改！！！

// Themer 框架路径信息常量，请勿修改，框架会用到
define( 'FRAMEWORK_PATH', is_dir($framework_path = get_template_directory() . '/themer') ? $framework_path : get_theme_root() . '/Themer/themer' );
define( 'FRAMEWORK_URI', is_dir($framework_path) ? get_template_directory_uri() . '/themer' : get_theme_root_uri() . '/Themer/themer' );

require FRAMEWORK_PATH . '/load.php';

function add_menu(){
    return array(
        'primary'   => '导航菜单',
        'footer'   => '页脚菜单'
    );
}
add_filter('wpcom_menus', 'add_menu');

// sidebar
if ( ! function_exists( 'wpcom_widgets_init' ) ) :
    function wpcom_widgets_init() {
        register_sidebar( array(
            'name'          => '首页边栏',
            'id'            => 'home',
            'description'   => '用于首页显示的边栏',
            'before_widget' => '<div class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>'
        ) );
    }
endif;
add_action( 'wpcom_sidebar', 'wpcom_widgets_init' );

add_filter('wpcom_image_sizes', 'justnews_image_sizes', 20);
function justnews_image_sizes($image_sizes){
    global $options;
    if(!isset($options['thumb_default_width'])){
        $image_sizes['post-thumbnail'] = array(
            'width' => 480,
            'height' => 300
        );
    }
    return $image_sizes;
}

// Excerpt length
if ( ! function_exists( 'wpcom_excerpt_length' ) ) :
    function wpcom_excerpt_length() {
        global $options;
        return isset($options['excerpt_len']) && $options['excerpt_len'] ? $options['excerpt_len'] : 180;
    }
endif;
add_filter( 'excerpt_length', 'wpcom_excerpt_length', 999 );

function wpcom_format_date($time){
    global $options, $post;
    $p_id = isset($post->ID) ? $post->ID : 0;
    $q_id = get_queried_object_id();
    $single = $p_id == $q_id && is_single();
    if(isset($options['time_format']) && $options['time_format']=='0'){
        return date_i18n(get_option('date_format').($single?' '.get_option('time_format'):''), $time);
    }
    $t = current_time('timestamp') - $time;
    $f = array(
        '86400' => 'd',
        '3600' => 'h',
        '60' => 'm',
        '1' => 's'
    );

    if($t==0){
        return __('1 second ago', 'wpcom');
    }else if( $t >= 604800 || $t < 0){
        return date_i18n(get_option('date_format').($single?' '.get_option('time_format'):''), $time);
    }else{
        foreach ($f as $k=>$v)    {
            if (0 !=$c=floor($t/(int)$k)) {
                break;
            }
        }
        $types = array(
            'd' => sprintf( _n( '%s day ago', '%s days ago', $c, 'wpcom' ), $c ),
            'h' => sprintf( _n( '%s hour ago', '%s hours ago', $c, 'wpcom' ), $c ),
            'm' => sprintf( _n( '%s min ago', '%s mins ago', $c, 'wpcom' ), $c ),
            's' => sprintf( _n( '%s second ago', '%s seconds ago', $c, 'wpcom' ), $c ),
        );
        if($v) return $types[$v];
    }
}

add_action('wp_ajax_wpcom_like_it', 'wpcom_like_it');
add_action('wp_ajax_nopriv_wpcom_like_it', 'wpcom_like_it');
function wpcom_like_it(){
    $data = $_POST;
    $res = array();
    if(isset($data['id']) && $data['id'] && $post = get_post(sanitize_text_field($data['id']))){
        $cookie = isset($_COOKIE["wpcom_liked_".$post->ID]) ? wp_unslash($_COOKIE["wpcom_liked_".$post->ID]) : 0;
        if(isset($cookie) && $cookie=='1'){
            $res['result'] = -2;
        }else{
            $res['result'] = 0;
            $likes = get_post_meta($post->ID, 'wpcom_likes', true);
            $likes = $likes ? $likes : 0;
            $res['likes'] = $likes + 1;
            // 数据库增加一个喜欢数量
            update_post_meta( $post->ID, 'wpcom_likes', $res['likes'] );
            //cookie标记已经给本文点赞过了
            setcookie('wpcom_liked_'.$post->ID, 1, time()+3600*24*365, '/');
            do_action('wpcom_liked_post', $post->ID);
        }
    }else{
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_action('wp_ajax_wpcom_heart_it', 'wpcom_heart_it');
add_action('wp_ajax_nopriv_wpcom_heart_it', 'wpcom_heart_it');
function wpcom_heart_it(){
    $data = $_POST;
    $res = array();
    $current_user = wp_get_current_user();
    if($current_user->ID){
        if(isset($data['id']) && $data['id'] && $post = get_post(sanitize_text_field($data['id']))){
            // 用户关注的文章
            $u_favorites = get_user_meta($current_user->ID, 'wpcom_favorites', true);
            $u_favorites = $u_favorites ?: array();
            // 文章关注人数
            $p_favorite = get_post_meta($post->ID, 'wpcom_favorites', true);
            $p_favorite = $p_favorite ?: 0;
            $is_hearted = false;
            if(in_array($post->ID, $u_favorites)){ // 用户是否关注本文
                $res['result'] = 1;
                $nu_favorites = array();
                foreach($u_favorites as $uf){
                    if($uf != $post->ID){
                        $nu_favorites[] = $uf;
                    }
                }
                $p_favorite -= 1;
            }else{
                $res['result'] = 0;
                $u_favorites[] = $post->ID;
                $nu_favorites = $u_favorites;
                $p_favorite += 1;
                $is_hearted = true;
            }
            $p_favorite = $p_favorite<0 ? 0 : $p_favorite;
            update_user_meta($current_user->ID, 'wpcom_favorites', $nu_favorites);
            update_post_meta($post->ID, 'wpcom_favorites', $p_favorite);
            do_action('wpcom_hearted_post', $post->ID, $current_user->ID, $is_hearted);
            $res['favorites'] = $p_favorite;
        }else{
            $res['result'] = -2;
        }
    }else{ // 未登录
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_filter( 'wpcom_profile_tabs_posts_class', 'justnews_profile_posts_class' );
function justnews_profile_posts_class(){
    return 'profile-posts-list post-loop post-loop-default clearfix';
}

add_filter( 'wpcom_profile_tabs', 'wpcom_add_profile_tabs' );
function wpcom_add_profile_tabs( $tabs ){
    $tabs += array(
        30 => array(
            'slug' => 'favorites',
            'title' => __( 'Favorites', 'wpcom' )
        )
    );

    return $tabs;
}

add_action('wpcom_profile_tabs_favorites', 'wpcom_favorites');
function wpcom_favorites() {
    $profile = isset($GLOBALS['profile']) ? $GLOBALS['profile'] : null;
    $favorites = get_user_meta($profile->ID, 'wpcom_favorites', true);

    $empty_icon = wpcom_empty_icon('favorite');

    if($favorites) {
        add_filter('posts_orderby', 'favorites_posts_orderby');
        $args = array(
            'post__in' => $favorites,
            'posts_per_page' => get_option('posts_per_page'),
        );
        $posts = WPCOM::get_posts($args);
        if ( $posts->have_posts() ) {
            echo '<ul class="profile-posts-list profile-favorites-list post-loop post-loop-default clearfix" data-user="'.$profile->ID.'">';
            while ($posts->have_posts()) : $posts->the_post();
                get_template_part('templates/loop', 'default');
            endwhile;
            echo '</ul>';
            if ($posts->have_posts()) { ?>
                <div class="load-more-wrap"><div class="wpcom-btn load-more j-user-favorites"><?php _e('Load more posts', 'wpcom'); ?></div></div><?php }
        } else {
            if (get_current_user_id() == $profile->ID) {
                echo '<div class="profile-no-content">' . $empty_icon . __('You have no favorite posts.', 'wpcom') . '</span></div>';
            } else {
                echo '<div class="profile-no-content">' . $empty_icon . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
            }
        }
        wp_reset_postdata();
    }else{
        if( get_current_user_id()==$profile->ID ) {
            echo '<div class="profile-no-content">' . $empty_icon . __('You have no favorite posts.', 'wpcom') . '</span></div>';
        } else {
            echo '<div class="profile-no-content">' . $empty_icon . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
        }
    }
}

add_action( 'wp_ajax_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
add_action( 'wp_ajax_nopriv_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
function wpcom_profile_tabs_favorites(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', sanitize_text_field($_POST['user']) ) ){
        $favorites = get_user_meta($user->ID, 'wpcom_favorites', true);

        if($favorites) {
            add_filter('posts_orderby', 'favorites_posts_orderby');

            $per_page = get_option('posts_per_page');
            $page = sanitize_text_field($_POST['page']);
            $page = $page ? $page : 1;
            $arg = array(
                'posts_per_page' => $per_page,
                'post__in' => $favorites,
                'paged' => $page
            );
            $posts = WPCOM::get_posts($arg);

            if ($posts->have_posts()) {
                while ($posts->have_posts()) : $posts->the_post();
                    get_template_part('templates/loop', 'default');
                endwhile;
                wp_reset_postdata();
            } else {
                echo 0;
            }
        }
    }
    exit;
}

function favorites_posts_orderby( $orderby ){
    global $wpdb;
    $profile = isset($GLOBALS['profile']) ? $GLOBALS['profile'] : null;
    if( !$profile ) return $orderby;

    $favorites = get_user_meta( $profile->ID, 'wpcom_favorites', true );
    if($favorites) $orderby = "FIELD(" . $wpdb->posts . ".ID, " . implode(',', $favorites) . ") DESC";

    return $orderby;
}

function wpcom_addpost_url(){
    global $options;
    if( isset($options['tougao_page']) && $options['tougao_page'] ){
        return get_permalink( $options['tougao_page'] );
    }
}

function post_editor_settings($args = array()){
    add_filter( 'user_can_richedit' , '__return_true', 100 );
    $img = current_user_can('upload_files');
    return array(
        'textarea_name' => $args['textarea_name'],
        'media_buttons' => false,
        'quicktags' => false,
        'tinymce'       => array(
            'height'        => 420,
            'content_css' => get_template_directory_uri() . '/css/editor-style.css',
            'toolbar1' => 'formatselect,bold,underline,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,'.($img?'wpcomimg,':'image,').'undo,redo,fullscreen,wp_help',
            'toolbar2' => '',
            'toolbar3' => '',
            'external_plugins' => '{wpcomimg: "' . get_template_directory_uri() . '/js/edit-img.js"}'
        )
    );
}

add_filter( 'mce_external_plugins', 'wpcom_mce_plugin');
function wpcom_mce_plugin($plugin_array){
    global $is_submit_page;
    if ( $is_submit_page ) {
        wp_enqueue_media();
        wp_enqueue_script('jquery.taghandler', get_template_directory_uri() . '/js/jquery.taghandler.min.js', array('jquery'), THEME_VERSION, true);
        wp_enqueue_script('edit-post', get_template_directory_uri() . '/js/edit-post.js', array('jquery'), THEME_VERSION, true);
    }
    return $plugin_array;
}

add_action('pre_get_posts', 'wpcom_restrict_media_library');
function wpcom_restrict_media_library( $wp_query_obj ) {
    global $current_user, $pagenow;
    if( ! $current_user instanceof WP_User )
        return;
    if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
        return;
    if( !current_user_can('edit_others_posts') )
        $wp_query_obj->set('author', $current_user->ID );
    return;
}

function wpcom_tougao_tinymce_style($content) {
    if ( ! is_admin() ) {
        global $editor_styles, $stylesheet;
        $editor_styles = (array) $editor_styles;
        $stylesheet    = (array) $stylesheet;
        $stylesheet[] = 'css/editor-style.css';
        $editor_styles = array_merge( $editor_styles, $stylesheet );
    }
    return $content;
}

add_filter('wpcom_update_post','wpcom_update_post');
function wpcom_update_post($res){

    add_filter('the_editor_content', "wpcom_tougao_tinymce_style");

    if(isset($_POST['post-title'])){ // 只处理post请求
        $nonce = $_POST['wpcom_update_post_nonce'];
        if ( wp_verify_nonce( $nonce, 'wpcom_update_post' ) ){
            $post_id = isset($_GET['post_id']) ? $_GET['post_id']:'';

            $post_title = esc_html($_POST['post-title']);
            $post_excerpt = esc_html($_POST['post-excerpt']);
            $post_content = wp_kses_post($_POST['post-content']);
            $post_category = isset($_POST['post-category']) && $_POST['post-category'] ? array_map( 'sanitize_text_field', $_POST['post-category'] ) : array();
            $post_tags = isset($_POST['post-tags']) ? esc_html($_POST['post-tags']) : '';
            $_thumbnail_id = isset($_POST['_thumbnail_id']) ? sanitize_text_field($_POST['_thumbnail_id']) : '';

            if($post_id){ // 编辑文章
                $post = get_post($post_id);
                if(isset($post->ID)) { // 文章要存在
                    $p = array(
                        'ID' => $post_id,
                        'post_type' => 'post',
                        'post_title' => $post_title,
                        'post_excerpt' => $post_excerpt,
                        'post_content' => $post_content,
                        'post_category' => $post_category,
                        'tags_input' => $post_tags
                    );
                    if(($post->post_status=='draft' || $post->post_status=='inherit') && trim($post_title)!='' && trim($post_content)!=''){
                        $p['post_status'] = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                    }
                    $pid = wp_update_post($p, true);
                    if ( !is_wp_error( $pid ) ) {
                        update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    }
                }
            }else{ // 新建文章
                if(trim($post_title)=='' && trim($post_content)==''){
                    return array();
                }else if(trim($post_title)=='' || trim($post_content)=='' || empty($post_category)){
                    $post_status = 'draft';
                }else{
                    $post_status = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                }
                $p = array(
                    'post_type' => 'post',
                    'post_title' => $post_title,
                    'post_excerpt' => $post_excerpt,
                    'post_content' => $post_content,
                    'post_status' => $post_status,
                    'post_category' => $post_category,
                    'tags_input' => $post_tags
                );
                $pid = wp_insert_post($p, true);
                if ( !is_wp_error( $pid ) ) {
                    update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    update_post_meta($pid, 'wpcom_copyright_type', 'copyright_tougao');
                    wp_redirect(get_edit_link($pid).'&submit=true');
                }
            }
        }
    }
    return $res;
}

function wpcom_tougao_notice($post){
    $current_user = wp_get_current_user();

    $notice = '<div class="wpcom-alert alert-success alert-dismissible fade in" role="alert"><div class="wpcom-close" data-wpcom-dismiss="alert" aria-label="Close">' . WPCOM::icon('close', 0) . '</div>';
    $review_text = $post->post_status === 'pending' ? __(', please wait for review', 'wpcom') : '';

    if (current_user_can('edit_post', $post->ID) || $post->post_status === 'publish') {
        $notice .= sprintf(
            __('Submission successful%s! You can <a target="_blank" href="%s">click here</a> to preview or return to <a target="_blank" href="%s">my posts list</a>.', 'wpcom'),
            $review_text,
            get_permalink($post->ID),
            get_author_posts_url($current_user->ID)
        );
    }else{
        $notice .= sprintf(
            __('Submission successful%s! You can return to <a target="_blank" href="%s">my posts list</a>.', 'wpcom'),
            $review_text,
            get_author_posts_url($current_user->ID)
        );
    }

    $notice .= '</div>';

    return apply_filters('wpcom_tougao_notice', $notice, $post);
}

function get_edit_link($id){
    $url = wpcom_addpost_url();
    $url = add_query_arg( 'post_id', $id, $url );
    return $url;
}

add_action('wp_ajax_wpcom_load_posts', 'wpcom_load_posts');
add_action('wp_ajax_nopriv_wpcom_load_posts', 'wpcom_load_posts');
function wpcom_load_posts(){
    global $is_sticky, $wp_posts, $wp_the_query;
    $is_sticky = 1;
    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $page = isset($_POST['page']) ? sanitize_text_field($_POST['page']) : '';
    $page = $page ?: 1;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'default';

    if(isset($_POST['taxonomy']) && $_POST['taxonomy']){ // 列表页面ajax请求
        $per_page = $type === 'default' ? get_option('posts_per_page') : get_option('per_page_for_' . $type);
        $per_page = $type && !$per_page ? get_option('posts_per_page') : $per_page;
        $is_sticky = 0;
    }else{
        $per_page = isset($_POST['per_page']) ? sanitize_text_field($_POST['per_page']) : get_option('posts_per_page');
    }
    if($id){
        $args = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'cat' => $id,
            'ignore_sticky_posts' => 0
        );
        if(isset($_POST['taxonomy']) && $_POST['taxonomy']){
            $args['ignore_sticky_posts'] = 1;
            $args['tax_query'] = array(
                array(
                    'taxonomy' => sanitize_text_field($_POST['taxonomy']),
                    'field'    => 'term_id',
                    'terms'    => $id,
                )
            );
            unset($args['cat']);
        }
        if((isset($_POST['attr']) && $_POST['attr']) || (isset($_POST['order']) && $_POST['order'])){
            if(isset($_POST['order']) && $_POST['order']){
                $_GET['order'] = sanitize_text_field($_POST['order']);
            }
            // $wp_the_query 设置成主循环
            $wp_the_query = new WP_Query;
            $wp_posts = WPCOM::get_posts($args, $wp_the_query);
        }else{
            $wp_posts = WPCOM::get_posts($args);
        }
    }else{
        $exclude = isset($_POST['exclude']) ? sanitize_text_field($_POST['exclude']) : '';
        if($exclude) $exclude = explode(',', $exclude);
        $exclude = $exclude ?: [];
        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'ignore_sticky_posts' => 0,
            'category__not_in' => $exclude
        );
        $wp_posts = WPCOM::get_posts($arg);
    }
    if($wp_posts->have_posts()) {
        while ( $wp_posts->have_posts() ) : $wp_posts->the_post();
            get_template_part('templates/loop', $type);
        endwhile;
        wp_reset_postdata();
        if($id && $page==1 && $wp_posts->have_posts()){
            echo '<div class="load-more-wrap"><div class="wpcom-btn load-more j-load-more" data-id="'.$id.'">'.__('Load more posts', 'wpcom').'</div></div>';
        }
    }else{
        echo 0;
    }
    exit;
}

add_action( 'init', 'wpcom_create_special' );
function wpcom_create_special(){
    global $options, $pagenow, $wp_version;
    if(!isset($options['special_on']) || $options['special_on']=='1' || (isset($_POST['action']) && $_POST['action'] === 'ocdi_import_demo_data')) { //是否开启专题功能
        $slug = isset($options['special_slug']) && $options['special_slug'] ? $options['special_slug'] : 'special';
        $labels = array(
            'name' => '专题',
            'singular_name' => '专题',
            'search_items' => '搜索专题',
            'all_items' => '所有专题',
            'parent_item' => '父级专题',
            'parent_item_colon' => '父级专题',
            'edit_item' => '编辑专题',
            'update_item' => '更新专题',
            'add_new_item' => '添加专题',
            'new_item_name' => '新专题名',
            'not_found' => '暂无专题',
            'menu_name' => '专题',
        );
        $is_hierarchical = $pagenow === 'edit.php' || ($pagenow === 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] === 'inline-save');
        $args = array(
            'hierarchical' => $is_hierarchical || version_compare($wp_version, '5.1', '<') ? true : false,
            'meta_box_cb' => 'post_categories_meta_box',
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $slug, 'with_front' => false),
            'show_in_rest' => true
        );
        register_taxonomy('special', 'post', $args);
    }
}

add_filter('rest_prepare_special', 'wpcom_special_for_editor', 10, 3);
function wpcom_special_for_editor($response, $item, $request){
    if(isset($request['_fields']) && $request['_fields'] && $response->data && !isset($response->data['parent'])){
        $response->data['parent'] = $item->parent;
    }
    return $response;
}

add_filter('rest_prepare_taxonomy', 'wpcom_prepare_special', 10, 3);
function wpcom_prepare_special( $response, $taxonomy, $request ){
    $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
    if( $context === 'edit' && $taxonomy->name == 'special' && $taxonomy->hierarchical === false ){
        $data_response = $response->get_data();
        $data_response['hierarchical'] = true;
        $response->set_data( $data_response );
    }
    return $response;
}

function get_special_list($num=10, $paged=1){
    global $options;
    $args = array(
        'taxonomy' => 'special',
        'orderby' => 'id',
        'order' => 'DESC',
        'number' => $num,
        'hide_empty' => false,
        'offset' => $num*($paged-1)
    );
    if(isset($options['special_order']) && $options['special_order']){
        $orderby = '';
        $order = 'DESC';
        switch ($options['special_order']){
            case '1':
                $order = 'ASC';
                break;
            case '2':
                $orderby = 'count';
                break;
            case '3':
                $orderby = 'last_post';
                break;
            case '0':
            default:
                $orderby = 'id';
                break;
        }
        $args['orderby'] = $orderby;
        $args['order'] = $order;
    }

    $cache_key = md5(maybe_serialize($args)) . ':' . wp_cache_get_last_changed('posts');
    $special = wp_cache_get($cache_key, 'special_terms');

    if($special === false){
        if($args['orderby'] === 'last_post') {
            unset($args['offset']);
            unset($args['number']);
            $terms = wpcom_get_sorted_terms($args);
            $offset = ($paged - 1) * $num;
            $special = array_slice($terms, $offset, $num);
        }else{
            $special = get_terms($args);
        }
        wp_cache_set( $cache_key, $special, 'special_terms');
    }

    return $special;
}

function wpcom_get_sorted_terms( $args = array() ){
    global $wpdb;

    if(isset($args['orderby'])) unset($args['orderby']);
    $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : 'category';
    $terms = get_terms( $args );

    $q = $wpdb->get_results( $wpdb->prepare("SELECT tax.term_id FROM `{$wpdb->prefix}term_taxonomy` tax
    INNER JOIN `{$wpdb->prefix}term_relationships` rel ON rel.term_taxonomy_id = tax.term_id
    INNER JOIN `{$wpdb->prefix}posts` post ON rel.object_id = post.ID WHERE tax.taxonomy = %s AND post.post_type = 'post' AND post.post_status = 'publish' ORDER BY post.post_date DESC", $taxonomy) );

    $sort = array_flip( array_unique( wp_list_pluck( $q, 'term_id' ) ) );

    usort( $terms, function( $a, $b ) use ( $sort, $terms ) {
        if( isset( $sort[ $a->term_id ], $sort[ $b->term_id ] ) && $sort[ $a->term_id ] != $sort[ $b->term_id ] )
            $res = ($sort[ $a->term_id ] > $sort[ $b->term_id ]) ? 1 : -1;
        else if( !isset( $sort[ $a->term_id ] ) && isset( $sort[ $b->term_id ] ) )
            $res = 1;
        else if( isset( $sort[ $a->term_id ] ) && !isset( $sort[ $b->term_id ] ) )
            $res = -1;
        else
            $res = 0;

        return $res;
    } );

    return $terms;
}

// 优化专题排序支持 Simple Custom Post Order 插件
add_filter( 'get_terms_orderby', 'wpcom_get_terms_orderby', 20, 3 );
function wpcom_get_terms_orderby($orderby, $args, $tax){
    if(class_exists('SCPO_Engine') && $tax && count($tax)==1 && $tax[0]=='special'){
        $orderby = 't.term_order, t.term_id';
    }
    return $orderby;
}

add_action('wp_ajax_wpcom_load_special', 'wpcom_load_special');
add_action('wp_ajax_nopriv_wpcom_load_special', 'wpcom_load_special');
function wpcom_load_special(){
    global $options, $post;
    $page = isset($_POST['page']) && $_POST['page'] ? sanitize_text_field($_POST['page']) : 1;
    $per_page = isset($options['special_per_page']) && $options['special_per_page'] ? $options['special_per_page'] : 10;

    $special = get_special_list($per_page, $page);
    if($special){
    foreach($special as $sp){
        $thumb = get_term_meta( $sp->term_id, 'wpcom_thumb', true );
        $link = get_term_link($sp->term_id);
        ?>
        <div class="col-md-12 col-xs-24 special-item-wrap">
            <div class="special-item">
                <div class="special-item-top">
                    <div class="special-item-thumb">
                        <a href="<?php echo $link;?>" target="_blank">
                            <?php echo wpcom_lazyimg($thumb, $sp->name);?>
                        </a>
                    </div>
                    <div class="special-item-info">
                        <div class="special-item-title">
                            <h2><a href="<?php echo $link;?>" target="_blank"><?php echo $sp->name;?></a></h2>
                            <a class="special-item-more" href="<?php echo $link;?>"><?php echo _x('Read More', 'topic', 'wpcom'); WPCOM::icon('arrow-right')?></a>
                        </div>
                        <div class="special-item-desc">
                            <?php echo term_description($sp->term_id, 'special');?>
                        </div>
                        <div class="special-item-meta">
                            <span class="special-item-count"><?php echo sprintf(__('%s posts', 'wpcom'), $sp->count);?></span>
                            <div class="special-item-share">
                                <span class="hidden-xs"><?php _e('Share to: ', 'wpcom');?></span>
                                <?php if(isset($options['post_shares'])){ if($options['post_shares']){ foreach ($options['post_shares'] as $share){ ?>
                                    <a class="share-icon <?php echo $share;?> hidden-xs" target="_blank" data-share="<?php echo $share;?>" data-share-callback="zt_share" rel="noopener noreferrer">
                                        <?php WPCOM::icon($share);?>
                                    </a>
                                <?php } } }else{ ?>
                                    <a class="share-icon wechat hidden-xs" data-share="wechat" data-share-callback="zt_share" rel="noopener noreferrer"><?php WPCOM::icon('wechat');?></a>
                                    <a class="share-icon weibo hidden-xs" target="_blank" data-share="weibo" data-share-callback="zt_share" rel="noopener noreferrer"><?php WPCOM::icon('weibo');?></a>
                                    <a class="share-icon qq hidden-xs" target="_blank" data-share="qq" data-share-callback="zt_share" rel="noopener noreferrer"><?php WPCOM::icon('qq');?></a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="special-item-bottom">
                    <?php
                    $args = array(
                        'posts_per_page' => 3,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'special',
                                'field' => 'term_id',
                                'terms' => $sp->term_id
                            )
                        )
                    );
                    $postslist = get_posts( $args );
                    foreach($postslist as $post){ setup_postdata($post);?>
                        <li><a title="<?php echo esc_attr(get_the_title());?>" href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a></li>
                    <?php } wp_reset_postdata(); ?>
                </ul>
            </div>
        </div>
    <?php }
    } else {
        echo 0;
    }
    exit;
}

function wpcom_post_copyright() {
    global $post, $options;
    $copyright = '';

    $copyright_type = get_post_meta($post->ID, 'wpcom_copyright_type', true);
    if(!$copyright_type){
        $copyright = isset($options['copyright_default']) ? $options['copyright_default'] : '';
    }else if($copyright_type=='copyright_tougao'){
        $copyright = isset($options['copyright_tougao']) ? $options['copyright_tougao'] : '';;
    }else if($copyright_type){
        if(isset($options['copyright_id']) && $options['copyright_id']) {
            foreach ($options['copyright_id'] as $i => $id) {
                if($copyright_type == $id && $options['copyright_text'][$i]) {
                    $copyright = $options['copyright_text'][$i];
                }
            }
        }
    }

    if(preg_match('%SITE_NAME%', $copyright)) $copyright = str_replace('%SITE_NAME%', get_bloginfo('name'), $copyright);
    if(preg_match('%SITE_URL%', $copyright)) $copyright = str_replace('%SITE_URL%', get_bloginfo('url'), $copyright);
    if(preg_match('%POST_TITLE%', $copyright)) $copyright = str_replace('%POST_TITLE%', get_the_title(), $copyright);
    if(preg_match('%POST_URL%', $copyright)) $copyright = str_replace('%POST_URL%', get_permalink(), $copyright);
    if(preg_match('%AUTHOR_NAME%', $copyright)) $copyright = str_replace('%AUTHOR_NAME%', get_the_author(), $copyright);
    if(preg_match('%AUTHOR_URL%', $copyright)) $copyright = str_replace('%AUTHOR_URL%', get_author_posts_url(get_the_author_meta( 'ID' )), $copyright);
    if(preg_match('%ORIGINAL_NAME%', $copyright)) $copyright = str_replace('%ORIGINAL_NAME%', get_post_meta($post->ID, 'wpcom_original_name', true), $copyright);
    if(preg_match('%ORIGINAL_URL%', $copyright)) $copyright = str_replace('%ORIGINAL_URL%', get_post_meta($post->ID, 'wpcom_original_url', true), $copyright);

    $copyright = $copyright ? '<div class="entry-copyright">'.$copyright.'</div>' : '';
    echo apply_filters('wpcom_post_copyright', $copyright);
}

add_filter('comment_reply_link', 'wpcom_comment_reply_link', 10, 1);
function wpcom_comment_reply_link($link){
    if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
        $link = '<a rel="nofollow" class="comment-reply-login" href="javascript:;">'.__( 'Reply' ).'</a>';
    }
    return $link;
}

add_action('init', 'wpcom_allow_contributor_uploads');
function wpcom_allow_contributor_uploads() {
    $user = wp_get_current_user();
    if( isset($user->roles) && $user->roles && isset($user->roles[0]) && $user->roles[0] == 'contributor' ){
        global $options;
        $allow = isset($options['tougao_upload']) && $options['tougao_upload']=='0' ? 0 : 1;
        $can_upload = isset($user->allcaps['upload_files']) ? $user->allcaps['upload_files'] : 0;

        if ( $allow && !$can_upload ) {
            $contributor = get_role('contributor');
            $contributor->add_cap('upload_files');
        } else if(!$allow && $can_upload){
            $contributor = get_role('contributor');
            $contributor->remove_cap('upload_files');
        }
    }
}

add_theme_support( 'wc-product-gallery-lightbox' );

add_action( 'wpcom_echo_ad', 'wpcom_echo_ad', 10, 1);
function wpcom_echo_ad( $id ){
    if($id && $id=='ad_flow'){
        global $wp_query, $wp_posts, $related_posts;
        $query = isset($related_posts) && isset($related_posts->post_count) ? $related_posts : (isset($wp_posts) && isset($wp_posts->post_count) ? $wp_posts : $wp_query);
        if(!isset($query->ad_index)) {
            if($query->post_count) {
                $query->ad_index = rand(1, $query->post_count-2);
            }else if(isset($query->posts->post_count) && $query->posts->post_count){
                $query->ad_index = rand(1, $query->posts->post_count-2);
            }
        }
        $current_post = $query->current_post;
        if(isset($query->posts->current_post)) $current_post = $query->posts->current_post;
        if($current_post==$query->ad_index && $current_post>0) echo wpcom_ad_html($id);
    }else if($id) {
        echo wpcom_ad_html($id);
    }
}

function wpcom_ad_html($id){
    if($id) {
        global $options;
        $html = '';
        $class = str_replace('ad_', '__', $id);
        if( wp_is_mobile() && isset($options[$id.'_mobile']) && $options[$id.'_mobile']!=='' ) {
            if(trim($options[$id.'_mobile'])){
                $html = '<div class="wpcom_myimg_wrap '.esc_attr($class).'">';
                $html .= $options[$id.'_mobile'];
                $html .= '</div>';
            }
        } else if ( isset($options[$id]) && $options[$id] ) {
            $html = '<div class="wpcom_myimg_wrap '.esc_attr($class).'">';
            $html .= $options[$id];
            $html .= '</div>';
        }

        if($html && $id=='ad_flow') {
            if(wp_doing_ajax() && preg_match('/\.baidustatic\.com/i', $html)) return false;
            $html = '<li class="item item-myimg">'.$html.'</li>';
        }
        return $html;
    }
}

add_filter( 'wpcom_custom_css', 'wpcom_style_output' );
if ( ! function_exists( 'wpcom_style_output' ) ) :
    function wpcom_style_output($css){
        global $options;
        if(!isset($options['theme_color'])) return $css;
        $css = $css ?: '';

        $css_var = [
            '--theme-color' => isset($options['theme_color']) && $options['theme_color'] ? WPCOM::color($options['theme_color']) : '',
            '--theme-hover' => isset($options['theme_color_hover']) && $options['theme_color_hover'] ? WPCOM::color($options['theme_color_hover']) : '',
            '--action-color' => isset($options['action_color']) && $options['action_color'] ? $options['action_color'] : '',
            '--logo-height' => isset($options['logo-height']) && intval($options['logo-height']) ? intval($options['logo-height']) . 'px' : '',
            '--logo-height-mobile' => isset($options['logo-height-mobile']) && intval($options['logo-height-mobile']) ? intval($options['logo-height-mobile']) . 'px' : '',
            '--menu-item-gap' => isset($options['menu_item_margin']) && $options['menu_item_margin'] ? $options['menu_item_margin'] : '',
            '--mobile-menu-color' => isset($options['mobile_menu_style']) && $options['mobile_menu_style'] == 0 ? 'rgba(255, 255, 255, .98)' : '',
            '--mobile-menu-active-color' => isset($options['mobile_menu_style']) && $options['mobile_menu_style'] == 0 ? '#fff' : '',
            '--mobile-menu-bg-color' => isset($options['mobile_menu_style']) && $options['mobile_menu_style'] == 0 ? 'var(--theme-color)' : ''
        ];

        // 背景
        if(isset($options['bg_color']) && ($options['bg_color'] || $options['bg_image'])){
            $css_var['--theme-body-bg-color'] = isset($options['bg_color']) && $options['bg_color'] ? WPCOM::color($options['bg_color']) : '';
            $css_var['--theme-body-bg-image'] = isset($options['bg_image']) && $options['bg_image'] ? 'url(\'' . esc_url($options['bg_image']) . '\')' : '';
            $css_var['--theme-body-bg-image-repeat'] = isset($options['bg_image_repeat']) && $options['bg_image_repeat'] ? $options['bg_image_repeat'] : '';

            if(isset($options['bg_image_size']) && $options['bg_image_size'] && (!$options['bg_image_repeat'] || $options['bg_image_repeat'] === 'no-repeat')) {
                $css_var['--theme-body-bg-image-size'] = $options['bg_image_size'] == 2 ? 'cover' : '100% auto';
            };

            $css_var['--theme-body-bg-image-position'] = isset($options['bg_image_position']) && $options['bg_image_position'] ? $options['bg_image_position'] : '';
            $css_var['--theme-body-bg-image-attachment'] = isset($options['bg_image_attachment']) && $options['bg_image_attachment'] == 1 ? 'fixed' : '';

            $css_var['--special-color'] = isset($options['special_color']) && $options['special_color'] ? WPCOM::color($options['special_color']) : '';
        }

        if( isset($GLOBALS['wpmx_options']) && isset($GLOBALS['wpmx_options']['member_login_bg']) && $GLOBALS['wpmx_options']['member_login_bg'] !== '' ) {
            $css_var['--member-login-bg'] = 'url(\'' . esc_url($GLOBALS['wpmx_options']['member_login_bg']) . '\')';
        }

        $header_bg = isset($options['header_bg']) && $options['header_bg'] ? WPCOM::gradient_color($options['header_bg'], true) : '';
        if ($header_bg && isset($header_bg['color']) && $header_bg['color']) {
            $css_var['--header-bg-color'] = $header_bg['color'];
            $css_var['--header-bg-image'] = isset($header_bg['image']) && $header_bg['image'] ? $header_bg['image'] : 'none';
        }

        if(isset($options['border-radius-s'])){
            $css_var['--theme-border-radius-s'] = $options['border-radius-s'];
            $css_var['--theme-border-radius-m'] = $options['border-radius-m'];
            $css_var['--theme-border-radius-l'] = $options['border-radius-l'];
            $css_var['--theme-border-radius-xl'] = $options['border-radius-xl'];
        }

        $sizes = apply_filters('wpcom_image_sizes', array());
        if(isset($sizes['post-thumbnail']) && $sizes['post-thumbnail']) {
            $css_var['--thumb-ratio-default'] = $sizes['default']['width'] . ' / ' . $sizes['default']['height'];
            $css_var['--thumb-ratio-post'] = $sizes['post-thumbnail']['width'] . ' / ' . $sizes['post-thumbnail']['height'];
        }

        $video_height = intval(isset($options['post_video_height']) && $options['post_video_height'] ? $options['post_video_height'] : '');
        if($video_height) {
            $css_var['--post-video-ratio'] = 860 . ' / ' . $video_height;
        }

        $dark_logo = $options['dark_style_logo'] ?? '';
        if ($dark_logo) {
            $dark_logo = is_numeric($dark_logo) ? WPCOM::get_image_url($dark_logo) : $dark_logo;
            $css_var['--dark-style-logo'] = 'url(\'' . esc_url($dark_logo) .'\')';
        }

        $css_var_str = '';
        foreach($css_var as $key => $val){
            if(trim($val) !== '') $css_var_str .= $key . ': ' . $val . '; ';
        }
        $css .= ':root{' . trim($css_var_str) . '}';

        if(isset($options['dark_style']) && $options['dark_style'] == '2') {
            $css .= '@media (prefers-color-scheme: dark) {';
            $css .= ':root{ --theme-base-color: #000; --theme-body-bg-color: #121212; --theme-el-bg-color: #2a2a2a; --theme-color-l: 98%; --theme-black-color: #fff;}';
            $css .= '.footer { color: var(--theme-gray-color); background: var(--theme-el-bg-color);}';
            if($dark_logo){
                $css .= '.header .logo a, .member-form-head .member-form-logo{display: block;background-image: var(--dark-style-logo);background-size: auto 100%;background-repeat: no-repeat;}';
                $css .= '.header .logo a img, .member-form-head .member-form-logo img{visibility: hidden;} .member-form-head .member-form-logo{display: inline-block;background-size: 100% auto;}';
            }
            $css .= '}';
        }

        $sticky_color = $options['sticky_color'] ?? '';
        if($sticky_color) {
            $css .= '.post-loop .item-sticky .item-title a{-webkit-background-clip: text;-webkit-text-fill-color: transparent;}';
            $css .= '.post-loop .item-sticky .item-title a, .post-loop .item-sticky .item-title a .sticky-post,.post-loop-card .item-sticky .item-title .sticky-post{' . WPCOM::gradient_color($sticky_color) . '}';
        }

        if(isset($options['sidebar_float']) && $options['sidebar_float'] === 'left')
            $css .= '.main{float: right;}';

        if(isset($options['theme_set_gray']) && $options['theme_set_gray'] == '1')
            $css .= 'html{-webkit-filter: grayscale(100%);filter:grayscale(100%);}';

        if(isset($options['custom_css']) && trim($options['custom_css']) !== '')
            $css .= "\r\n" . $options['custom_css'];

        return $css;
    }
endif;

add_action('wp_head', function(){
    global $options;
    if(isset($options['theme_set_gray']) && $options['theme_set_gray'] == '2' && (is_home() || is_front_page())){
        echo '<style>html{-webkit-filter: grayscale(100%);filter:grayscale(100%);}</style>';
    }
});

function is_multimage( $post_id = '' ){
    global $post, $options;
    if($post_id==''){
        $post_id = $post->ID;
    }
    $multimage = get_post_meta($post_id, 'wpcom_multimage', true);
    $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;
    return $multimage;
}

add_action('init', 'wpcom_kx_init');
if ( ! function_exists( 'wpcom_kx_init' ) ) :
    function wpcom_kx_init(){
        global $options;
        if( (isset($options['kx_on']) && $options['kx_on']=='1') || (isset($_POST['action']) && $_POST['action'] === 'ocdi_import_demo_data')) {
            $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
            $labels = array(
                'name' => '快讯',
                'singular_name' => '快讯',
                'add_new' => '添加',
                'add_new_item' => '添加',
                'edit_item' => '编辑',
                'new_item' => '添加',
                'view_item' => '查看',
                'search_items' => '查找',
                'not_found' => '没有内容',
                'not_found_in_trash' => '回收站为空',
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'capability_type' => 'post',
                'menu_position' => null,
                'rewrite' => array('slug' => $slug),
                'show_in_rest' => true,
                'supports' => array('title', 'excerpt', 'thumbnail', 'comments')
            );
            register_post_type('kuaixun', $args);

            // add post meta
            add_filter( 'wpcom_post_metas', 'wpcom_add_kx_metas' );
        }
    }
endif;

add_action( 'pre_get_posts', 'wpcom_kx_orderby' );
function wpcom_kx_orderby( $query ){
    if( function_exists('get_current_screen') && $query->is_admin ) {
        $screen = get_current_screen();
        if ( isset($screen->base) && isset($screen->post_type) && 'edit' == $screen->base && 'kuaixun' == $screen->post_type && !isset($_GET['orderby'])) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
}

if ( ! function_exists( 'wpcom_add_kx_metas' ) ) :
    function wpcom_add_kx_metas( $metas ){
        $metas['kuaixun'] = array(
            array(
                "title" => "快讯设置",
                "option" => array(
                    array(
                        'name' => 'kx_url',
                        'title' => '快讯来源',
                        'desc' => '快讯来源链接地址',
                        'type' => 'text'
                    ),
                    array(
                        'name' => 'kx_color',
                        'title' => '标题高亮',
                        'desc' => '重要内容可选择对标题设置高亮颜色',
                        'type' => 'color'
                    )
                )
            )
        );
        return $metas;
    }
endif;

add_filter( 'get_the_excerpt', 'wpcom_kx_excerpt', 20, 2 );
if ( ! function_exists( 'wpcom_kx_excerpt' ) ) :
    function wpcom_kx_excerpt( $excerpt, $post ) {
        if( $post->post_type == 'kuaixun' && $url = get_post_meta($post->ID, 'wpcom_kx_url', true ) ){
            $excerpt .= ' <a class="kx-more" href="'.esc_url($url).'" target="_blank" rel="noopener nofollow">['._x('Read More', '原文链接', 'wpcom').']</a>';
        }
        return $excerpt;
    }
endif;

add_filter('the_title', 'wpcom_kx_title', 10, 2);
function wpcom_kx_title($title, $id){
    if(function_exists('get_current_screen') && $screen = get_current_screen()){
        if($screen->base === 'edit') return $title;
    }
    $post = get_post( $id );
    if($post && $post->post_type === 'kuaixun' && ((function_exists('WWA_is_rest') && !WWA_is_rest()) || !function_exists('WWA_is_rest')) && $color = get_post_meta($post->ID, 'wpcom_kx_color', true )){
        return '<span style="color:' . WPCOM::color($color) . ';">' . $title . '</span>';
    }
    return $title;
}

add_action( 'init', 'wpcom_kx_rewrite' );
function wpcom_kx_rewrite() {
    global $wp_rewrite, $options, $permalink_structure;
    if(isset($options['kx_on']) && $options['kx_on']=='1') {
        if (!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure) {
            $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
            $queryarg = 'post_type=kuaixun&p=';
            $wp_rewrite->add_rewrite_tag('%kx_id%', '([^/]+)', $queryarg);
            $wp_rewrite->add_permastruct('kuaixun', $slug . '/%kx_id%.html', false);
        }
    }
}

add_filter('post_type_link', 'wpcom_kx_permalink', 5, 2);
function wpcom_kx_permalink( $post_link, $id ) {
    global $wp_rewrite, $permalink_structure, $options;
    if(isset($options['kx_on']) && $options['kx_on']=='1') {
        if (!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure) {
            $post = get_post($id);
            if (!is_wp_error($post) && $post->post_type == 'kuaixun') {
                $newlink = $wp_rewrite->get_extra_permastruct('kuaixun');
                $newlink = str_replace('%kx_id%', $post->ID, $newlink);
                $newlink = home_url(untrailingslashit($newlink));
                return $newlink;
            }
        }
    }
    return $post_link;
}

add_action('wp_ajax_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
add_action('wp_ajax_nopriv_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
if ( ! function_exists( 'wpcom_load_kuaixun' ) ) :
    function wpcom_load_kuaixun(){
        global $options;
        $page = isset($_POST['page']) && $_POST['page'] ? sanitize_text_field($_POST['page']) : 1;
        $per_page = get_option('posts_per_page');

        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_type' => 'kuaixun'
        );
        $posts = WPCOM::get_posts($arg);

        if($posts->have_posts()) {
            $cur_day = '';
            while ( $posts->have_posts() ) : $posts->the_post();
                if($cur_day != $date = get_the_date(get_option('date_format'))){
                    $cur_day = $date;
                    $pre_day = '';
                    $week = date_i18n('D', get_the_date('U'));
                    if(date_i18n(get_option('date_format'), current_time('timestamp')) == $date) {
                        $pre_day = __('Today', 'wpcom') . ' • ';
                    }else if(date_i18n(get_option('date_format'), current_time('timestamp')-86400) == $date){
                        $pre_day = __('Yesterday', 'wpcom') . ' • ';
                    }
                    echo '<div class="kx-date">'. $pre_day .$date . ' • ' . $week.'</div>';
                } ?>
                <div class="kx-item" data-id="<?php the_ID();?>">
                    <span class="kx-time"><?php the_time('H:i');?></span>
                    <div class="kx-content">
                        <h2><?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a>
                            <?php } else{ the_title(); } ?></h2>
                        <?php the_excerpt();?>
                        <?php if(get_the_post_thumbnail()){ ?>
                            <?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a class="kx-img" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_post_thumbnail('full'); ?></a>
                            <?php }else{ ?>
                                <div class="kx-img"><?php the_post_thumbnail('full'); ?></div>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <div class="kx-meta clearfix" data-url="<?php the_permalink();?>">
                        <?php if( wpcom_show_poster() ) { ?>
                        <span class="j-mobile-share" data-id="<?php the_ID();?>" data-qrcode="<?php the_permalink();?>">
                            <?php WPCOM::icon('share');?> <?php _e('Generate poster', 'wpcom');?>
                        </span>
                        <?php } ?>
                        <span class="hidden-xs"><?php _e('Share to: ', 'wpcom');?></span>
                        <?php if(isset($options['post_shares'])){ if($options['post_shares']){ foreach ($options['post_shares'] as $share){ ?>
                            <a class="share-icon <?php echo $share;?> hidden-xs" target="_blank" data-share="<?php echo $share;?>" data-share-callback="kx_share" rel="noopener noreferrer">
                                <?php WPCOM::icon($share);?>
                            </a>
                        <?php } } }else{ ?>
                            <a class="share-icon wechat hidden-xs" data-share="wechat" data-share-callback="kx_share" rel="noopener noreferrer"><?php WPCOM::icon('wechat');?></a>
                            <a class="share-icon weibo hidden-xs" target="_blank" data-share="weibo" data-share-callback="kx_share" rel="noopener noreferrer"><?php WPCOM::icon('weibo');?></a>
                            <a class="share-icon qq hidden-xs" target="_blank" data-share="qq" data-share-callback="kx_share" rel="noopener noreferrer"><?php WPCOM::icon('qq');?></a>
                        <?php } ?>
                        <span class="share-icon copy hidden-xs"><?php WPCOM::icon('copy');?></span>
                    </div>
                </div>
            <?php endwhile;
            wp_reset_postdata();
        }else{
            echo 0;
        }
        exit;
    }
endif;

add_action('wp_ajax_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
add_action('wp_ajax_nopriv_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
function wpcom_new_kuaixun(){
    $id = isset($_POST['id']) && $_POST['id'] ? sanitize_text_field($_POST['id']) : '';
    if($post = get_post($id)){
        $time = get_the_time('U', $post->ID);
        $args = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun',
            'date_query' => array(
                array(
                    'after'    => array(
                        'year'   => date('Y', $time),
                        'month'  => date('m', $time),
                        'day'    => date('d', $time),
                        'hour'   => date('H', $time),
                        'minute' => date('i', $time),
                        'second' => date('s', $time),
                    ),
                    'inclusive' => false
                )
            ),
            'posts_per_page' => -1
        );
        $my_date_query = new WP_Query( $args );
        $num = $my_date_query->found_posts;
        if($num >= 1){
            printf(_n('You have %s new message!', 'You have %s new messages!', $num, 'wpcom'), $num);
        }
    }
    exit;
}

add_filter('wpcom_page_can_cache', function($cache){
    global $options;
    if($cache && isset($options['kx_page']) && $options['kx_page'] && is_page($options['kx_page'])){
        $cache = false;
    }
    return $cache;
});

add_filter( 'user_can_richedit', 'wpcom_can_richedit' );
if ( ! function_exists( 'wpcom_can_richedit' ) ) {
    function wpcom_can_richedit( $wp_rich_edit ){
        global $is_IE;
        if( !$wp_rich_edit && $is_IE && !is_admin() ){
            $wp_rich_edit = 1;
        }
        return $wp_rich_edit;
    }
}

if(!function_exists('wpcom_post_metas')){
    function wpcom_post_metas( $key = '', $url = true ){
        $html = '';
        if($key){
            global $post;
            switch ($key){
                case 'h':
                    $fav = get_post_meta($post->ID, 'wpcom_favorites', true);
                    $fav = $fav ?: 0;
                    $html = '<span class="item-meta-li stars" title="'._x('Favorites', 'metas', 'wpcom').'">' . WPCOM::icon('star', false) . $fav.'</span>';
                    break;
                case 'z':
                    $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                    $likes = $likes ?: 0;
                    $html = '<span class="item-meta-li likes" title="'._x('Likes', 'metas', 'wpcom').'">' . WPCOM::icon('thumb-up', false) . $likes.'</span>';
                    break;
                case 'v':
                    if( function_exists('the_views') ) {
                        $views = $post->views ?: 0;
                        if ($views >= 1000) $views = sprintf("%.1f", $views / 1000) . 'K';
                        $html = '<span class="item-meta-li views" title="'._x('Views', 'metas', 'wpcom').'">' . WPCOM::icon('eye', false) . $views . '</span>';
                    }
                    break;
                case 'c':
                    global $options;
                    if(isset($options['comments_open']) && $options['comments_open']=='0') break;
                    $comments = get_comments_number();
                    if($url){
                        $html = '<a class="item-meta-li comments" href="'.get_permalink($post->ID).'#comments" target="_blank" title="'._x('Comments', 'metas', 'wpcom').'">';
                    }else{
                        $html = '<span class="item-meta-li comments" title="'._x('Comments', 'metas', 'wpcom').'">';
                    }
                    $html .= WPCOM::icon('comment', false) . $comments;
                    $html .= $url ? '</a>' : '</span>';
                    break;
            }
        }
        return $html;
    }
}

add_shortcode('wpcom_tags', 'wpcom_shortcode_tags');
function wpcom_shortcode_tags($args){
    $paged = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
    $number = isset($args['per_page']) && $args['per_page'] ? $args['per_page'] : 0;
    $taxonomy = isset($args['taxonomy']) && $args['taxonomy'] ? $args['taxonomy'] : 'post_tag';
    $offset = ( $paged > 0 ) ?  $number * ( $paged - 1 ) : '';
    $max   = wp_count_terms( $taxonomy, array( 'hide_empty' => true ) );
    $totalpages   = $number ? ceil( $max / $number ) : 0;
    $args = array(
        'taxonomy' => $taxonomy,
        'orderby' => 'count',
        'order' => 'DESC',
        'offset' => $offset,
        'number' => $number
    );
    $tags = get_terms($taxonomy, $args);
    if ( empty( $tags ) || is_wp_error( $tags ) ) {
        return;
    }
    $html = '<ul class="wpcom-shortcode-tags">';
    foreach ( $tags as $key => $tag ) {
        $link = get_term_link( intval( $tag->term_id ), $tag->taxonomy );
        if ( is_wp_error( $link ) ) {
            return;
        }
        $html .= '<li><a href="'.$link.'" target="_blank" title="'.($tag->description?:$tag->name).'">'.$tag->name.'</a><span>('.$tag->count.')</span></li>';
    }
    $html .= '</ul>';
    if($number){
        ob_start();
        wpcom_pagination(6, array('paged' => $paged, 'numpages' => $totalpages));
        $html .= ob_get_contents();
        ob_end_clean();
    }
    return $html;
}

add_filter( 'wpcom_localize_script', 'wpcom_video_height' );
function wpcom_video_height($scripts){
    global $options;
    $scripts['video_height'] = intval(isset($options['post_video_height']) && $options['post_video_height'] ? $options['post_video_height'] : 484);
    $scripts['fixed_sidebar'] = 1;
    $scripts['js_lang'] = isset($scripts['js_lang']) ? $scripts['js_lang'] : array();
    $scripts['js_lang'] = array_merge($scripts['js_lang'], array(
        'page_loaded' => __('All content has been loaded', 'wpcom'),
        'no_content' => __('No content yet', 'wpcom'),
        'load_failed' => __('Load failed, please try again later!', 'wpcom'),
        'expand_more' => __('Expand and read the remaining %s', 'wpcom')
    ));
    $scripts['dark_style'] = isset($options['dark_style']) ? $options['dark_style'] : 0;
    // $lang = get_locale();
    $font_str = '?family=Noto+Sans+SC:wght@400;500&display=swap';
    $font_url = 'https://fonts.googleapis.com/css2' . $font_str;
    if((isset($options['google-font-local']) && $options['google-font-local'] == '1') || !isset($options['google-font-local'])){
        $font_url = \WPCOM\Themer\Static_Cache::get_font_css($font_url);
    }
    $scripts['font_url'] = preg_replace('/^(http:|https:)/i', '', $font_url);
    return $scripts;
}

add_filter( 'body_class', 'wpcom_el_boxed_class' );
function wpcom_el_boxed_class($class) {
    global $options;
    if( !isset($options['el_boxed']) || (isset($options['el_boxed']) && $options['el_boxed']) ) $class[] = 'el-boxed';
    if(isset($options['dark_style']) && $options['dark_style'] == '1' ) $class[] = 'style-for-dark';
    if(!isset($options['header_fixed']) ||  $options['header_fixed'] == '1' ) $class[] = 'header-fixed';
    if(isset($options['dark_style']) && $options['dark_style'] == '2'){
        $class[] = 'style-by-auto';
    }else if(isset($options['dark_style_toggle']) && $options['dark_style_toggle'] == '1'){
        $class[] = 'style-by-toggle';
    }
    return $class;
}

add_filter( 'wpcom_thumbnail_url', 'wpcom_thumbnail_url', 10, 4);
function wpcom_thumbnail_url($img_url, $post_id, $post_thumbnail_id, $size){
    global $options;
    $_post = $post_id ? get_post($post_id) : '';
    if(!$post_thumbnail_id && !$img_url && isset($_post->ID) && $_post->post_type == 'post'){
        $img_id = isset($options['post_thumb']) && $options['post_thumb'] ? $options['post_thumb'] : '';
        if(is_array($img_id)) {
            $img_index = array_rand($img_id);
            $img_id = $img_id[$img_index];
        }
        if($img_id) $img_url = wp_get_attachment_image_url( $img_id, $size );
    }
    return $img_url;
}

add_action('embed_head', 'wpcom_embed_head', 5);
function wpcom_embed_head() {
    echo '<meta charset="'.get_bloginfo( 'charset' ).'">'. "\r\n";
    wp_enqueue_style('stylesheet');
    wp_enqueue_script('wpcom-icons');
}

remove_action('embed_footer', 'print_embed_sharing_dialog');

add_action('wp_head', function(){
    wp_deregister_script('wp-embed');
    wp_register_script('wp-embed', get_template_directory_uri() . '/js/wp-embed.js', array('jquery'), THEME_VERSION, true);
    wp_enqueue_script('wp-embed');
});

add_filter('wpcom_exclude_post_metas', 'wpcom_exclude_post_metas');
function wpcom_exclude_post_metas($metas) {
    $metas = array_merge($metas, array('favorites', 'likes'));
    return $metas;
}

function wpcom_post_target(){
    global $options;
    return isset($options['post_target']) && $options['post_target']==='' ? '' : ' target="_blank"';
}

add_filter('wpcom_widget_preview_style', 'wpcom_widget_preview_style');
function wpcom_widget_preview_style($style){
    $style .= '.widget-priview{background: #f7f8f9!important;}';
    return $style;
}

add_filter('admin_print_footer_scripts', 'wpcom_widgetarea_bg');
function wpcom_widgetarea_bg(){
    global $options;?>
    <style>
        .blocks-widgets-container .wp-block-widget-area__inner-blocks.editor-styles-wrapper>.block-editor-block-list__layout{
            background: <?php echo (isset($options['el_boxed']) && $options['el_boxed'] ? ($options['bg_color']?:'#f7f8f9') : '#fff' );?>;
        }
        .blocks-widgets-container .top-news{
            display: none;
        }
    </style>
<?php }

// 6.8.0版本开始
add_action('themer_updated', 'wpcom_update_user_approve_data');
function wpcom_update_user_approve_data($version){
    if($version === '' || version_compare($version,'2.6.18','<')){
        global $wpdb;
        $meta_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
        // 仅处理2k数据
        $users = get_users(array(
            'number' => 2000,
            'order' => 'DESC',
            'orderby' => 'ID',
            'meta_query' => array(
                array(
                    'key' => $meta_key,
                    'value' => 's:7:"approve";i:0;',
                    'compare' => 'LIKE'
                )
            )
        ) );
        if($users){
            foreach ($users as $user){
                wp_update_user( array( 'ID' => $user->ID, 'user_status' => -1 ) );
                update_user_meta($user->ID, 'wpcom_approve', ''); // 清除旧数据
            }
        }
    }
}

add_filter( 'post_class', 'wpcom_post_classes' );
function wpcom_post_classes( $classes ) {
    $classes[] = 'entry';
    return $classes;
}

function wpcom_header_class(){
    global $options;
    $header_class = '';
    if(isset($options['header_bg']) && $options['header_bg'] && isset($options['header_style'])){
        if($options['header_style'] == '1') {
            $header_class = ' header-style-2';
        }else if(isset($options['dark_style']) && $options['dark_style'] == '1' && isset($options['dark_style_toggle']) && $options['dark_style_toggle'] == '0'){
            $header_class = ' header-style-1';
        }
    }
    if(isset($options['header_fluid']) && $options['header_fluid'] == '1'){
        $header_class .= ' header-fluid';
    }
    return $header_class;
}

add_filter('wpcom_top_news_classes', function($classes){
    global $options;
    if(isset($options['header_fluid']) && $options['header_fluid'] == '1'){
        $classes .= ' top-news-fluid';
    }
    return $classes;
});

add_filter('the_content', 'wpcom_content_expand_more', 99);
function wpcom_content_expand_more($content){
    global $options;
    if(is_singular('post') && in_the_loop() && is_main_query() && isset($options['expand_more']) && $options['expand_more'] == '1'){
        $content .= '<div class="entry-readmore"><div class="entry-readmore-btn"></div></div>';
    }
    return $content;
}

add_action('wpcom_options_updated', function($ops){
    if(isset($ops['dark_style']) && $ops['dark_style'] != 0 && ($ops['header_bg']==='#fff' || $ops['header_bg']==='#FFFFFF')){
        global $wpcom_panel;
        $wpcom_panel->set_theme_options(array('header_bg' => ''));
    }
});

add_action('pre_get_posts', function($wp_query){
    global $options;
    if($wp_query->is_main_query() && $wp_query->is_category() && isset($options['category_sticky']) && $options['category_sticky']){
        $wp_query->set('ignore_sticky_posts', 0);
        global $is_sticky;
        $is_sticky = 1;
    }
    if( $wp_query->is_main_query() && ($wp_query->is_category() || $wp_query->is_tag() || $wp_query->is_tax('special')) ){
        $term_id = get_queried_object_id();
        $pagenavi = get_term_meta($term_id, 'wpcom_pagenavi', true);
        if($pagenavi == '1' || $pagenavi == '2'){
            $wp_query->set('no_found_rows', 1);
        }
        $wp_query->set('thumbnail', 1);
    }
});

add_filter('wpcom_sidebar_classes', function($classes){
    global $options;
    if(isset($options['sidebar_float']) && $options['sidebar_float'] === 'left'){
        $classes .= ' sidebar-on-left';
    }
    return $classes;
});

add_action('wpcom_member_account_after_dio', function(){
    global $options;
    if( isset($options['tougao_on']) && $options['tougao_on']=='1' ){ ?>
        <a class="wpcom-btn btn-primary btn-block member-account-tg" href="<?php echo esc_url(wpcom_addpost_url());?>">
        <?php echo (isset($options['tougao_btn']) && $options['tougao_btn'] ? $options['tougao_btn'] : WPCOM::icon('quill-pen', false).__('Submit a Post', 'wpcom'));?>
        </a>
    <?php }
});

add_filter('wpcom_menu_metas', function($metas){
    $_metas = [];
    foreach($metas as $key => $meta){
        if($key === 'style'){
            $style = [
                ['' => '默认风格1||/themer/menu-style-0.png'],
                ['1' => '风格2||/themer/menu-style-1.png'],
                ['2' => '风格3||/themer/menu-style-2.png'],
                ['3' => '风格4||/themer/menu-style-3.png'],
                ['4' => '风格5||/themer/menu-style-4.png'],
                ['5' => '风格6||/themer/menu-style-5.png']
            ];
            isset($meta['o']) && $meta['o'] = $style OR $meta['options'] = $style;
        }

        $_metas[$key] = $meta;

        if($key === 'title'){
            $_metas['description'] = [
                'name' => '导航简介',
                'type' => 'ta',
                'rows' => 2,
                'f' => 'style:4&&level:!!0,style:5&&level:2',
                'd' => '文字简介信息，部分下拉菜单风格会用到',
            ];
        }
    }

    $_metas += [
        'adv-style' => [
            'name' => '附加样式',
            't' => 'r',
            'f' => 'level:0',
            'd' => '温馨提示：附加样式<b>仅对页头主菜单生效</b>',
            'o' => [
                '' => '无',
                'flag' => '角标',
                'btn' => '按钮'
            ]
        ],
        'btn-color' => [
            'name' => '按钮文字颜色',
            't' => 'c',
            'f' => 'level:0&&adv-style:btn'
        ],
        'btn-bg' => [
            'name' => '按钮背景颜色',
            't' => 'c',
            'f' => 'level:0&&adv-style:btn',
            'gradient' => 1
        ],
        'btn-radius' => [
            'name' => '按钮是否圆角',
            't' => 't',
            's' => 0,
            'f' => 'level:0&&adv-style:btn'
        ],
        'flag-text' => [
            'name' => '角标文字',
            'f' => 'level:0&&adv-style:flag',
            'd' => '建议精简文字，不要太长，避免影响显示效果'
        ],
        'flag-color' => [
            'name' => '角标文字颜色',
            't' => 'c',
            'f' => 'level:0&&adv-style:flag'
        ],
        'flag-bg' => [
            'name' => '角标背景颜色',
            't' => 'c',
            'f' => 'level:0&&adv-style:flag',
            'gradient' => 1
        ],
    ];
    return $_metas;
}, 20);

add_filter( 'nav_menu_css_class', function($classes, $item, $args){
    if(isset($args->advanced_menu) && $args->advanced_menu && isset($item->{'adv-style'}) && $item->{'adv-style'}){
        $classes[] = 'adv-style-' . $item->{'adv-style'};
    }
    return $classes;
}, 10, 3);

add_filter( 'walker_nav_menu_start_el', function($item_output, $item, $depth, $args){
    if(isset($args->advanced_menu) && $args->advanced_menu && $depth === 0 && isset($item->{'adv-style'}) && $item->{'adv-style'} === 'flag' && isset($item->{'flag-text'}) && trim($item->{'flag-text'})){
        $text = trim($item->{'flag-text'});
        $color = isset($item->{'flag-color'}) && $item->{'flag-color'} ? WPCOM::color($item->{'flag-color'}) : '';
        $bg = isset($item->{'flag-bg'}) && $item->{'flag-bg'} ? WPCOM::gradient_color($item->{'flag-bg'}) : '';
        $style = '';
        if($color) $style .= 'color:'.$color.';';
        if($bg) $style .= $bg;
        if($style) $style = ' style="'.$style.'"';
        $item_output .= '<span class="menu-item-flag"'.$style.'>'.$text.'</span>';
    }
    return $item_output;
}, 10, 4);

add_filter( 'nav_menu_link_attributes', function($atts, $item, $args){
    if(isset($args->advanced_menu) && $args->advanced_menu && isset($item->{'adv-style'}) && $item->{'adv-style'} === 'btn' && isset($item->{'btn-color'})){
        $color = isset($item->{'btn-color'}) && $item->{'btn-color'} ? WPCOM::color($item->{'btn-color'}) : '';
        $bg = isset($item->{'btn-bg'}) && $item->{'btn-bg'} ? WPCOM::gradient_color($item->{'btn-bg'}) : '';
        $radius = isset($item->{'btn-radius'}) && $item->{'btn-radius'};
        $style = '';
        if($color) $style .= 'color:'.$color.';';
        if($bg) $style .= $bg;
        if($style) $atts['style'] = $style;
        if($radius) {
            $atts['class'] = isset($atts['class']) ? $atts['class'] : '';
            $atts['class'] .= ($atts['class'] ? ' ' : '').'btn-radius';
        }
    }
    return $atts;
}, 10, 3);

function wpcom_nav_menu_classes(){
    global $options;

    $classes = ['collapse', 'navbar-collapse'];

    $menu_location = $options['menu_location'] ?? 1;
    $mobile_menu = $options['mobile_menu_style'] ?? 0;

    if($menu_location) $classes[] = 'navbar-right';
    $classes[] = 'mobile-style-' . esc_attr($mobile_menu);

    $classes = apply_filters('wpcom_nav_menu_classes', $classes);

    return implode(' ', $classes);
}

if( class_exists( 'ezTOC' ) ) {
    if(!function_exists( 'ez_toc_pro_activation_link' ) && !is_admin()) {
        function ez_toc_pro_activation_link(){}
    }

    add_filter('ez_toc_modify_icon', function($icon){
        return '<i class="ez-toc-toggle-el"></i>';
    });

    add_filter('ez_toc_url_anchor_target', function($id){
        return str_replace('%', '', urlencode($id));
    });

    add_filter('ez_toc_maybe_apply_the_content_filter', function($apply){
        if($apply && function_exists('is_wpcom_member_page') && ( is_wpcom_member_page('account') || is_wpcom_member_page('profile') ) ){
            $apply = false;
        }
        return $apply;
    });

    add_filter('eztoc_do_shortcode', function($isEligible){
        if(wp_doing_ajax()) $isEligible = false;
        return $isEligible;
    });

    add_filter( 'ez_toc_modify_process_page_content', function($content){
        if(function_exists('WWA_is_rest') && WWA_is_rest()) $content = '';
        return $content;
    }, 999);

    add_filter( 'ez_toc_get_option_smooth_scroll', function($value){
        return false;
    });

    add_action('wp_loaded', function(){
        global $wp_registered_widgets;
        if($wp_registered_widgets){
            foreach($wp_registered_widgets as $id => $widget){
                if(isset($widget['id']) && preg_match('/^ez_toc_widget_sticky/i', $widget['id']) && isset($widget['classname'])){
                    $wp_registered_widgets[$id]['classname'] = 'widget-area widget-ez_toc_sticky';
                }
            }
        }
    });
}

add_filter('wpcom_no_sidebar_width_enable', '__return_true');

// 封装页头搜索框，基于 get_search_form filter 来兼容多语言搜索
function wpcom_header_search_form(){
    $form = '<div class="navbar-search-icon j-navbar-search">' . WPCOM::icon('search', false) . '</div>';
    $form .= '<form class="navbar-search" action="' . esc_url( home_url( '/' ) ) . '" method="get" role="search">';
    $form .= '<div class="navbar-search-inner">';
    $form .= WPCOM::icon('close', false, 'navbar-search-close');
    $form .= '<input type="text" name="s" class="navbar-search-input" autocomplete="off" maxlength="100" placeholder="' .__('Type your search here ...', 'wpcom') . '" value="' . get_search_query() . '">';
    $form .= '<button class="navbar-search-btn" type="submit" aria-label="' . __('Search', 'wpcom') . '">' . WPCOM::icon('search', false) . '</button>';
    $form .= '</div></form>';

    echo apply_filters('get_search_form', $form, ['echo' => false]);
}

function wpcom_show_poster(){
    global $options;
    if(!isset($options['poster']) || (isset($options['poster']) && $options['poster'] == '1')){
        return true;
    }
    return false;
}