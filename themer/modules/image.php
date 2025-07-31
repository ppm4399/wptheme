<?php
namespace WPCOM\Modules;

class Image extends Module{
    function __construct(){
        $options = [
            [
                'tab-name' => '常规设置',
                'image' => [
                    'name' => '图片',
                    'type' => 'at'
                ],
                'alt' => [
                    'name' => '替代文本',
                    'desc' => '可选，图片alt属性，图片替代文本，利于SEO，留空的话默认获取图片附件的替代文本或者标题'
                ],
                'url' => [
                    'name' => '链接地址',
                    'type' => 'url',
                    'desc' => '可选'
                ]
            ],
            [
                'tab-name' => '风格样式',
                'align' => [
                    'name' => '图片对齐',
                    'type' => 'r',
                    'ux' => 1,
                    'value' => 'center',
                    'o' => [
                        'left' => '<i class="material-icons">format_align_left</i>',
                        'center' => '<i class="material-icons">format_align_center</i>',
                        'right' => '<i class="material-icons">format_align_right</i>',
                        'justify' => '<i class="material-icons">format_align_justify</i>'
                    ]
                ],
                'width' => [
                    'name' => '显示宽度',
                    'type' => 'length',
                    'mobile' => 1,
                    'filter' => 'align:left,align:center,align:right',
                    'units' => 'px, %'
                ],
                'ratio' => [
                    'name' => '显示宽高比',
                    'mobile' => 1,
                    'desc' => '可选，留空默认按图片本身宽高比展示<br>格式：<b>宽度:高度</b>，比如 <b>800:500</b> 或者 <b>8:5</b> 都可以'
                ],
                'object-fit' => [
                    'name' => '缩放适配',
                    't' => 'r',
                    'ux' => 1,
                    'f' => 'ratio:!!!',
                    'value' => 'cover',
                    'desc' => '设置显示宽高比时图片将根据此比例缩放调整<br><b>按比例铺满</b>图片会覆盖对应区域，但是图片比例与设置的宽高比不一样的情况图片可能无法完整展示；<b>完整显示图片</b>会确保图片完整展示，但是可能部分区域会有空白',
                    'o' => [
                        'cover' => '按比例铺满',
                        'contain' => '完整显示图片'
                    ]
                ],
                'radius' => [
                    'name' => '圆角',
                    'type' => 'length',
                    'mobile' => 1,
                    'units' => 'px, %'
                ],
                'margin' => [
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => apply_filters('module_default_margin_value', '20px')
                ]
            ]
        ];
        parent::__construct( 'image', '图片', $options, 'panorama', '/themer/mod-image.png' );
    }

    function style($atts){
        if($ratio = trim($this->value('ratio'))){
            $ratio = str_replace('：', ':', $ratio);
            if (preg_match('/^\d+\s*:\s*\d+$/', $ratio)) {
                $ratio = preg_replace('/\s*:\s*/', '/', $ratio);
            }
        }
        if($ratio_mobile = trim($this->value('ratio_mobile'))){
            $ratio_mobile = str_replace('：', ':', $ratio_mobile);
            if (preg_match('/^\d+\s*:\s*\d+$/', $ratio_mobile)) {
                $ratio_mobile = preg_replace('/\s*:\s*/', '/', $ratio_mobile);
            }
        }
        return [
            'radius' => [
                '.modules-image-inner > img, .modules-image-inner > a' => 'border-radius: {{value}}; overflow: hidden;'
            ],
            'width' => [
                '.modules-image-inner > img, .modules-image-inner > a' => 'width: {{value}};'
            ],
            'ratio' => [
                '.modules-image-inner > img' => 'aspect-ratio: ' . $ratio . ';'
            ],
            'ratio_mobile' => [
                '@[(max-width: 767px)] .modules-image-inner > img' => 'aspect-ratio: ' . $ratio_mobile . ';'
            ],
            'object-fit' => [
                '.modules-image-inner > img' => 'object-fit: {{value}};'
            ]
        ];
    }

    function template($atts, $depth){
        $image = $this->value('image');
        $alt = $this->value('alt');
        $width = '';
        $height = '';
        if($image && is_numeric($image)){
            $src = wp_get_attachment_image_src($image, 'full');
            if($src){
                $image = $src[0];
                $width = $src[1];
                $height = $src[2];
                if (trim($alt) === '') $alt = get_post_meta($this->value('image'), '_wp_attachment_image_alt', true);
                if (trim($alt) === '') $alt = get_the_title($this->value('image'));
            }else{
                $image = '';
            }
        } ?>
        <div class="modules-image-inner image-align-<?php echo $this->value('align');?>">
            <?php if($url = $this->value('url')){  if($this->value('target')=='1' && !preg_match('/, /i', $url)) $url .= ', _blank'; ?>
                <a <?php echo \WPCOM::url($url);?>><?php echo wpcom_lazyimg($image, $alt, $width, $height); ?></a>
            <?php } else { ?>
                <?php echo wpcom_lazyimg($image, $alt, $width, $height); ?>
            <?php } ?>
        </div>
    <?php }
}

register_module( Image::class );