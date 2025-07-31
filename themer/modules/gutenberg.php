<?php
namespace WPCOM\Modules;

class Gutenberg extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'content' => array(
                    'name' => '内容',
                    'type' => 'gutenberg',
                    'desc' => '点击打开 Gutenberg 区块编辑器来编辑内容<br><b>温馨提示：</b>如安装了<b>经典编辑器插件</b>的话，请开启<b>设置>撰写>允许用户切换编辑器</b>选项，否则区块编辑器无法正常调起'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'text-indent' => array(
                    'name' => '段落缩进',
                    'type' => 't'
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
        parent::__construct( 'gutenberg', '区块编辑器', $options, 'draw' );
    }
    function style($atts){
        $style = array(
            'text-indent' => array(
                ' > p' => $this->value('text-indent') ? 'text-indent: 2em;' : ''
            )
        );
        return $style;
    }
    function template($atts, $depth){
        echo apply_filters( 'the_content', $this->value('content') );
    }
}

register_module( Gutenberg::class );