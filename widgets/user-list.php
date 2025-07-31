<?php
namespace WPCOM\Widgets;

class User_List extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_user_list';
        $this->widget_description = '在页面边栏展示指定用户信息';
        $this->widget_id = 'user-list';
        $this->widget_name = '#推荐用户';
        $this->settings = array(
            'title' => array(
                'name' => '标题'
            ),
            'follow' => array(
                'name' => '关注',
                'type' => 'toggle',
                'desc' => '是否显示关注按钮，需要先开启关注功能（主题设置>用户中心>用户关注）'
            ),
            'from' => array(
                'name' => '调用方式',
                't' => 's',
                'value' => 'id',
                'o' => array(
                    'all' => '所有用户',
                    'id' => '根据用户ID',
                    'group' => '根据用户分组'
                )
            ),
            'ids' => array(
                'f' => 'from:id',
                'name' => '用户ID',
                'desc' => '多个用户ID请用逗号分隔，按ID顺序显示'
            ),
            'group' => array(
                'f' => 'from:group',
                'name' => '用户分组',
                't' => 's',
                'tax' => 'user-groups'
            ),
            'orderby' => array(
                'f' => 'from:all,from:group',
                'name' => '排序依据',
                't' => 's',
                'value' => 'registered',
                'o' => array(
                    'registered' => '注册时间',
                    'post_count' => '文章数量',
                    'last_post' => '最后发文章',
                    'last_comment' => '最后评论'
                )
            ),
            'order' => array(
                'f' => 'from:all,from:group',
                'name' => '排序顺序',
                't' => 'r',
                'ux' => 1,
                'value' => 'DESC',
                'o' => array(
                    'ASC' => '顺序：1,2,3',
                    'DESC' => '倒序：3,2,1',
                )
            ),
            'number' => array(
                'f' => 'from:all,from:group',
                'name' => '显示数量',
                'value' => 5
            ),
        );
        parent::__construct();

        add_filter('wpcom_widget_form_instance', function($instance, $that){
            if(isset($that->widget_id) && $that->widget_id === 'user-list'){
                if(!isset($instance['from']) && isset($instance['ids']) && $instance['ids']){
                    $instance['from'] = 'id';
                }
            }
            return $instance;
        }, 10, 2);
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $follow = empty( $instance['follow'] ) ? 0 : $instance['follow'];
        $this->widget_start( $args, $instance );
        ?>
        <ul class="user-list-wrap">
            <?php
            $users = $this->get_users($instance);
            if($users){
            foreach ($users as $user) {
                $author_url = get_author_posts_url($user->ID); ?>
                <li class="user-list-item j-user-card" data-user="<?php echo $user->ID;?>">
                    <a href="<?php echo $author_url ?>" target="_blank"><?php echo get_avatar($user->ID);?></a>
                    <div class="user-list-content">
                        <div class="user-list-hd">
                            <a class="user-list-name" href="<?php echo $author_url ?>" target="_blank">
                                <?php echo apply_filters('wpcom_user_display_name', '<span class="user-name-inner">'.$user->display_name.'</span>', $user->ID); ?>
                            </a>
                            <?php if(class_exists('WPCOM_Follow') && $follow) {?>
                                <a class="user-list-btn btn-follow j-follow" data-user="<?php echo $user->ID ?>">
                                    <?php echo apply_filters('wpcom_follow_btn_html', '');?>
                                </a>
                            <?php } ?>
                        </div>
                        <a href="<?php echo $author_url ?>">
                            <p class="user-list-desc"><?php echo $user->description; ?></p>
                        </a>
                    </div>
                </li>
            <?php } } ?>
        </ul>

        <?php
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }

    function get_users($instance){
        $options = $GLOBALS['wpmx_options'];
        $from = empty( $instance['from'] ) ? $this->settings['from']['value'] : $instance['from'];
        if($from === 'id'){
            $_ids = str_replace("，",",", isset($instance['ids']) && $instance['ids'] ? $instance['ids'] : '');
            $ids = explode(',', $_ids);
            $args['include'] = $ids;
            $args['orderby'] = 'include';
        }else{
            $number = empty( $instance['number'] ) ? $this->settings['number']['value'] : $instance['number'];
            $orderby = empty( $instance['orderby'] ) ? $this->settings['orderby']['value'] : $instance['orderby'];
            $order = empty( $instance['order'] ) ? $this->settings['order']['value'] : $instance['order'];
            $args = array('number' => $number, 'orderby' => $orderby, 'order' => $order);

            if( $from === 'group' && isset($instance['group']) && $instance['group']) {
                $user_ids = get_objects_in_term( explode(',', $instance['group']), 'user-groups' );
                if( $user_ids && !is_wp_error($user_ids) ) $args['include'] = $user_ids;
            }
        }

        $member_reg_active = $options && isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';
        if( $member_reg_active!='0' ){
            // 开启审核则只显示审核通过的用户
            $args['user_status'] = 0;
        }

        $users_query = new \WP_User_Query( $args );
        $users = $users_query->get_results();
        if( $users && !is_wp_error($users) ) {
            if(isset($orderby) && $orderby === 'last_post'){
                $users = $this->orderby_last_post($users, $args);
            }else if(isset($orderby) && $orderby === 'last_comment'){
                $users = $this->orderby_last_comment($users, $args);
            }
            return $users;
        }
    }

    function orderby_last_post($users, $args){
        $asc = (isset($args['order']) && 'ASC' === strtoupper($args['order']));
        $post_dates = array();
        if ($users) {
            foreach ($users as $user) {
                $ID = $user->ID;
                $posts = get_posts('numberposts=1&author='.$ID);
                $post_dates[$ID] = '';
                if ($posts) $post_dates[$ID] = $posts[0]->post_date;
            }
        }

        if (!$asc) {
            arsort($post_dates);
        }else{
            asort($post_dates);
        }

        $users = array();
        foreach ($post_dates as $key => $value) {
            $users[] = get_userdata($key);
        }
        return $users;
    }

    function orderby_last_comment($users, $args){
        $asc = (isset($args['order']) && 'ASC' === strtoupper($args['order']));
        $comment_dates = array();
        if ($users) {
            foreach ($users as $user) {
                $ID = $user->ID;
                $comments = get_comments('number=1&user_id='.$ID);
                $comment_dates[$ID] = '';
                if ($comments) $comment_dates[$ID] = $comments[0]->comment_date;
            }
        }

        if (!$asc) {
            arsort($comment_dates);
        }else{
            asort($comment_dates);
        }

        $users = array();
        foreach ($comment_dates as $key => $value) {
            $users[] = get_userdata($key);
        }
        return $users;
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( User_List::class );
} );