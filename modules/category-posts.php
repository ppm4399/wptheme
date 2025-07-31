<?php
namespace WPCOM\Modules;

class Category_Posts extends Module {
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
                'style' => [
                    'name' => '显示风格',
                    'type' => 'r',
                    'ux' => 2,
                    'o' => [
                        '' => '默认列表||/justnews/list-tpl-default.png',
                        'image' => '图文列表||/justnews/list-tpl-image.png',
                        'card' => '卡片列表||/justnews/list-tpl-card.png',
                        'list' => '文章列表||/justnews/list-tpl-list.png',
                    ]
                ],
                'cols' => [
                    'name' => '每行显示',
                    'type' => 's',
                    'filter' => 'style:image,style:card',
                    'value'  => '4',
                    'o' => [
                        '2' => '2篇',
                        '3' => '3篇',
                        '4' => '4篇',
                        '5' => '5篇'
                    ]
                ],
                'hide-excerpt' => [
                    'filter' => 'style:',
                    'name' => '隐藏摘要',
                    'd' => '如果使用栅格分栏显示，则可能过于拥挤显示不下太多内容，此时建议开启此选项',
                    'type'  => 't'
                ],
                'hide-date' => [
                    'filter' => 'style:list,style:card',
                    'name' => '隐藏时间',
                    'type'  => 't'
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
        parent::__construct('category-posts', '分类文章', $options, 'folder', '/justnews/mod-category-posts.png');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 0;
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : 4;
        $style = isset($atts['style']) && $atts['style'] ? $atts['style'] : 'default';
        $cat = $this->value('cat', '');
        $cat_link = $cat ? get_category_link($cat) : '';
        $title = isset($atts['title']) ? $atts['title'] : '';
        if($title && $cat_link) $title = '<a href="'.$cat_link.'" target="_blank">'.$title.'</a>';
        $child_cats = $cat ? get_terms(array(
            'taxonomy' => 'category',
            'parent' => $cat
        )) : '';
        $hide_date = ($style === 'list' || $style === 'card') && $this->value('hide-date') ? ' hide-date' : '';
        $hide_excerpt = $style === 'default' && $this->value('hide-excerpt') ? ' hide-excerpt' : '';
        ?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <h2>
                        <span><?php echo $title; ?></span>
                        <small><?php echo $atts['sub-title']; ?></small>
                        <?php if($child_cats) {
                            echo '<div class="sec-panel-more">';
                            $i = 0;
                            foreach ($child_cats as $c){ if($i < 3){ if($i>0) echo '<span class="split">/</span>'; ?>
                                <a href="<?php echo get_category_link($c->term_id);?>" target="_blank"><?php echo $c->name;?></a>
                            <?php $i++;}}
                            echo '</div>';
                        }else if($cat_link){ ?><a class="more" href="<?php echo $cat_link;?>" target="_blank"><?php _e('More', 'wpcom');?> <?php \WPCOM::icon('arrow-right');?></a><?php } ?>
                    </h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <ul class="post-loop post-loop-<?php echo $style;?> cols-<?php echo $cols; echo $hide_date; echo $hide_excerpt;?>">
                    <?php
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
                    $args = [
                        'cat' => $cat,
                        'showposts' => $this->value('number', 12),
                        'orderby' => $orderby
                    ];
                    if($orderby === 'meta_value_num') {
                        $args['meta_key'] = 'views';
                        $days = isset($atts['days']) && $atts['days'] ? intval($atts['days']) : 0;
                        if($days){
                            $args['date_query'] = [
                                [
                                    'column' => 'post_date',
                                    'after' => date('Y-m-d H:i:s', current_time('timestamp')-3600*24*$days)
                                ]
                            ];
                        }
                    }
                    $posts = \WPCOM::get_posts( $args );
                    if($posts->have_posts()){
                        while ( $posts->have_posts() ) : $posts->the_post();
                            get_template_part( 'templates/loop' , $style, ['hide_date' => $hide_date] );
                        endwhile; wp_reset_postdata();
                    } ?>
                </ul>
            </div>
        </div>
    <?php }
}

register_module( Category_Posts::class );