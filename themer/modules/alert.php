<?php
namespace WPCOM\Modules;

class Alert extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'icon' => array(
                    'name' => '图标',
                    'desc' => '可选',
                    'img' => 1,
                    'type' => 'ic'
                ),
                'content' => array(
                    'name' => '提示内容',
                    'type' => 'e',
                    'mini' => 1
                ),
                'close' => array(
                    'name' => '允许关闭',
                    'type' => 't',
                    'value' => 1,
                    'desc' => '是否允许关闭此提示信息'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'font-size' => array(
                    'name' => '字体大小',
                    'type' => 'length',
                    'mobile' => 1,
                    'desc' => '文案字体大小',
                    'units' => 'px',
                    'value'  => '16px'
                ),
                'color' => array(
                    'name' => '字体颜色',
                    'type' => 'c',
                    'desc' => '文案字字体颜色',
                ),
                'icon-color' => array(
                    'name' => '图标颜色',
                    'type' => 'c'
                ),
                'bg-color' => array(
                    'name' => '背景颜色',
                    'type' => 'c'
                ),
                'radius' => array(
                    'name' => '圆角',
                    'type' => 'length',
                    'mobile' => 1,
                    'units' => 'px, %'
                ),
                'padding' => array(
                    'name' => '内边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'desc' => '模块内容区域与边界的距离',
                    'units' => 'px, em, vw, vh',
                    'value'  => '12px 16px'
                ),
                'margin' => array(
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => apply_filters('module_default_margin_value', '20px')
                )
            )
        );
        add_filter('wpcom_module_alert_default_style', function($style){
            if($style && isset($style['padding'])) {
                unset($style['padding']);
                unset($style['padding_mobile']);
            }
            return $style;
        });
        parent::__construct( 'alert', '提示信息', $options, 'info', '/themer/mod-alert.png' );
    }

    function style($atts){
        return array(
            'font-size' => array(
                '' => '--module-font-size:{{value}};'
            ),
            'color' => array(
                '' => '--module-color:{{value}};'
            ),
            'icon-color' => array(
                '' => '--module-icon-color:{{value}};'
            ),
            'bg-color' => array(
                '' => '--module-bg-color:{{value}};'
            ),
            'radius' => array(
                '' => '--module-border-radius: {{value}};'
            ),
            'padding' => array(
                '' => '--module-padding:{{value}};'
            )
        );
    }

    function template($atts, $depth){
        $icon = $this->value('icon');
        $content = wp_kses_post($this->value('content'));
        $content = wpautop($content);
        $content = do_shortcode( shortcode_unautop($content)); ?>
        <div class="wpcom-alert fade in">
            <?php if($icon){ ?><div class="alert-icon">
                <?php \WPCOM::icon($icon);?>
            </div><?php } ?>
            <div class="alert-content">
                <?php echo $content;?>
            </div>
            <?php if($this->value('close') == 1){ ?><div class="alert-close" data-wpcom-dismiss="alert"></div><?php } ?>
        </div>
    <?php }
}

register_module( Alert::class );