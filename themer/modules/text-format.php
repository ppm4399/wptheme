<?php
namespace WPCOM\Modules;

class Text_Format extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'format' => array(
                    'name' => '格式',
                    'type' => 's',
                    'value' => 'p',
                    'options' => array(
                        'p' => '段落',
                        'h1' => '一级标题',
                        'h2' => '二级标题',
                        'h3' => '三级标题',
                        'h4' => '四级标题',
                        'h5' => '五级标题',
                        'h6' => '六级标题'
                    )
                ),
                'content' => array(
                    'name' => '内容',
                    'type' => 'editor',
                    'mini' => 1
                ),
                'align' => array(
                    'name' => '对齐',
                    'type' => 'r',
                    'ux' => 1,
                    'value' => 'left',
                    'mobile' => 1,
                    'o' => array(
                        'left' => '<i class="material-icons">format_align_left</i>',
                        'center' => '<i class="material-icons">format_align_center</i>',
                        'right' => '<i class="material-icons">format_align_right</i>'
                    )
                ),
                'size' => array(
                    'name' => '文字尺寸',
                    'type' => 'l',
                    'mobile' => 1,
                    'min' => 6
                ),
                'line-height' => array(
                    'name' => '文字行高',
                    'type' => 'l',
                    'mobile' => 1,
                    'units' => 'px, em'
                ),
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
        parent::__construct( 'text-format', '文本格式', $options, 'title', '/themer/mod-text-format.png' );
    }

    function style($atts){
        return array(
            'align' => array(
                '.text-format-el' => 'text-align: {{value}};'
            ),
            'size' => array(
                '.text-format-el' => 'font-size: {{value}};'
            ),
            'line-height' => array(
                '.text-format-el' => 'line-height: {{value}};'
            ),
            'title-font' => array(
                '.text-format-el' => $this->value('title-font') == 1 ? 'font-family: var(--theme-title-font);' : ''
            )
        );
    }

    function template($atts, $depth){
        $tag = $this->value('format', 'p');
        $content = wp_kses_post($this->value('content')); ?>
        <<?php echo $tag;?> class="text-format-el"><?php echo do_shortcode($content);?></<?php echo $tag;?>>
    <?php }
}

register_module( Text_Format::class );