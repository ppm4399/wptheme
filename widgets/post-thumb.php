<?php
namespace WPCOM\Widgets;

class Post_Thumb extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_post_thumb';
        $this->widget_description = '带缩略图的文章列表';
        $this->widget_id = 'post-thumb';
        $this->widget_name = '#图文列表';
        $this->settings = [
            'title'       => [
                'name' => '标题',
            ],
            'number'      => [
                'name' => '显示数量',
                'value'   => 10
            ],
            'category'    => [
                'type'  => 'cat-single',
                'value'   => '',
                'name' => '分类'
            ],
            'orderby'    => [
                'type'  => 'select',
                'value'   => '0',
                'name' => '排序',
                'd' => '如果网站文章较多（例如10w+）不推荐选择随机排序，会有性能问题',
                'options' => [
                    '0' => '发布时间',
                    '1' => '评论数',
                    '2' => '浏览数(需安装WP-PostViews插件)',
                    '3' => '随机排序',
                    '4' => '更新时间'
                ]
            ],
            'days' => [
                'name' => '时间范围',
                'f' => 'orderby:2',
                'desc' => '限制时间范围，以天为单位，例如填写365，则表示仅获取1年内的文章，可避免获取太久之前的文章，留空或0则不限制'
            ]
        ];
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();

        $category = isset($instance['category']) ? $instance['category'] : '';
        $_orderby = empty( $instance['orderby'] ) ? $this->settings['orderby']['value'] :  $instance['orderby'];
        $number = empty( $instance['number'] ) ? $this->settings['number']['value'] : absint( $instance['number'] );

        $orderby = 'date';
        if($_orderby == 1) {
            $orderby = 'comment_count';
        } else if($_orderby == 2) {
            $orderby = 'meta_value_num';
        } else if($_orderby == 3) {
            $orderby = 'rand';
        } else if($_orderby == 4) {
            $orderby = 'modified';
        }

        $parg = [
            'cat' => $category,
            'showposts' => $number,
            'orderby' => $orderby
        ];
        if($orderby === 'meta_value_num') {
            $parg['meta_key'] = 'views';
            $days = isset($instance['days']) && $instance['days'] ? intval($instance['days']) : 0;
            if($days){
                $parg['date_query'] = [
                    [
                        'column' => 'post_date',
                        'after' => date('Y-m-d H:i:s', current_time('timestamp')-3600*24*$days)
                    ]
                ];
            }
        }

        $posts = \WPCOM::get_posts( $parg );

        $this->widget_start( $args, $instance );

        if ( $posts->have_posts() ) : global $post;?>
            <ul>
                <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                    <li class="item">
                        <?php $has_thumb = get_the_post_thumbnail(null, 'default'); if($has_thumb){
                            $video = get_post_meta( $post->ID, 'wpcom_video', true );?>
                            <div class="item-img<?php echo $video?' item-video':'';?>">
                                <a class="item-img-inner" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>">
                                    <?php echo $has_thumb; ?>
                                </a>
                            </div>
                        <?php } ?>
                        <div class="item-content<?php echo ($has_thumb?'':' item-no-thumb');?>">
                            <p class="item-title"><a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>"><?php the_title();?></a></p>
                            <p class="item-date"><?php the_time(get_option('date_format'));?></p>
                        </div>
                    </li>
                <?php endwhile; wp_reset_postdata();?>
            </ul>
        <?php
        else:
            echo '<p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">' . __('No Posts', 'wpcom') . '</p>';
        endif;
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean(), 3600 );
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( Post_Thumb::class );
} );