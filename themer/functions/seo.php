<?php defined('ABSPATH') || exit;

add_action('wp_head', 'wpcom_seo', 1);
function wpcom_seo() {
    global $options, $post, $wp_query;
    $keywords = '';
    $description = '';
    $seo = '';
    if (!isset($options['seo']) || $options['seo'] == '1') {
        $open_graph = !isset($options['open_graph']) || $options['open_graph'];
        if (!isset($options['seo'])) {
            $options = isset($options) ? $options : array();
            $options['keywords'] = '';
            $options['description'] = '';
            $options['fav'] = '';
        }
        if (is_home() || is_front_page()) {
            $keywords = str_replace('，', ',', wp_strip_all_tags($options['keywords'], true));
            $description = wp_strip_all_tags($options['description'], true);
        } else if (is_singular()) {
            $keywords = str_replace('，', ',', wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_keywords', true), true));
            if ($keywords == '' && is_singular('post')) {
                $post_tags = get_the_tags();
                if ($post_tags) {
                    foreach ($post_tags as $tag) {
                        $keywords = $keywords . $tag->name . ",";
                    }
                }
                $keywords = rtrim($keywords, ',');
            } else if ($keywords == '' && is_singular('page')) {
                $keywords = $post->post_title;
            } else if (is_singular('product')) {
                $product_tag = get_the_terms($post->ID, 'product_tag');
                if ($product_tag) {
                    foreach ($product_tag as $tag) {
                        $keywords = $keywords . $tag->name . ",";
                    }
                }
                $keywords = rtrim($keywords, ',');
            } elseif ($keywords == '' && is_singular('qa_post')) {
                global $qa_options;
                if (!isset($qa_options)) $qa_options = get_option('qa_options');
                if (isset($qa_options['enable_related']) && $qa_options['related_by']) {
                    $keywords = get_post_meta($post->ID, '_qa_tags', true);
                }
            }
            $description = wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_description', true), true);
            if ($description == '' && !post_password_required($post)) {
                if ($post->post_excerpt) {
                    $description = utf8_excerpt(wp_strip_all_tags($post->post_excerpt, true), 160);
                } else {
                    $content = wp_strip_all_tags(strip_shortcodes($post->post_content), true);
                    if ($content) $description = utf8_excerpt($content, 160);
                }
                if ($description == '' && function_exists('is_wpcom_member_page') && is_wpcom_member_page('profile')) {
                    global $profile;
                    $description = isset($profile->description) ? wp_strip_all_tags($profile->description, true) : '';
                    if (isset($profile->display_name)) $keywords .= ',' . $profile->display_name;
                }
            }
            // 单独处理问答分类
            if ($wp_query->get('qa_cat')) {
                $cat = get_term_by('slug', $wp_query->get('qa_cat'), 'qa_cat');
                if ($cat) {
                    $wp_query->set('title', $cat->name);
                    $keywords = get_term_meta($cat->term_id, 'wpcom_seo_keywords', true);
                    $keywords = $keywords != '' ? str_replace('，', ',', wp_strip_all_tags($keywords, true)) : $cat->name;

                    $description = get_term_meta($cat->term_id, 'wpcom_seo_description', true);
                    $description = $description != '' ? $description : term_description($cat->term_id);
                    $description = wp_strip_all_tags($description, true);
                }
            }
        } else if ((is_category() || is_tag() || is_tax()) && $term = get_queried_object()) {
            $keywords = get_term_meta($term->term_id, 'wpcom_seo_keywords', true);
            $keywords = $keywords != '' ? str_replace('，', ',', wp_strip_all_tags($keywords, true)) : single_cat_title('', false);

            $description = get_term_meta($term->term_id, 'wpcom_seo_description', true);
            $description = $description != '' ? $description : term_description();
            $description = wp_strip_all_tags($description, true);
        } else if (function_exists('is_woocommerce') && is_shop()) {
            $post = get_post(wc_get_page_id('shop'));
            $keywords = str_replace('，', ',', wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_keywords', true), true));
            if (!$keywords) $keywords = $post->post_title;
            $description = wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_description', true), true);
            if (!$description) {
                if ($post->post_excerpt) {
                    $description = utf8_excerpt(wp_strip_all_tags($post->post_excerpt, true), 160);
                } else {
                    $content = wp_strip_all_tags(strip_shortcodes($post->post_content), true);
                    if ($content) $description = utf8_excerpt($content, 160);
                }
            }
        }

        $wx_thumb = isset($options['wx_thumb']) ? $options['wx_thumb'] : '';
        $wx_thumb = is_numeric($wx_thumb) ? WPCOM::get_image_url($wx_thumb) : $wx_thumb;
        $keywords = apply_filters('wpcom_seo_keywords', $keywords);
        $description = apply_filters('wpcom_seo_description', $description);
        if ($keywords) $seo .= '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        if ($description) $seo .= '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        if (is_singular() && !is_front_page()) {
            global $paged;
            if (!$paged) {
                $paged = 1;
            }
            $url = get_pagenum_link($paged);

            $img_url = WPCOM::thumbnail_url($post->ID, 'full');
            $GLOBALS['post-thumb'] = $img_url;
            if (!$img_url) {
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
                if (isset($matches[1]) && isset($matches[1][0])) {
                    $img_url = $matches[1][0];
                }
            }

            $image = $img_url ?: $wx_thumb;

            $type = 'article';
            if (is_singular('page')) {
                $type = 'webpage';
            } else if (is_singular('product')) {
                $type = 'product';
            }
            if ($open_graph) {
                $post_title = $wp_query->get('qa_cat') && $wp_query->get('title') ? $wp_query->get('title') : $post->post_title;
                $seo .= '<meta property="og:type" content="' . $type . '">' . "\n";
                $seo .= '<meta property="og:url" content="' . $url . '">' . "\n";
                $seo .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo("name")) . '">' . "\n";
                $seo .= '<meta property="og:title" content="' . esc_attr($post_title) . '">' . "\n";
                if ($image) $seo .= '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
                if ($description) $seo .= '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
        } else if (is_home() || is_front_page()) {
            global $page;
            if (!$page) {
                $page = 1;
            }
            $url = get_pagenum_link($page);

            $image = $wx_thumb;
            $title = isset($options['home-title']) ? $options['home-title'] : '';;

            if ($title == '') {
                $desc = get_bloginfo('description');
                if ($desc) {
                    $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : ' - ') . $desc;
                } else {
                    $title = get_option('blogname');
                }
            }
            if ($open_graph) {
                $seo .= '<meta property="og:type" content="webpage">' . "\n";
                $seo .= '<meta property="og:url" content="' . $url . '">' . "\n";
                $seo .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo("name")) . '">' . "\n";
                $seo .= '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
                if ($image) $seo .= '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
                if ($description) $seo .= '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
        } else if (is_category() || is_tag() || is_tax()) {
            global $paged;
            if (!$paged) {
                $paged = 1;
            }
            $url = get_pagenum_link($paged);
            $image = $wx_thumb;
            if ($open_graph) {
                $seo .= '<meta property="og:type" content="webpage">' . "\n";
                $seo .= '<meta property="og:url" content="' . $url . '">' . "\n";
                $seo .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo("name")) . '">' . "\n";
                $seo .= '<meta property="og:title" content="' . esc_attr(single_cat_title('', false)) . '">' . "\n";
                if ($image) $seo .= '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
                if ($description) $seo .= '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
        }
    }

    if (isset($options['canonical']) && $options['canonical'] == '1' && is_singular()) {
        $id = get_queried_object_id();
        if (0 !== $id && $url = wp_get_canonical_url($id)) {
            $seo .= '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        }
    }
    if ((is_attachment() && isset($options['noindex_attachment']) && $options['noindex_attachment']) || (is_singular() && get_option('blog_public') && '1' == get_post_meta($post->ID, 'wpcom_seo_noindex', true))) {
        $seo .= '<meta name="robots" content="noindex,nofollow">' . "\n";
    }
    $seo .= '<meta name="applicable-device" content="pc,mobile">' . "\n";
    $seo .= '<meta http-equiv="Cache-Control" content="no-transform">' . "\n";
    if (isset($options['fav']) && $options['fav']) {
        $url = is_numeric($options['fav']) ? WPCOM::get_image_url($options['fav']) : $options['fav'];
        $seo .= '<link rel="shortcut icon" href="' . $url . '">' . "\n";
    }

    echo apply_filters('wpcom_head_seo', $seo);
}

// wp title
add_filter('wp_title_parts', 'wpcom_title_parts', 20);
if (!function_exists('wpcom_title')) :
    function wpcom_title_parts($parts) {
        global $post, $options, $wp_title_parts, $wp_query;
        if (!isset($options['seo']) || $options['seo'] == '1') {
            if (is_tax() && get_queried_object()) {
                $parts = array(single_term_title('', false));
            }
            $title_array = array();
            foreach ($parts as $t) {
                if (trim($t)) $title_array[] = $t;
            }
            if (is_singular()) {
                // 问答分类插件已经处理，排除
                if (!$wp_query->get('qa_cat')) {
                    $seo_title = wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_title', true), true);
                    if ($seo_title != '') $title_array[0] = $seo_title;
                }
            } else if ( (is_category() || is_tag() || is_tax()) && $term = get_queried_object()) {
                $seo_title = get_term_meta($term->term_id, 'wpcom_seo_title', true);
                $seo_title = $seo_title != '' ? $seo_title : '';
                if ($seo_title != '') $title_array[0] = $seo_title;
            } else if (function_exists('is_woocommerce') && is_shop()) {
                $post = get_post(wc_get_page_id('shop'));
                $seo_title = wp_strip_all_tags(get_post_meta($post->ID, 'wpcom_seo_title', true), true);
                if ($seo_title != '') $title_array[0] = $seo_title;
            }
            $wp_title_parts = $title_array;
        } else {
            $wp_title_parts = $parts;
        }

        return $wp_title_parts;
    }
endif;

add_filter('wp_title', 'wpcom_title', 10, 3);
if (!function_exists('wpcom_title')) :
    function wpcom_title($title, $sep, $seplocation) {
        global $paged, $page, $options, $wp_title_parts;

        if (!isset($options['seo']) || $options['seo'] == '1') {
            if ((is_home() || is_front_page()) && isset($options['home-title']) && $options['home-title']) {
                $_title = $options['home-title'];
                if ($paged >= 2 || $page >= 2) $_title = $_title . $sep . sprintf(__('Page %s', 'wpcom'), max($paged, $page));
                return $_title;
            }

            $prefix = !empty($title) ? $sep : '';
            $title = $seplocation == 'right' ? implode($sep, array_reverse($wp_title_parts)) . $prefix : $prefix . implode($sep, $wp_title_parts);
        }

        // 首页标题
        if (empty($title) && (is_home() || is_front_page())) {
            $desc = get_bloginfo('description');
            if ($desc) {
                $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : $sep) . $desc;
            } else {
                $title = get_option('blogname');
            }
            // 增加页数
            if ($paged >= 2 || $page >= 2) $title = $title . $sep . sprintf(__('Page %s', 'wpcom'), max($paged, $page));
        } else {
            if ($paged >= 2 || $page >= 2) // 增加页数
                $title = $title . sprintf(__('Page %s', 'wpcom'), max($paged, $page)) . $sep;
            if ('right' == $seplocation) {
                $title = $title . get_option('blogname');
            } else {
                $title = get_option('blogname') . $title;
            }
        }
        return $title;
    }
endif;


// JSON_LD数据
add_action('wp_footer', 'wpcom_jsonLd_data', 50);
function wpcom_jsonLd_data() {
    if (!is_singular() || is_attachment() || is_page() || is_singular('product') || is_front_page()) return;
    global $post;
    $id = get_queried_object_id();
    $post = get_post($id);
    $author = get_the_author_meta('ID');
    $author_data = array(
        '@type' =>  'Person',
        'name' => wp_strip_all_tags(get_the_author()),
        'url' => get_author_posts_url($author)
    );
    if (defined('WPMX_VERSION') && is_singular('post')) {
        $author_data['image'] = get_avatar_url($author);
    }
    $image = wpcom_jsonLd_imgs();
?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Article",
            "@id": "<?php the_permalink($post); ?>",
            "url": "<?php the_permalink($post); ?>",
            "headline": "<?php echo get_the_title($post); ?>",
            <?php if ($image && $image !== '[]') { ?> "image": <?php echo wpcom_jsonLd_imgs(); ?>,
            <?php } ?> "description": "<?php echo utf8_excerpt(wp_strip_all_tags(strip_shortcodes(get_the_excerpt()), true), 160); ?>",
            "datePublished": "<?php the_time(DATE_W3C); ?>",
            "dateModified": "<?php the_modified_time(DATE_W3C); ?>",
            "author": <?php echo wp_json_encode($author_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        }
    </script>
<?php }

function wpcom_jsonLd_imgs() {
    global $post;
    $imgs = '[]';

    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches, PREG_PATTERN_ORDER);

    if (isset($matches[1]) && isset($matches[1][2])) { // 有3张图片
        for ($i = 0; $i < 3; $i++) {
            if (preg_match('/^\/\//i', $matches[1][$i])) $matches[1][$i] = 'http:' . $matches[1][$i];
        }
        $imgs = '["' . $matches[1][0] . '","' . $matches[1][1] . '","' . $matches[1][2] . '"]';
    } else if ($img_url = (isset($GLOBALS['post-thumb']) ? $GLOBALS['post-thumb'] : WPCOM::thumbnail_url($post->ID))) {
        if (preg_match('/^\/\//i', $img_url)) $img_url = 'http:' . $img_url;
        $imgs = '"' . $img_url . '"';
    }
    return $imgs;
}

add_action('transition_post_status', 'wpcom_baidu_pre_submit', 10, 3);
function wpcom_baidu_pre_submit($new_status, $old_status, $post) {
    if ($new_status != 'publish' || $new_status == $old_status || ($post->post_type != 'post' && $post->post_type != 'product')) return false;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
    global $options;
    if (isset($post->ID) && (
        (isset($options['zz-submit']) && $options['zz-submit']) ||
        (isset($options['ks-submit']) && $options['ks-submit']) ||
        (isset($options['bing-apikey']) && $options['bing-apikey'])
        )
    ) {
        global $_pre_submit;
        $_pre_submit = $post->ID;
    }
}

add_action('wp_insert_post', 'wpcom_baidu_submit', 50, 2);
function wpcom_baidu_submit($post_ID, $post) {
    global $_pre_submit;
    if (isset($_pre_submit) && $post->post_status == 'publish' && $_pre_submit == $post_ID) {
        $args = array($post_ID, $post);
        // 10s后执行定时任务，避免post meta还未保存到数据库的情况
        wp_schedule_single_event(time() + 10, 'wpcom_post_submit_cron', $args);
        $_pre_submit = 0;
    }
}

add_action('wpcom_post_submit_cron', 'wpcom_post_submit_cron_fun', 10, 2);
function wpcom_post_submit_cron_fun($post_ID, $post) {
    global $options;
    $zz_url = isset($options['zz-submit']) && $options['zz-submit'] ? $options['zz-submit'] : '';
    $ks_url = isset($options['ks-submit']) && $options['ks-submit'] ? $options['ks-submit'] : '';
    $bing_apikey = isset($options['bing-apikey']) && $options['bing-apikey'] ? $options['bing-apikey'] : '';
    $post_url = get_permalink($post_ID);
    if (trim($ks_url) !== '') { // 有快速抓取则优先提交快速抓取
        $req = wpcom_submit2baidu($post_url, $ks_url);
        $body = $req && isset($req['body']) ? json_decode($req['body'], true) : [];
        if($body && isset($body['success']) && $body['success'] == 1){ // 快速收录提交成功
            return;
        }else if(trim($zz_url) !== '' && $req['body']){ // 有设置普通收录，则提交普通收录
            wpcom_submit2baidu($post_url, $zz_url);
        }
    }else if(trim($zz_url) !== ''){
        wpcom_submit2baidu($post_url, $zz_url);
    }
    if(trim($bing_apikey) !== ''){
        $bing_url = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' . trim($bing_apikey);
        $parsedUrl = parse_url($post_url);
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'http';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $siteUrl = $scheme . '://' . $host;
        $data = [
            'siteUrl' => $siteUrl,
            'urlList' => [$post_url]
        ];
        $bing_req = wp_remote_post(
            $bing_url,
            array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($data)
            )
        );
        wpcom_add_log($post_url . ' - ' . wp_json_encode($bing_req, JSON_UNESCAPED_UNICODE));
    }
}

function wpcom_submit2baidu($post_url, $submit_url){
    $req = wp_remote_post(
        str_replace(' ', '', $submit_url),
        array(
            'timeout' => 15,
            'headers' => array('Content-Type' => 'text/plain'),
            'body' => $post_url
        )
    );
    wpcom_add_log($post_url . ' - ' . wp_json_encode($req, JSON_UNESCAPED_UNICODE));
    return $req;
}