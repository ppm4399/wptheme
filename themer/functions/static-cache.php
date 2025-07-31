<?php
namespace WPCOM\Themer;
defined( 'ABSPATH' ) || exit;

class Static_Cache{
    static $folder = 'wpcom';
    private $enable;
    function __construct(){
        global $options;
        add_action('wp_enqueue_scripts', [$this, 'enqueue_style'], 20);
        add_action('wpcom_static_cache_clear', [$this, 'cron']);
        add_action('wpcom_static_cache_check', [$this, 'check'], 10, 2);
        add_action('switch_theme', [$this, 'rebuild']);
        add_action('wpcom_options_updated', [$this, 'rebuild']);
        add_action('wpcom-member-pro_options_updated', [$this, 'rebuild']);
        add_action('wp_ajax_wpcom_ve_save', [$this, 'rebuild'], 1);
        $this->enable = false;
        if(self::get_folder() && (!isset($options['css_cache']) || $options['css_cache']=='1')){
            $this->enable = true;
        }
    }
    public static function get_folder(){
        return apply_filters('wpcom_static_cache_path', self::$folder);
    }
    public static function dir(){
        $_dir = _wp_upload_dir();
        if(self::get_folder()){
            $dir = $_dir['basedir'] . '/' . self::get_folder();
            if(wp_mkdir_p($dir)) return $dir;
        }
    }
    public static function url(){
        if(self::get_folder()){
            $url = _wp_upload_dir();
            return $url['baseurl'] . '/' . self::get_folder();
        }
    }
    function build_css(){
        $child = is_child_theme();
        $css = $child ? '/style.css' : '/css/style.css';
        $path = get_stylesheet_directory() . $css;
        $dir = self::dir();
        $time = get_option('wpcom_static_time');
        $current = current_time('timestamp', 1);
        if(!$time){
            $time = $current;
            update_option('wpcom_static_time', $time);
        }
        $file = '/style.' . THEME_VERSION . '.' . $time . '.css';
        if( is_singular() && (is_page_template('page-home.php') || is_singular('page_module')) ) {
            global $post;
            $file = '/style.p' . $post->ID .'.'. THEME_VERSION . '.' . $time . '.css';
        }
        $build = 1;
        if(file_exists($dir . $file)){ // 缓存文件存在，比较下修改时间
            $build = 0;
            // css文件修改时间晚于缓存时间，则表示有修改，更新缓存文件
            if(filemtime($path) > filemtime($dir . $file)){
                $build = 1;
            }
        }
        if($build && file_exists($path)){
            $css_str = @file_get_contents($path);
            if($child){ // 处理子主题引用的父主题样式
                preg_match('/\@import\s+url\([\'"]?(\.\.\/([^\)\'"]+))[\'"]?/im', $css_str, $matches);
                if($matches && isset($matches[1])){
                    $_parent_theme = preg_replace('/^\.\./i', '', $matches[1]);
                    $parent_theme = get_theme_root() . $_parent_theme;
                    $parent_css_str = @file_get_contents($parent_theme);
                    preg_match('/\@import\s+url\([\'"]?\.\.\/[^\)\'"]+[\'"]?\);?/im', $css_str, $m);
                    if($m && isset($m[0]) && $m[0]){
                        $css_str = str_replace($m[0], $parent_css_str, $css_str);
                    }
                }
            }
            $css_str = $this->replace_images_path($css_str);
            $css_str .= apply_filters('wpcom_custom_css', '');
            if($dir) {
                self::save_file($dir . $file, $css_str, self::url() . $file);
                wp_schedule_single_event( time() + 5, 'wpcom_static_cache_check', [
                    'file' => $dir . $file,
                    'current' => $current
                ] );
            }
        }
        if(file_exists($dir . $file)){
            return self::url() . $file;
        }
        return false;
    }
    function replace_images_path($str){
        $url = get_theme_root_uri() . '/' . get_template();
        $str = str_replace('../images/', $url . '/images/', $str);
        return $str;
    }
    function enqueue_style(){
        if($this->enable && self::dir() && $css = $this->build_css()){
            wp_deregister_style('stylesheet');
            $css = preg_replace('/^(http:|https:)/i', '', $css);
            wp_register_style('stylesheet', $css, [], THEME_VERSION);
            do_action('wpcom_enqueue_cache_style');
        }else{
            add_action( 'wp_head', [$this, 'custom_css'], 20 );
        }
    }
    function custom_css(){
        $css = apply_filters('wpcom_custom_css', '');
        if($css) echo '<style>'.$css.'</style>' . "\r\n";
    }
    function rebuild(){
        delete_option('wpcom_static_time');
    }
    public static function get_font_css($url){
        $dir = self::dir();
        if(!$dir) return $url;
        $file = '/fonts.' . substr(md5($url), 8, 16) . '.css';
        $path = $dir . $file;
        $timestamp = current_time('timestamp', 1);
        if(file_exists($path)) {
            $url = self::url() . $file;
            if(!current_user_can('manage_options')) return $url;
            // 检查字体文件是否全部本地化，超过8小时就不检查了
            if($timestamp - filemtime($path) < 28800){
                $css_str = @file_get_contents($path);
                $css_str = self::load_font($css_str, true, $url);
                if($css_str){
                    self::save_file($path, $css_str, $url);
                }
            }
            return $url;
        };
        if(!current_user_can('manage_options')) return $url;

        // 10分钟内不重复执行
        $last_time = get_option('wpcom_last_get_font_css');
        $last_time = $last_time && is_array($last_time) ? $last_time : ['times' => 0, 'last' => 0];
        if(isset($last_time['last']) && $last_time['last'] && $timestamp - $last_time['last'] < 600) return $url;

        $http_options = [
            'timeout' => 5,
            'sslverify' => false,
            'user-agent' => \WPCOM::request_ua(),
            'headers' => [
                'referer' => home_url('/')
            ]
        ];
        if(preg_match('/^\/\//i', $url)) $url = 'https:' . $url;
        $get = wp_remote_get($url, $http_options);
        $is_success = 0;
        if (!is_wp_error($get) && 200 === $get['response']['code']) {
            $get['body'] = self::load_font($get['body'], false, $url);
            self::save_file($path, $get['body'], self::url() . $file);
            $is_success = 1;
            $url = self::url() . $file;
        }else if(is_wp_error($get) && strpos($url, 'chinese-fonts-cdn.deno.dev') !== false){
            // chinese-fonts-cdn.deno.dev 地址下载可能失败，尝试使用 chinese-fonts-cdn.netlify.app 地址重试
            $url = str_replace('chinese-fonts-cdn.deno.dev', 'chinese-fonts-cdn.netlify.app', $url);
            $get = wp_remote_get($url, $http_options);
            if (!is_wp_error($get) && 200 === $get['response']['code']) {
                $get['body'] = self::load_font($get['body'], false, $url);
                self::save_file($path, $get['body'], self::url() . $file);
                $is_success = 1;
                $url = self::url() . $file;
            }
        }

        // 失败超过3次自动关闭选项
        $last_time['times'] += 1;
        if($last_time['times'] > 3 && (!$is_success || $timestamp - $last_time['last'] < 3600)){
            global $wpcom_panel;
            $last_time['times'] = 0;
            $wpcom_panel->set_theme_options(['google-font-local' => '0']);
        }else if($timestamp - $last_time['last'] > 2592000){
            // 上一次请求是1个月前，则重新计算
            $last_time['times'] = 1;
        }
        // 记录最后下载时间
        $last_time['last'] = $timestamp;
        update_option('wpcom_last_get_font_css', $last_time);
        return $url;
    }
    static function load_font($str, $recheck, $css_url){
        $changed = false;
        preg_match_all('/url\([\'"]?([^\'"\)]+)[\'"]?\)/i', $str, $matches);
        if($matches && isset($matches[1]) && $matches[1]){
            $http_options = [
                'timeout' => 3,
                'sslverify' => false,
                'user-agent' => \WPCOM::request_ua(),
                'headers' => [
                    'referer' => home_url('/')
                ]
            ];
            $fonts = [];
            foreach($matches[1] as $i => $font){
                $arr = explode('.', $font);
                $ext = array_pop($arr);
                $file = '/fonts.' . substr(md5($font), 8, 16) . '.' . $ext;
                $path = self::dir() . $file;
                $url = '.' . $file;
                if(file_exists($path)) {
                    $fonts[$i] = $url;
                }else if($recheck && preg_match('/\.\/fonts\.([a-zA-Z0-9-_]+)\.([a-zA-Z0-9]+)$/i', $font)){ // 已经是本地文件了
                    $fonts[$i] = $font;
                }else{
                    // 检查是否为相对路径
                    if (strpos($font, './') === 0 && $css_url) {
                        $font = dirname($css_url) . '/' . ltrim($font, './'); // 构建完整URL
                    }
                    $get = wp_remote_get($font, $http_options);
                    if (!is_wp_error($get) && 200 === $get['response']['code']) {
                        self::save_file($path, $get['body'], $url, 'font/'.$ext);
                        $fonts[$i] = $url;
                        if($recheck) $changed = true;
                    }else{
                        $fonts[$i] = $font;
                    }
                }
            }
            $str = str_replace( $matches[1], $fonts, $str );
        }
        return $recheck && !$changed ? false : $str;
    }
    private static function save_file($path, $body, $url, $type = 'text/css'){
        if($path && $body){
            @file_put_contents($path, $body);
            // 基于wp_handle_upload钩子，兼容云储存插件同步
            apply_filters(
                'wp_handle_upload',
                [
                    'file'  => $path,
                    'url'   => $url,
                    'type'  => $type,
                    'error' => false,
                ],
                'sideload'
            );
        }
    }
    function cron(){
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $dir = self::dir();
        if($dir && $files = list_files($dir, 1)){
            // 删除超过30天的缓存文件，字体文件超过一年删除
            foreach ($files as $file){
                if(preg_match('/fonts\.([a-zA-Z0-9-_]+)\.([a-zA-Z0-9]+)$/i', $file)){
                    $expired = 2592000 * 12;
                }else{
                    $expired = 2592000;
                }

                if(current_time('timestamp', 1) - filemtime($file) > $expired){
                    @unlink($file);
                }
            }
        }
    }
    function check($file, $current){
        if($file && $current && !file_exists($file)) {
            global $wpcom_panel;
            $time = get_option('wpcom_static_time');
            // 15分钟后依然检查失败则关闭css缓存功能
            if($time && $current - $time > 900) $wpcom_panel->set_theme_options(['css_cache' => '0', 'google-font-local' => '0']);
        }
    }
}