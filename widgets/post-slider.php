<?php
namespace WPCOM\Widgets;

class Post_Slider extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_post_slider';
        $this->widget_description = '文章幻灯片轮播展示';
        $this->widget_id = 'post-slider';
        $this->widget_name = '#文章轮播滑块';
        $this->settings = [
            'from'      => [
                'name' => '文章来源',
                'type'   => 'r',
                'ux' => 1,
                'value'   => '0',
                'o' => [
                    '0' => '自定义文章',
                    '1' => '按分类调用'
                ]
            ],
            'show_title' => [
                'name' => '是否显示标题',
                'value' => '0',
                't' => 't'
            ],
            'post_ids' => [
                'filter' => 'from:0',
                'name' => '文章ID',
                'desc' => '多个文章ID使用英文逗号分隔，例如：1,2,3'
            ],
            'category'    => [
                'filter' => 'from:1',
                'type'  => 'cat-single',
                'value'   => '0',
                'd' => '不选择分类则默认调用所有文章',
                'name' => '分类'
            ],
            'number'      => [
                'filter' => 'from:1',
                'name' => '文章数量',
                'd' => '最多不超过10篇',
                'value'   => 5
            ],
            'orderby'    => [
                'filter' => 'from:1',
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
                'filter' => 'from:1',
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

        $form = empty( $instance['from'] ) ? $this->settings['from']['value'] :  $instance['from'];
        $show_title = empty( $instance['show_title'] ) ? $this->settings['show_title']['value'] :  $instance['show_title'];

        if($form == '0'){
            $post_ids = empty($instance['post_ids']) ? '' : $instance['post_ids'];
            $post_ids = trim(str_replace('，', ',', $post_ids));
            $post_ids = explode(',', $post_ids);
            $parg = [
                'post__in' => $post_ids,
                'posts_per_page' => 10
            ];
        }else{
            $category = isset($instance['category']) && $instance['category'] ? $instance['category'] : '';
            $_orderby = empty( $instance['orderby'] ) ? $this->settings['orderby']['value'] :  $instance['orderby'];
            $number = empty( $instance['number'] ) ? $this->settings['number']['value'] : absint( $instance['number'] );
            $number = $number > 10 ? 10 : $number;

            $orderby = 'date';
            if($_orderby == 1){
                $orderby = 'comment_count';
            }else if($_orderby == 2){
                $orderby = 'meta_value_num';
            }else if($_orderby == 3){
                $orderby = 'rand';
            }else if($_orderby == 4){
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
                            'after' => date('Y-m-d H:i:s',current_time('timestamp')-3600*24*$days)
                        ]
                    ];
                }
            }
        }

        $posts = \WPCOM::get_posts( $parg );

        $this->widget_start( $args, $instance );

        if ( $posts->have_posts() ) :?>
            <div class="wpcom-slider swiper-container<?php echo $show_title ? ' show-title' : '';?>">
                <ul class="swiper-wrapper">
                    <?php while ( $posts->have_posts() ) : $posts->the_post();
                        $thumb = get_the_post_thumbnail();
                        if($thumb) { ?>
                        <li class="swiper-slide">
                            <a class="slide-post-inner" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>">
                                <?php echo $thumb;?>
                                <?php if($show_title) {?><span class="slide-post-title"><?php the_title();?></span><?php } ?>
                            </a>
                        </li>
                    <?php } endwhile; wp_reset_postdata();?>
                </ul>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        <?php
        endif;
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean(), 3600 );
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( Post_Slider::class );
} );