<?php defined( 'ABSPATH' ) || exit;

if( !function_exists('wpcom_get_related_post') ) :
    function wpcom_get_related_post( $showposts = 10, $title = '相关文章', $tpl = '', $class = '' ){
        if( $showposts == '0' ) return false;
        global $post, $options, $related_posts;
        $post_id = get_queried_object_id();
        if(!$post || $post->ID != $post_id) $post = get_post($post_id);

        $args = array(
            'post__not_in' => array($post->ID),
            'showposts' => $showposts,
            'orderby' => 'rand',
            'thumbnail' => $tpl !== 'list'
        );

        if(isset($options['related_order']) && $options['related_order']=='1'){
            $args['orderby'] = 'date';
        }

        $use_cat = false;
        if(isset($options['related_by']) && $options['related_by']=='1'){
            $tag_list = array();
            $tags = get_the_tags($post->ID);
            if($tags && !is_wp_error($tags)) {
                foreach ($tags as $tag) {
                    $tid = $tag->term_id;
                    if (!in_array($tid, $tag_list)) {
                        $tag_list[] = $tid;
                    }
                }
                $args['tag__in'] = $tag_list;
            }else{
                $use_cat = true;
            }
        }else{
            $use_cat = true;
        }

        if($use_cat){
            $cat_list = array();
            $categories = get_the_category($post->ID);
            if($categories) {
                foreach ($categories as $category) {
                    $cid = $category->term_id;
                    if (!in_array($cid, $cat_list)) {
                        $cat_list[] = $cid;
                    }
                }
            }
            $args['cat'] = join(',', $cat_list);
        }

        /**
         * 20241207: 如果支持缓存的话，无文章是缓存-1状态避免重复查询，有文章则会基于默认的文章查询返回缓存或者查询
         */
        $enable_cache = isset($options['enable_cache']) && $options['enable_cache'] == 1;
        if($enable_cache){
            $cache_key = md5(maybe_serialize($args));
            $cache = wp_cache_get( $cache_key, 'related_post' );

            if($cache == '-1'){ // 没有相关文章
                return false;
            }
        }

        $related_posts = WPCOM::get_posts($args);

        $output = '';
        if( $related_posts->have_posts() ) {
            $output .= '<h3 class="entry-related-title">'.$title.'</h3>';
            $output .=  '<ul class="entry-related '.$class.'">';
            while ( $related_posts->have_posts() ) { $related_posts->the_post();
                if ( $tpl ) {
                    ob_start();
                    get_template_part( $tpl );
                    $output .= ob_get_contents();
                    ob_end_clean();
                } else {
                    $output .= '<li class="related-item"><a href="' . get_the_permalink() . '" title="' . esc_attr(get_the_title()) . '">' . get_the_title() . '</a></li>';
                }
            }
            $output = str_replace(array('<h2 ', '</h2>'), array('<h4 ', '</h4>'), $output);
            $output .= '</ul>';
            $for_cache = 1;
        }else{ // 没有相关文章
            $for_cache = -1;
        }
        if(isset($for_cache) && $enable_cache) wp_cache_set( $cache_key, $for_cache, 'related_post', 3*DAY_IN_SECONDS );
        wp_reset_postdata();
        return $output;
    }
endif;

if( !function_exists('wpcom_related_post') ) :
    function wpcom_related_post($showposts = 10, $title = '相关文章', $tpl = '', $class = '', $img = false){
        $html = wpcom_get_related_post( $showposts, $title, $tpl, $class, $img );
        if($html) echo $html;
    }
endif;