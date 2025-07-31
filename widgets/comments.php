<?php
namespace WPCOM\Widgets;

class Comments extends Widget{
    public function __construct(){
        $this->widget_cssclass = 'widget_comments';
        $this->widget_description = '显示网站最新的评论列表';
        $this->widget_id = 'comments';
        $this->widget_name = '#最新评论';
        $this->settings = [
            'title'       => [
                'name' => '标题',
            ],
            'number'      => [
                'value'   => 10,
                'name' => '显示数量',
            ],
            'exclude_admin'      => [
                'name' => '排除管理员评论',
                't' => 't',
                'value' => '0'
            ]
        ];
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $number = !empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['value'];
        $query_args = [ 'post_status' => 'publish', 'status' => 'approve', 'number' => $number ];

        // 排除管理员评论
        $exclude_admin = isset($instance['exclude_admin']) && $instance['exclude_admin'] == '1';
        if($exclude_admin){
            $admins = get_users([ 'role' => 'Administrator', 'fields' => 'ID' ]);
            $query_args['author__not_in'] = $admins;
        }

        $comments_query = new \WP_Comment_Query();
        $comments = $comments_query->query( $query_args );
        $this->widget_start( $args, $instance );

        if ( $comments ) : ?>
            <ul>
                <?php foreach ( $comments as $comment ) :
                    if($comment->user_id){
                        $author_url = get_author_posts_url( $comment->user_id );
                        $userdata = get_userdata( $comment->user_id );
                        $display_name = apply_filters( 'get_comment_author', $userdata->display_name, $comment->comment_ID, $comment );
                        $attr = 'target="_blank"';
                    }else{
                        $author_url = $comment->comment_author_url ?: '#';
                        $display_name = $comment->comment_author;
                        $attr = 'target="_blank" rel=nofollow';
                    } ?>
                    <li>
                        <div class="comment-info">
                            <a href="<?php echo esc_url($author_url);?>" <?php echo $attr; if($comment->user_id){ echo ' class="j-user-card" data-user="'.$comment->user_id.'"';}?>>
                                <?php echo get_avatar( $comment, 60, '', $display_name ?: __('Anonymous', 'wpcom') );?>
                                <span class="comment-author"><?php echo $display_name ?: __('Anonymous', 'wpcom');?></span>
                            </a>
                            <span><?php echo date(get_option('date_format'), strtotime($comment->comment_date)); ?></span>
                        </div>
                        <div class="comment-excerpt">
                            <p><?php comment_excerpt( $comment );?></p>
                        </div>
                        <p class="comment-post">
                            <?php _e('Comment on', 'wpcom');?> <a href="<?php echo get_permalink($comment->comment_post_ID); ?>" target="_blank"><?php echo get_the_title($comment->comment_post_ID);?></a>
                        </p>
                    </li>
                <?php endforeach;?>
            </ul>
        <?php
        else:
            echo '<p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">'.__('No comments', 'wpcom').'</p>';
        endif;
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean(), 3600 );
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( Comments::class );
} );