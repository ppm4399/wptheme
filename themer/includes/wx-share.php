<?php

namespace WPCOM\Themer;

class WXShare{
    public function __construct() {
        add_action('wp_ajax_wpcom_wx_config', [$this, 'wpcom_wx_config']);
        add_action('wp_ajax_nopriv_wpcom_wx_config', [$this, 'wpcom_wx_config']);
    }

    public function wpcom_wx_config(){
        global $options;
        $url = esc_url(urldecode($_POST['url']));
        if($url && !empty($options['wx_appid']) && !empty($options['wx_appsecret'])) {
            $wx = [];

            //生成签名的时间戳
            $wx['timestamp'] = time();

            $wx['appId'] = $options['wx_appid'] ?: '';

            //生成签名的随机串
            $wx['noncestr'] = 'www.wpcom.cn';

            // 通过access_token来获取jsapi_ticket
            $wx['jsapi_ticket'] = $this->get_jsapi_ticket();

            //分享的地址，不包含#及其后面部分
            $wx['url'] = $url;
            $string = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wx['jsapi_ticket'], $wx['noncestr'], $wx['timestamp'], $wx['url']);

            //生成签名
            $wx['signature'] = sha1($string);
            $wx['desc'] = $options['wx_desc'] ?: '';

            $img_url = \WPCOM::thumbnail_url(sanitize_text_field($_POST['ID']));
            $wx['thumb'] = $img_url ?: $options['wx_thumb'];
            $wx['thumb'] = is_numeric($wx['thumb']) ? \WPCOM::get_image_url( $wx['thumb'] ) : $wx['thumb'];

            $wx = apply_filters( 'wpcom_wx_config', $wx );
            wp_send_json($wx);
        }
        exit;
    }

    //获取微信公众号 ticket
    function get_jsapi_ticket() {
        $ticket = '';
        $old_ticket = is_multisite() ? get_network_option( get_main_network_id(), 'wx_ticket' ) : get_option('wx_ticket');
        if($old_ticket && isset($old_ticket['expires_in']) && $old_ticket['expires_in'] > time() + 60 && $old_ticket['ticket']){
            $ticket = $old_ticket['ticket'];
        }

        if($ticket === '') {
            global $options;
            $appid = $options['wx_appid'] ?: '';
            $secret = $options['wx_appsecret'] ?: '';

            $jsapi_ticket = apply_filters('wpcom_wxmp_jsapi_ticket', '', $appid, $secret);

            if($jsapi_ticket && isset($jsapi_ticket['ticket']) && $jsapi_ticket['ticket'] && $jsapi_ticket['appid'] === $appid && $jsapi_ticket['expires_in'] > time() + 60){
                $res = $jsapi_ticket;
            }else{
                $url = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi", \WPCOM::wxmp_token($appid, $secret));
                $result = wp_remote_request($url, ['method' => 'get']);

                if(is_array($result) && isset($result['body'])){
                    $res = json_decode($result['body'], true);
                }
            }

            if(isset($res) && isset($res['ticket'])) {
                $tickets = [ 'appid' => $appid, 'ticket' => $res['ticket'] ];
                $tickets['expires_in'] = time() + $res['expires_in'];

                if(is_multisite()){
                    update_network_option( get_main_network_id(), 'wx_ticket', $tickets );
                }else{
                    update_option('wx_ticket', $tickets);
                }

                $ticket = $res['ticket'];
            }
        }

        return $ticket;
    }
}