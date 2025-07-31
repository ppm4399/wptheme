<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'get_avatar_url', 'wpcom_replace_avatar_url', 20 );
function wpcom_replace_avatar_url($url){
    global $options;
    $gravatar = isset($options["wafc_gravatar"]) ? $options["wafc_gravatar"] : 0;
    $gravatar = !$gravatar ? 1 : $gravatar;
    if($gravatar && $gravatar > 0){
        $gravatars = array('https://www.gravatar.com/avatar', '//cn.gravatar.com/avatar', '//g.izt6.com/avatar', '//fdn.geekzu.org/avatar');
            // 匹配头像链接
            if($gravatar=='1'){
                $patterns = '/(http:|https:)?\/\/[0-9a-zA-Z]+\.gravatar\.com\/avatar/';
            }else{
                $patterns = '/\/\/[0-9a-zA-Z]+\.gravatar\.com\/avatar/';
            }
            // 使用可以访问到头像图片替换
        $url = preg_replace($patterns, $gravatars[$gravatar-1], $url);
    }
    return $url;
}