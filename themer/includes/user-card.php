<?php
namespace WPCOM\Member;

defined( 'ABSPATH' ) || exit;

class User_Card{
    function __construct(){
        if(apply_filters( 'wpcom_member_show_profile' , true ) === false) return false;

        add_action('wp_ajax_wpcom_user_card', [$this, 'user_card']);
        add_action('wp_ajax_nopriv_wpcom_user_card', [$this, 'user_card']);

        add_filter('wpcom_localize_script', [$this, 'localize_script']);
        add_filter('get_comment_author_link', [$this, 'comment_author_link'], 20, 3);
    }

    function user_card(){
        $uid = isset($_REQUEST['user']) && $_REQUEST['user'] ? sanitize_text_field($_REQUEST['user']) : '';
        $res = ['result' => -1];
        if($uid){
            global $wpcom_member;
            $user = get_user_by('ID', $uid);
            if($user && isset($user->ID)){
                $res['result'] = 0;
                $res['html'] = $wpcom_member->load_template('user-card', ['user' => $user]);
            }
        }
        wp_send_json($res);
    }

    function localize_script($script){
        $script['user_card'] = 1;
        return $script;
    }

    function comment_author_link($comment_author_link, $comment_author, $comment_id){
        $comment = get_comment( $comment_id );
        if( $comment && isset($comment->user_id) && $comment->user_id && class_exists(Member::class) && get_userdata( $comment->user_id )){
            $pattern = '/<a\s+([^>]*)class=["\']([^"\']*)["\']/i';
            $replacement = '<a $1class="$2 j-user-card" data-user="' . $comment->user_id . '" target="_blank"';
            $comment_author_link = preg_replace($pattern, $replacement, $comment_author_link);
        }
        return $comment_author_link;
    }
}