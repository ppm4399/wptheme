<?php
namespace WPCOM\Widgets;

class Html_Ad extends Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_html_myimg';
        $this->widget_description = '适合添加html广告代码，无边框';
        $this->widget_id = 'html-ad';
        $this->widget_name = '#广告代码';
        $this->settings = array(
            'html'       => array(
                'type'  => 'textarea',
                'code' => 1,
                'name' => '代码',
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $html  = empty( $instance['html'] ) ? '' : $instance['html'];
        $this->widget_start( $args, $instance );
        echo do_shortcode($html);
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
add_action( 'widgets_init', function(){
    register_widget( Html_Ad::class );
} );