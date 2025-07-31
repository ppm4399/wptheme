<?php
namespace WPCOM\Modules;

class Feature_Posts extends Module {
    function __construct() {
        $options = [
            [
                'tab-name' => '常规设置',
                'from' => [
                    'name' => '文章来源',
                    'type' => 'r',
                    'ux' => 1,
                    'value'  => '0',
                    'options' => [
                        '0' => '使用文章推送',
                        '1' => '按文章分类'
                    ]
                ],
                'w' => [
                    't' => 'w',
                    'filter' => 'from:1',
                    'o' => [
                        'cat' => [
                            'name' => '文章分类',
                            'type' => 'cat-single',
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
                    ]
                ],
                'posts_num' => [
                    "name" => '文章数量',
                    "desc" => '调用的文章数量',
                    "value" => '5'
                ],
                'style' => [
                    'name' => '显示风格',
                    'type' => 'r',
                    'ux' => 2,
                    'o' => [
                        '0' => '默认风格||/justnews/feature-style-0.png',
                        '1' => '单篇+虚化背景||/justnews/feature-style-1.png',
                        '2' => '3篇一组轮播||/justnews/feature-style-2.png',
                        '3' => '4篇一组轮播||/justnews/feature-style-3.png',
                        '4' => '5篇一组轮播||/justnews/feature-style-4.png',
                    ]
                ],
                'padding' => [
                    'name' => '虚化背景上下内边距',
                    'f' => 'style:1',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '模块内容区域与边界的距离',
                    'units' => 'px, %',
                    'value'  => '30px'
                ],
                'ratio' => [
                    'f' => 'style:0,style:1',
                    'name' => '显示宽高比',
                    'mobile' => 1,
                    'desc' => '固定格式：<b>宽度:高度</b>，例如<b>10:3</b>',
                    'value' => '10:3',
                ],
                'hide-date' => [
                    'name' => '隐藏时间',
                    'type'  => 't'
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
        add_filter('wpcom_module_feature-posts_default_style', [$this, 'default_style']);
        parent::__construct('feature-posts', '推荐文章', $options, 'view_module', '/justnews/mod-feature-posts.png');
    }

    function default_style($style){
        if($style && isset($style['padding'])) {
            unset($style['padding']);
            unset($style['padding_mobile']);
        }
        return $style;
    }

    function classes( $atts, $depth = 0 ){
        $style = $this->value('style', 0);
        $classes = $depth==0 ? 'container' : '';
        $classes .= ' feature-posts-style-' . $style;
        return $classes;
    }

    function style( $atts ){
        $style = $this->value('style', 0);
        $ratio = '';
        if($style == 0 || $style == 1){
            $ratio = $this->value('ratio');
            $ratio = trim(str_replace('：', ':', $ratio));
            $ratio = str_replace(':', ' / ', $ratio);

            $ratio_m = $this->value('ratio_mobile');
            $ratio_m = trim(str_replace('：', ':', $ratio_m));
            $ratio_m = str_replace(':', ' / ', $ratio_m);
        }
        return [
            'ratio' => [
                '.post-loop-card' => $ratio ? '--thumb-ratio-post: ' . $ratio . ';' : ''
            ],
            'ratio_mobile' => [
                '@[(max-width: 767px)] .post-loop-card' => $ratio_m ? '--thumb-ratio-post: ' . $ratio_m . ';' : ''
            ],
            'padding' => [
                '.item-inner' => $style == 1 ? \WPCOM::trbl($this->value('padding'), 'padding', 'tb') : ''
            ],
            'padding_mobile' => [
                '@[(max-width: 767px)] .item-inner' => $style == 1 ? \WPCOM::trbl($this->value('padding_mobile'), 'padding', 'tb') : ''
            ]
        ];
    }

    function template( $atts, $depth ){
        global $feature_post, $feature_style;
        $feature_post= 1;
        $style = $this->value('style', 0);
        $feature_style = $style;
        $posts_num = $this->value('posts_num');
        $args = [
            'showposts' => $posts_num
        ];
        if($this->value('from')=='1'){
            $args['cat'] = $this->value('cat', 0);
            $_orderby = $this->value('orderby', 0);
            $orderby = 'date';
            if($_orderby==1){
                $orderby = 'comment_count';
            }else if($_orderby==2){
                $orderby = 'meta_value_num';
            }else if($_orderby==3){
                $orderby = 'rand';
            }else if($_orderby == 4){
                $orderby = 'modified';
            }
            $args['orderby'] = $orderby;
            if($orderby=='meta_value_num') {
                $args['meta_key'] = 'views';
                $days = isset($atts['days']) && $atts['days'] ? intval($atts['days']) : 0;
                if($days){
                    $args['date_query'] = [
                        [
                            'column' => 'post_date',
                            'after' => date('Y-m-d H:i:s',current_time('timestamp')-3600*24*$days)
                        ]
                    ];
                }
            }
        }else{
            $args['meta_key'] = '_show_as_slide';
            $args['meta_value'] = '1';
        }
        $posts = \WPCOM::get_posts( $args );?>
        <div class="feature-posts-wrap wpcom-slider">
            <ul class="post-loop post-loop-card cols-3 swiper-wrapper">
                <?php if($posts->have_posts()){
                    global $post;
                    if($style==3||$style==4){
                        $post_array = [];
                        $per = $style==3 ? 4 : 5;
                        $i = 0;
                        while ( $posts->have_posts() ) : $posts->the_post();
                            $key = intval($i/$per);
                            if(!isset($post_array[$key])) $post_array[$key] = [];
                            $post_array[$key][] = $post;
                            $i++;
                        endwhile;
                        if($post_array){
                            foreach ($post_array as $array){
                                echo '<li class="swiper-slide">';
                                foreach ($array as $post){ setup_postdata($post);
                                    get_template_part('templates/loop', 'card', ['hide_date' => $this->value('hide-date')]);
                                }
                                echo  '</li>';
                            }
                        }
                    }else {
                        while ( $posts->have_posts() ) : $posts->the_post();
                            get_template_part('templates/loop', 'card', ['hide_date' => $this->value('hide-date')]);
                        endwhile;
                    }
                } wp_reset_postdata(); ?>
            </ul>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
        <script>
            jQuery(function($) {
                var _swiper_<?php echo $atts['modules-id'];?> = {
                    pagination: {
                        el: '#modules-<?php echo $atts['modules-id'];?> .swiper-pagination',
                        clickable: true
                    },
                    slideClass: 'item'
                };
                <?php if($style==2){ ?>
                _swiper_<?php echo $atts['modules-id'];?>.slidesPerView = 1;
                _swiper_<?php echo $atts['modules-id'];?>.spaceBetween = 1;
                _swiper_<?php echo $atts['modules-id'];?>.slidesPerGroup = 1;
                _swiper_<?php echo $atts['modules-id'];?>.breakpoints = {
                    768: {
                        slidesPerView: 3,
                        slidesPerGroup: 3,
                        spaceBetween: 0
                    }
                };
                <?php }else if($style==3||$style==4){ ?>
                _swiper_<?php echo $atts['modules-id'];?>.slideClass = 'swiper-slide';
                <?php } ?>
                $(document.body).trigger('swiper', {
                    el: '#modules-<?php echo $atts['modules-id'];?> .feature-posts-wrap',
                    args: _swiper_<?php echo $atts['modules-id'];?>
                });
            });
        </script>
    <?php $feature_style = '';}
}

register_module( Feature_Posts::class );