<?php
namespace WPCOM\Modules;

class Text extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'content' => array(
                    'name' => '内容',
                    'type' => 'editor'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'margin' => array(
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => apply_filters('module_default_margin_value', '20px')
                ),
                'padding' => array(
                    'name' => '内边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'desc' => '模块内容区域与边界的距离',
                    'units' => 'px, %',
                    'value'  => '10px'
                )
            )
        );
        parent::__construct( 'text', '自定义内容', $options, 'text_fields', '/themer/mod-text.png' );
    }

    function template($atts, $depth){
        $content = wp_kses_post($this->value('content'));
        echo do_shortcode( shortcode_unautop(wpautop($content)) );
    }
}

register_module( Text::class );