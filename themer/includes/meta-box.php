<?php
namespace WPCOM\Themer;
defined( 'ABSPATH' ) || exit;

class Meta {
    public function __construct() {
        add_action( 'load-post.php', [ $this, 'register_scripts' ] );
        add_action( 'load-post-new.php', [ $this, 'register_scripts' ] );
        add_action( 'add_meta_boxes', [ $this, 'set_metabox' ] );
        add_action( 'save_post', [ $this, 'save_metabox' ] );
        add_action( 'wp_ajax_wpcom_get_keys_value', [ $this, 'get_keys_value' ] );
        add_action( 'wp_ajax_wpcom_get_attachments', [ $this, 'get_attachments' ] );
        add_action( 'wp_ajax_wpcom_get_settings_by_key', [ $this, 'get_settings_by_key' ] );
    }

    public function register_scripts() {
        add_action('admin_enqueue_scripts', ['WPCOM', 'panel_script']);
    }

    public function set_metabox(){
        global $wp_post_types;
        $exclude_types = [ 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'um_form', 'um_role', 'um_directory', 'product_variation', 'shop_order', 'shop_order_refund', 'shop_coupon', 'shop_order_placehold', 'page_module', 'app_module' ];
        $exclude_types = apply_filters('wpcom_metabox_exclude_types', $exclude_types);

        foreach( $wp_post_types as $type => $args ){
            if( ! in_array( $type , $exclude_types ) ){
                add_meta_box('wpcom-metas', '设置选项', [$this, 'metabox_html'], $type, 'normal', 'high', []);
            }
        }
    }

    public function metabox_html( $post ){
        // Add an nonce field
        wp_nonce_field( 'wpcom_meta_box', 'wpcom_meta_box_nonce' );
        $editor = post_type_supports($post->post_type, 'editor');
        if($post->post_type !== 'gutenberg_content'){ ?><div id="wpcom-panel" class="wpcom-post-metas"><post-panel :ready="ready" /></div><?php } ?>
        <script>_panel_options = <?php echo $this->get_post_metas($post);?>;</script>
        <?php if(!$editor){ ?><div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', \WPCOM::editor_settings(['textarea_name' => 'EDITOR-NAME', 'skip_init' => true]) );?></div><?php } ?>
    <?php }

    private function get_post_metas( $post ){
        global $options;
        $res = ['type' => 'post', 'post_type' => $post->post_type];
        $res['options'] = get_post_meta($post->ID, '_wpcom_metas', true);
        $_options = [];
        if(!empty($options)){
            foreach($options as $k => $v){
                if((is_array($v) && !preg_match('/^sl_/', $k)) || $k === 'save_remote_img') $_options[$k] = $v;
            }
        }

        $res['theme-settings'] = apply_filters('wpcom_get_settings_for_theme_settings', $_options);
        $res['filters'] = apply_filters( 'wpcom_post_metas', [] );
        $res['post_id'] = $post->ID;
        $res['ver'] = THEME_VERSION;
        $res['theme-id'] = THEME_ID;
        $res['framework_url'] = FRAMEWORK_URI;
        $res['framework_ver'] = FRAMEWORK_VERSION;
        $res['seo'] = !isset($options['seo']) || $options['seo']=='1' ? true : false;
        $res = apply_filters('wpcom_post_panel_options', $res);
        return wp_json_encode($res);
    }

    public function get_keys_value(){
        $post_id = $_REQUEST['id'];
        $keys = $_REQUEST['keys'];
        $tax = isset($_REQUEST['tax']) ? $_REQUEST['tax'] : '';
        $res = [];
        if( current_user_can( 'edit_posts', $post_id ) ){
            foreach ($keys as $key){
                if($post_id) $res[$key] = $tax ? get_term_meta( $post_id, $key, true ) : get_post_meta($post_id, $key, true);
            }
        }
        wp_send_json($res);
    }

    public function get_attachments(){
        $ids = array_map( 'intval', $_REQUEST['ids'] );
        $res = [];
        if( current_user_can( 'edit_posts' ) ){
            foreach ($ids as $id){
                $post = get_post( $id );
                if($post && $post->post_mime_type && !preg_match('/^image\//i', $post->post_mime_type)){
                    $res[$id] = wp_mime_type_icon( $id );
                }else{
                    $img = \WPCOM::get_image_url( $id );
                    if($img) $res[$id] = $img;
                }
            }
        }
        wp_send_json($res);
    }

    public function get_settings_by_key(){
        $key = array_map( 'sanitize_text_field', $_REQUEST['key'] );
        $res = [];
        if( current_user_can( 'edit_posts' ) ){
            $options = get_option($key);
            $_options = [];
            foreach($options as $k => $v){
                if(is_array($v)) $_options[$k] = $v;
            }
            $res = apply_filters('wpcom_get_settings_for_' . $key, $_options);
        }
        wp_send_json($res);
    }

    /**
     * Save the meta when the post is saved.
     */
    public function save_metabox($post_id){
        global $post;
        if($post && $post->ID != $post_id) return false;

        if(isset($_POST['post_type'])){
            foreach($_POST as $key => $value) {
                if (preg_match('/^_wpcom_/i', $key)) {
                    $meta_boxes = isset($meta_boxes) ? $meta_boxes : [];
                    $meta_boxes[] = preg_replace('/^_wpcom_/i', '', $key);
                }
            }
        }

        if(!isset($meta_boxes)||!$meta_boxes) return false;

        // Check if our nonce is set.
        if ( ! isset( $_POST['wpcom_meta_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['wpcom_meta_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'wpcom_meta_box' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        // so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Check the user's permissions.
        if ( 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        $metas = get_post_meta( $post_id, '_wpcom_metas', true);
        $metas = is_array($metas) ? $metas : [];
        foreach ($meta_boxes as $meta) {
            if(preg_match('/^_/', $meta)) {
                update_post_meta($post_id, $meta, stripslashes_deep( $_POST['_wpcom_' . $meta] ) );
            } else {
                $value = stripslashes_deep( $_POST['_wpcom_' . $meta] );

                if ( $value !== '' )
                    $metas[$meta] = $value;
                else if ( isset($metas[$meta]) )
                    unset($metas[$meta]);
            }
        }
        update_post_meta($post_id, '_wpcom_metas', $metas );
    }
}

class_alias(Meta::class, 'WPCOM_Meta');