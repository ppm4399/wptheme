<?php
namespace WPCOM\Widgets;

if( !class_exists('\WPCOM_products_widget') && !class_exists( Products::class ) ) :
    class Products extends Widget {
        public function __construct() {
            $this->widget_cssclass = 'widget_lastest_products';
            $this->widget_description = '选择指定产品分类，适合用于显示产品列表/图文列表信息';
            $this->widget_id = 'lastest-products';
            $this->widget_name = '#产品列表';
            $this->settings = array(
                'title'       => array(
                    'name' => '标题',
                ),
                'number'      => array(
                    'value'   => 10,
                    'name' => '显示数量',
                ),
                'category'    => array(
                    'type'  => 'cat-single',
                    'std'   => '0',
                    'name' => '分类'
                ),
                'orderby'    => array(
                    'type'  => 'select',
                    'value'   => '0',
                    'name' => '排序',
                    'd' => '如果网站文章较多（例如10w+）不推荐选择随机排序，会有性能问题',
                    'options' => array(
                        '0' => '发布时间',
                        '1' => '评论数',
                        '2' => '浏览数(需安装WP-PostViews插件)',
                        '3' => '随机排序'
                    )
                ),
                'days' => array(
                    'name' => '时间范围',
                    'f' => 'orderby:2',
                    'desc' => '限制时间范围，以天为单位，例如填写365，则表示仅获取1年内的文章，可避免获取太久之前的文章，留空或0则不限制'
                )
            );
            parent::__construct();
        }

        public function widget( $args, $instance ) {
            if ( $this->get_cached_widget( $args ) ) return;
            ob_start();

            $category = $instance['category'];
            $orderby_id = empty( $instance['orderby'] ) ? $this->settings['orderby']['value'] :  $instance['orderby'];
            $number = empty( $instance['number'] ) ? $this->settings['number']['value'] : absint( $instance['number'] );

            $orderby = 'date';
            if($orderby_id==1){
                $orderby = 'comment_count';
            }else if($orderby_id==2){
                $orderby = 'meta_value_num';
            }else if($orderby_id==3){
                $orderby = 'rand';
            }

            $parg = array(
                'cat' => $category,
                'showposts' => $number,
                'orderby' => $orderby
            );
            if($orderby=='meta_value_num') {
                $parg['meta_key'] = 'views';
                $days = isset($instance['days']) && $instance['days'] ? intval($instance['days']) : 0;
                if($days){
                    $parg['date_query'] = array(
                        array(
                            'column' => 'post_date',
                            'after' => date('Y-m-d H:i:s',current_time('timestamp')-3600*24*$days)
                        )
                    );
                }
            }

            $posts = \WPCOM::get_posts( $parg );

            $this->widget_start( $args, $instance );

            if ( $posts->have_posts() ) : ?>
                <ul class="p-list row">
                    <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                        <li class="col-xs-12 col-md-12 p-item">
                            <div class="p-item-wrap">
                                <a class="thumb" href="<?php echo esc_url( get_permalink() )?>">
                                    <?php the_post_thumbnail();?>
                                </a>
                                <h4 class="title">
                                    <a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>">
                                        <?php the_title();?>
                                    </a>
                                </h4>
                            </div>
                        </li>
                    <?php endwhile; wp_reset_postdata();?>
                </ul>
            <?php
            endif;

            $this->widget_end( $args );
            echo $this->cache_widget( $args, ob_get_clean(), 3600 );
        }
    }

    // register widget
    add_action( 'widgets_init', function() {
        register_widget( Products::class );
    });
endif;