<?php
namespace WPCOM\Modules;

class Carousel_Posts extends Module {
    function __construct() {
        $options = [
            [
                'tab-name' => '常规设置',
                'title' => [
                    'name' => '模块标题'
                ],
                'sub-title' => [
                    'name' => '模块副标题'
                ],
                'cat' => [
                    'name' => '文章分类',
                    'type' => 'cat-single'
                ],
                'orderby'    => [
                    'type'  => 'select',
                    'value'   => '0',
                    'name' => '排序',
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
                ],
                'per-view' => [
                    'name' => '每栏显示',
                    'type' => 'r',
                    'ux' => 1,
                    'value'  => '4',
                    'o' => [
                        '3' => '3篇',
                        '4' => '4篇',
                        '5' => '5篇'
                    ]
                ],
                'number' => [
                    'name' => '显示数量',
                    'value'  => '12'
                ]
            ],
            [
                'tab-name' => '风格样式',
                'margin' => [
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => '20px'
                ]
            ]
        ];
        parent::__construct('carousel-posts', '轮播文章', $options, 'view_carousel', '/justnews/mod-carousel-posts.png');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 0;?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <div class="sec-panel-more">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                    <h2><span><?php echo $atts['title']; ?></span> <small><?php echo $atts['sub-title']; ?></small></h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body carousel-slider">
                <div class="j-slider-<?php echo $atts['modules-id'];?> cs-inner">
                    <ul class="swiper-wrapper post-loop post-loop-image cols-<?php echo isset($atts['per-view']) && $atts['per-view'] ? $atts['per-view'] : 4;?>">
                        <?php
                        $i = 0;
                        $_orderby = $this->value('orderby', 0);
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
                        $args = [
                            'cat' => $this->value('cat', ''),
                            'showposts' => $this->value('number', 12),
                            'orderby' => $orderby
                        ];
                        if($orderby === 'meta_value_num') {
                            $args['meta_key'] = 'views';
                            $days = isset($atts['days']) && $atts['days'] ? intval($atts['days']) : 0;
                            if($days){
                                $args['date_query'] = array(
                                    array(
                                        'column' => 'post_date',
                                        'after' => date('Y-m-d H:i:s',current_time('timestamp')-3600*24*$days)
                                    )
                                );
                            }
                        }
                        $posts = \WPCOM::get_posts( $args );
                        if($posts->have_posts()){
                            while ( $posts->have_posts() ) : $posts->the_post();
                                get_template_part( 'templates/loop' , 'image' );
                            $i++; endwhile; wp_reset_postdata();
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
        <script>
            <?php $per_view = $this->value('per-view');?>
            jQuery(function($){
                $(document.body).trigger('swiper', {
                    el: '.j-slider-<?php echo $atts['modules-id'];?>',
                    args: {
                        slidesPerView: 2,
                        spaceBetween: 0,
                        slidesPerGroup: 1,
                        slideClass: 'item',
                        navigation:{
                            nextEl: '#modules-<?php echo $atts['modules-id'];?> .swiper-button-next',
                            prevEl: '#modules-<?php echo $atts['modules-id'];?> .swiper-button-prev',
                        },
                        breakpoints: {
                            1025: {
                                slidesPerView: <?php echo $per_view?>,
                                slidesPerGroup: <?php echo $i%2 === 0 ? 2 : 1;?>
                            }
                        },
                        _callback(swiper){
                            $(swiper.slides).addClass('swiper-slide');
                        }
                    }
                });
            })
        </script>
    <?php }
}

register_module( Carousel_Posts::class );