<?php
namespace WPCOM\Widgets;

class Tags extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_tags';
        $this->widget_description = '主题自带标签云小工具，解决系统标签云不支持排序、数量设置等问题';
        $this->widget_id = 'tags';
        $this->widget_name = '#文章标签';
        $this->settings = array(
            'title' => array(
                'name' => '标题'
            ),
            'orderby' => array(
                'name' => '排序依据',
                't' => 'r',
                'ux' => 1,
                'value' => 'count',
                'o' => array(
                    'count' => '文章数量',
                    'term_id' => '标签ID',
                    'name' => '标签名'
                )
            ),
            'order' => array(
                'name' => '排序方式',
                't' => 'r',
                'ux' => 1,
                'value' => 'RAND',
                'o' => array(
                    'RAND' => '随机',
                    'ASC' => '升序',
                    'DESC' => '降序'
                )
            ),
            'include' => array(
                'name' => '指定标签',
                'd' => '填写后将仅展示指定的标签，填写标签ID，多个标签请用逗号隔开'
            ),
            'exclude' => array(
                'name' => '排除的标签',
                'd' => '填写标签ID，多个标签请用逗号隔开'
            ),
            'hide_empty' => array(
                'name' => '隐藏空标签',
                'd' => '隐藏没有文章的标签',
                't' => 't',
                'value' => 1
            ),
            'number' => array(
                'name' => '显示数量',
                'value' => 30
            ),
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $orderby = empty( $instance['orderby'] ) ? $this->settings['orderby']['value'] :  $instance['orderby'];
        $order = empty( $instance['order'] ) ? $this->settings['order']['value'] :  $instance['order'];
        $include = empty( $instance['include'] ) ? '' :  $instance['include'];
        $exclude = empty( $instance['exclude'] ) ? '' :  $instance['exclude'];
        $hide_empty = empty( $instance['hide_empty'] ) ? $this->settings['hide_empty']['value'] :  $instance['hide_empty'];
        $number = empty( $instance['number'] ) ? $this->settings['number']['value'] :  $instance['number'];
        $this->widget_start( $args, $instance );

        $_args = array(
            'taxonomy' => 'post_tag',
            'orderby' => $orderby,
            'order' => $order,
            'number' => $number,
            'hide_empty' => (int)$hide_empty,
            'include' => str_replace('，', ',', $include),
            'exclude' => str_replace('，', ',', $exclude)
        );

        $tags = get_terms('post_tag', $_args);
        if(!is_wp_error($tags) && !empty($tags)){ ?>
        <div class="tagcloud">
            <?php foreach($tags as $tag){ ?>
                <a href="<?php echo esc_url(get_term_link( intval( $tag->term_id ), $tag->taxonomy ))?>" title="<?php echo esc_attr($tag->name);?>"><?php echo $tag->name;?></a>
            <?php } ?>
        </div>
        <?php }

        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean(), 3600 );
    }
}

// register widget
add_action( 'widgets_init', function() {
    register_widget( Tags::class );
});