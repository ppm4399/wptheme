<?php defined( 'ABSPATH' ) || exit;

class WPCOM_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent = str_repeat( "\t", $depth );
        $class_names = 'dropdown-menu';
        if ( $depth === 0) {
            $class_names .= ' menu-item-wrap';
            if( isset($args->child_count) && $args->child_count > 1 ) $class_names .= ' menu-item-col-' . ($args->child_count < 6 ? $args->child_count : 5);
        }
		$output .= "\n$indent<ul class=\"" . $class_names . "\">\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        global $root_menu_style;
        $root_menu_style = $root_menu_style ?? new stdClass;
        if(isset($item->style) && $args->has_children){
            $root_menu_style->{$item->db_id} = isset($root_menu_style->{$item->menu_item_parent}) ? $root_menu_style->{$item->menu_item_parent} : $item->style;
        }

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
        $classes = empty( $item->classes ) ? [] : (array) $item->classes;

        if ( in_array( 'current-menu-item', $classes ) ||  in_array( 'current-menu-ancestor', $classes ) ||  in_array( 'current-post-ancestor', $classes )) {
            $classes[] = 'active';
        }

        $unset_classes = ['current-menu-item', 'current-menu-ancestor', 'current_page_ancestor', 'current_page_item', 'current_page_parent', 'current-menu-parent'];
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

        if ( $depth === 0 && ! empty( $item->style ) ) {
            $class_names .= ' menu-item-style menu-item-style-' . esc_attr($item->style);
        }

        if ( ! empty( $item->image ) ) {
            $class_names .= ' menu-item-has-image';
        }

        if ( $args->has_children )
            $class_names .= ' dropdown';

        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= $indent . '<li' . $class_names .'>';

        $atts = array();
        $atts['target'] = ! empty( $item->target )	? $item->target	: '';
        $atts['target'] = $atts['target'] == '1' ? '_blank' : $atts['target'];
        $atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';

        // If item has_children add atts to a.
        if ( $args->has_children && $depth === 0 ) {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
            $atts['class']			= 'dropdown-toggle';
        } else {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
        }

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before;

        if( trim($item->title) != '0' ) {
            if (!empty($item->attr_title))
                $item_output .= '<a' . $attributes . ' title="'.esc_attr($item->attr_title).'">';
            else
                $item_output .= '<a' . $attributes . '>';

            if (!empty($item->image)) {
                if(preg_match('/^(http:|https:|\/\/)/i', $item->image)){
                    $item_output .= wpcom_lazyimg($item->image, $item->title, '', '', 'menu-item-image');
                }else{
                    $item_output .= WPCOM::icon($item->image, false, 'menu-item-icon');
                }
            }

            $has_desc = false;
            if($depth > 0 && isset($root_menu_style->{$item->menu_item_parent}) && $root_menu_style->{$item->menu_item_parent} == '4'){
                $has_desc = true;
            }else if($depth === 2 && isset($root_menu_style->{$item->menu_item_parent}) && $root_menu_style->{$item->menu_item_parent} == '5'){
                $has_desc = true;
            }

            if($has_desc) $item_output .= '<span class="menu-item-inner">';

            $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;

            if($has_desc) {
                if(isset($item->description) && trim($item->description) !== ''){
                    $item_output .= '<span class="menu-item-desc">' . sanitize_textarea_field($item->description) . '</span>';
                }
                $item_output .= '</span>';
            }

            $item_output .= '</a>';
        }

        if(isset($item->style) && $item->style == 5 && $args->has_children && $depth === 0){
            $item_output .= '<div class="dropdown-menu menu-item-wrap menu-item-col-5">';
        }

        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $end_output = '';
		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$n = '';
		} else {
			$n = "\n";
		}

        if(isset($item->style) && $item->style == 5 && $args->has_children && $depth === 0){
            $end_output .= "</div>{$n}";
        }

        $end_output .= "</li>{$n}";

        $output .= apply_filters( 'walker_nav_menu_end_el', $end_output, $data_object, $depth, $args );
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element )
            return;

        $id_field = $this->db_fields['id'];

        // Display this element.
        if ( is_object( $args[0] ) ) {
            $args[0]->has_children = !empty($children_elements[$element->$id_field]);
            if( $depth==0 && $args[0]->has_children ){
                $args[0]->child_count = count($children_elements[$element->$id_field ]);
            }
        }

        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }

	/**
	 * Menu Fallback
	 * =============
	 * If this function is assigned to the wp_nav_menu's fallback_cb variable
	 * and a manu has not been assigned to the theme location in the WordPress
	 * menu manager the function with display nothing to a non-logged in user,
	 * and will add a link to the WordPress menu manager if logged in as an admin.
	 *
	 * @param array $args passed from the wp_nav_menu function.
	 *
	 */
	public static function fallback( $args ) {
		if ( current_user_can( 'manage_options' ) ) {

			extract( $args, EXTR_SKIP );

			$fb_output = null;

			if ( $container ) {
				$fb_output = '<' . $container;

				if ( $container_id )
					$fb_output .= ' id="' . $container_id . '"';

				if ( $container_class )
					$fb_output .= ' class="' . $container_class . '"';

				$fb_output .= '>';
			}

			$fb_output .= '<ul';

			if ( $menu_id )
				$fb_output .= ' id="' . $menu_id . '"';

			if ( $menu_class )
				$fb_output .= ' class="' . $menu_class . '"';

			$fb_output .= '>';
			$fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">设置导航</a></li>';
			$fb_output .= '</ul>';

			if ( $container )
				$fb_output .= '</' . $container . '>';

			echo $fb_output;
		}
	}
}


add_filter( 'nav_menu_css_class', 'wpcom_nav_menu_css_class' );
function wpcom_nav_menu_css_class( $classes ){
    if($classes){
        $unset_classes = array('menu-item-type-post_type', 'menu-item-object-page', 'menu-item-object-category', 'menu-item-type-taxonomy', 'menu-item-object-custom', 'menu-item-type-custom', 'menu-item-has-children', 'page_item', 'menu-item-home');
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }
    }
    return $classes;
}


/**
 * 以下代码均用于后台菜单编辑
 */

add_filter( 'wp_edit_nav_menu_walker', 'wpcom_nav_walke_fun', 10 );
add_action( 'wp_update_nav_menu_item', 'wpcom_update_nav_menu_item', 20, 2 );
function wpcom_nav_walke_fun($walker){
    global $wpcom_panel;
    include_once FRAMEWORK_PATH . '/includes/nav-walker-edit.php';
    if($wpcom_panel->get_demo_config()) $walker = 'WPCOM_Nav_Walker_Edit';
    return $walker;
}

function wpcom_update_nav_menu_item( $menu_id, $item_id ){
    if(isset($_POST) && $_POST){
        $exclude_key = array('title', 'url', 'classes', 'xfn');
        foreach(wp_unslash($_POST) as $key => $val){
            $_key = preg_replace('/^menu-item-/i', '', $key);
            if($_key !== $key && !in_array($_key, $exclude_key) && is_array($val)){
                $item = isset($val[$item_id]) ? sanitize_text_field($val[$item_id]) : '';
                update_post_meta( $item_id, ($_key === 'target' ? '' : 'wpcom_').$_key, $item );
            }
        }
    }
}

add_filter( 'wp_setup_nav_menu_item', function ( $menu_item ){
    global $hook_suffix;
    if($menu_item){
        $exclude_key = array('title', 'url', 'classes', 'xfn');
        if( $hook_suffix === 'nav-menus.php' && isset($_POST) && $_POST ) {
            foreach(wp_unslash($_POST) as $key => $val){
                $_key = preg_replace('/^menu-item-/i', '', $key);
                if($_key !== $key && !in_array($_key, $exclude_key) && is_array($val) && isset($val[$menu_item->ID])){
                    $menu_item->{$_key} = sanitize_text_field($val[$menu_item->ID]);
                }
            }
        }else{
            $metas = apply_filters('wpcom_menu_metas', array());
            foreach($metas as $key => $meta){
                if(!in_array($key, $exclude_key) && !empty($meta)){
                    if($key === 'target'){
                        if($hook_suffix === 'nav-menus.php') $menu_item->{$key} = $menu_item->{$key} ? 1 : '';
                    }else{
                        $menu_item->{$key} = get_post_meta( $menu_item->ID, 'wpcom_'.$key, true );
                    }
                }
            }
        }
    }
    return $menu_item;
}, 20);

add_filter( 'wp_nav_menu_args', function( $args ){
    if( isset($args['advanced_menu']) && $args['advanced_menu'] ){
        if( isset($args['menu_class']) && $args['menu_class'] ){
            $args['menu_class'] .= ' wpcom-adv-menu';
        }else{
            $args['menu_class'] = 'wpcom-adv-menu';
        }
    }
    return $args;
});

add_action('admin_enqueue_scripts', function (){
    global $pagenow;
    if($pagenow === 'nav-menus.php'){
        WPCOM::panel_script();
    }
});

add_action('admin_print_footer_scripts-nav-menus.php', function (){ ?>
    <script>_panel_options = <?php echo wpcom_init_menu_options();?>;</script>
    <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', \WPCOM::editor_settings(['textarea_name' => 'EDITOR-NAME', 'skip_init' => true]) );?></div>
    <?php
});

function wpcom_init_menu_options(){
    $res = array('type' => 'menu');
    $res['ver'] = THEME_VERSION;
    $res['theme-id'] = THEME_ID;
    $res['settings'] = apply_filters('wpcom_menu_metas', array());
    $res['framework_url'] = FRAMEWORK_URI;
    $res['framework_ver'] = FRAMEWORK_VERSION;
    $res = apply_filters('wpcom_menu_panel_options', $res);
    return wp_json_encode($res);
}

add_filter('wpcom_menu_metas', function($metas){
    $metas += [
        'url' => [
            'f' => 'object:custom',
            'name' => 'URL'
        ],
        'title' => [
            'name' => '导航标签'
        ],
        'image' => [
            'name' => '图标/图片',
            'type' => 'icon',
            'img' => 1
        ],
        'target' => [
            'name' => '在新标签页中打开链接',
            'type' => 't'
        ],
        'style' => [
            'f' => 'level:0',
            'name' => '下拉菜单风格',
            'type' => 'r',
            'ux' => 2,
            'o' => [
                ['' => '默认风格1||/themer/menu-style-0.png'],
                ['1' => '风格2||/themer/menu-style-1.png'],
                ['2' => '风格3||/themer/menu-style-2.png'],
                ['3' => '风格4||/themer/menu-style-3.png']
            ]
        ],
        'classes' => [
            'name' => 'CSS类',
            'desc' => '可选，即class属性'
        ],
        'xfn' => [
            'name' => '链接关系（XFN）',
            'desc' => '可选，rel属性，可设置nofollow'
        ]
    ];
    return $metas;
}, 5);

add_filter('manage_nav-menus_columns', 'wpcom_nav_menus_columns', 20);
function wpcom_nav_menus_columns(){
    return [];
}

/**
 * 默认禁止提交，为按钮添加disabled，前端渲染完成后再移除disabled
 */
add_action('load-nav-menus.php', function (){
    //开启缓冲
    ob_start("wpcom_menu_btn_replace");
});

add_action('admin_print_footer_scripts-nav-menus.php', function (){
    // 关闭缓冲
    if (ob_get_level() > 0) ob_end_flush();
});

function wpcom_menu_btn_replace($str){
    $regexp = "/<(input|button)[^<>]+name=\"save_menu\"[^<>]+>/i";
    $str = preg_replace_callback($regexp, "wpcom_menu_btn_replace_callback", $str);
    return $str;
}

function wpcom_menu_btn_replace_callback($matches){
    return preg_replace('/name=\"save_menu\"/i', 'name="save_menu" disabled', $matches[0]);
}