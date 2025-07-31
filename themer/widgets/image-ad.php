<?php
namespace WPCOM\Widgets;

if( !class_exists('\WPCOM_image_ad_widget') && !class_exists( Image_Ad::class ) ) :
    class Image_Ad extends Widget {
        public function __construct() {
            $this->widget_cssclass = 'widget_image_myimg';
            $this->widget_description = '可以添加图片链接广告';
            $this->widget_id = 'image-ad';
            $this->widget_name = '#图片广告';
            $this->settings = array(
                'title'       => array(
                    'name' => '标题',
                    'desc' => '不直接显示，会用作图片的alt属性'
                ),
                'image'      => array(
                    'type'  => 'upload',
                    'name' => '图片',
                ),
                'url'      => array(
                    'type'  => 'url',
                    'name' => '链接',
                )
            );
            parent::__construct();
        }

        public function widget( $args, $instance ) {
            if ( $this->get_cached_widget( $args ) ) return;
            ob_start();
            $title  = empty( $instance['title'] ) ? '' : esc_attr( $instance['title'] );
            $image = empty( $instance['image'] ) ? '' : esc_url( $instance['image'] );
            $url = empty($instance['url']) ? '' : $instance['url'];
            if(!preg_match('/, /i', $url)){
                if(!empty($instance['target'])) $url .= ', _blank';
                if(!empty($instance['nofollow'])) $url .= ', nofollow';
            }
            echo $args['before_widget'];
            if($url){ ?>
                <a <?php echo \WPCOM::url($url);?>>
                    <?php echo wpcom_lazyimg($image, $title);?>
                </a>
            <?php } else { ?>
                <?php echo wpcom_lazyimg($image, $title);?>
            <?php }

            $this->widget_end( $args );
            echo $this->cache_widget( $args, ob_get_clean() );
        }
    }

    // register widget
    add_action( 'widgets_init', function(){
        register_widget( Image_Ad::class );
    } );
endif;