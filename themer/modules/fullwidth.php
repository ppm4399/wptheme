<?php
namespace WPCOM\Modules;

class Fullwidth extends Module {
    function __construct() {
        $options = [
            [
                'tab-name' => '常规设置',
                'fluid' => [
                    'name' => '固定宽度',
                    'type' => 't',
                    'desc' => '模块内容宽度固定，居中显示；否则内容宽度不固定，为100%',
                    'value'  => '1'
                ],
                'mobile-hide' => [
                    'name' => '设备可见性',
                    'type' => 'r',
                    'ux' => 1,
                    's' => 0,
                    'desc' => '可设置在电脑或者手机端展示',
                    'o' => [
                        '0' => '全部可见',
                        '1' => '电脑端可见',
                        '2' => '移动端可见'
                    ]
                ]
            ],
            [
                'tab-name' => '风格样式',
                'bg-color' => [
                    'name' => '背景颜色',
                    'type' => 'c',
                    'gradient' => 1
                ],
                'bg-image' => [
                    'name' => '背景图片',
                    'type' => 'u',
                    'desc' => '温馨提示：如果设置了背景图片，则背景颜色不支持设置渐变色'
                ],
                'bg-video' => [
                    'name' => '背景视频',
                    'type' => 'u',
                    'desc' => '可选，MP4格式视频；<b>另外由于手机端部分浏览器存在兼容问题，所以手机端不展示视频，建议再设置上面的背景图片选项</b>'
                ],
                'wrap' => [
                    'filter' => 'bg-image:!!!',
                    'type' => 'wrapper',
                    'o' => [
                        'bg-image-repeat' => [
                            'name' => '背景平铺',
                            'type' => 'r',
                            'ux' => 1,
                            'value'  => 'no-repeat',
                            'o' => [
                                'no-repeat' => '不平铺',
                                'repeat' => '平铺',
                                'repeat-x' => '水平平铺',
                                'repeat-y' => '垂直平铺'
                            ]
                        ],
                        'bg-image-size' => [
                            'name' => '背景尺寸',
                            'type' => 'r',
                            'ux' => 1,
                            'f' => 'bg-image-repeat:no-repeat',
                            'desc' => '自动调整背景图片显示尺寸',
                            'value'  => '1',
                            'mobile' => 1,
                            'o' => [
                                '0' => '默认',
                                '1' => '铺满模块',
                                '2' => '按宽度铺满',
                                '9' => '自定义'
                            ]
                        ],
                        'bg-image-size2' => [
                            'name' => '自定义尺寸',
                            'f' => 'bg-image-size:9',
                            'mobile' => 1,
                            'v-show' => 1,
                            'desc' => '即 background-size 值，非技术人员不推荐此选项'
                        ],
                        'bg-image-position' => [
                            'name' => '背景位置',
                            'type' => 's',
                            'desc' => '分别为左右对齐方式和上下对齐方式',
                            'value'  => 'center center',
                            'o' => [
                                'left top' => '左 上<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#111" d="M.5.5h20v20H.5z"/><path fill="#CCC" d="M26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'center top' => '中 上<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5z"/><path fill="#222" d="M26 .5h20v20H26z"/><path fill="#CCC" d="M51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'right top' => '右 上<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26z"/><path fill="#222" d="M51.5.5h20v20h-20z"/><path fill="#CCC" d="M.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'left center' => '左 中<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20z"/><path fill="#222" d="M.5 26h20v20H.5z"/><path fill="#CCC" d="M26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'center center' => '中 中<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5z"/><path fill="#222" d="M26 26h20v20H26z"/><path fill="#CCC" d="M51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'right center' => '右 中<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26z"/><path fill="#222" d="M51.5 26h20v20h-20z"/><path fill="#CCC" d="M.5 51.5h20v20H.5zM26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'left bottom' => '左 下<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20z"/><path fill="#222" d="M.5 51.5h20v20H.5z"/><path fill="#CCC" d="M26 51.5h20v20H26zM51.5 51.5h20v20h-20z"/></g></svg>',
                                'center bottom' => '中 下<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5z"/><path fill="#222" d="M26 51.5h20v20H26z"/><path fill="#CCC" d="M51.5 51.5h20v20h-20z"/></g></svg>',
                                'right bottom' => '右 下<svg width="72" height="72" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#CCC" d="M.5.5h20v20H.5zM26 .5h20v20H26zM51.5.5h20v20h-20zM.5 26h20v20H.5zM26 26h20v20H26zM51.5 26h20v20h-20zM.5 51.5h20v20H.5zM26 51.5h20v20H26z"/><path fill="#222" d="M51.5 51.5h20v20h-20z"/></g></svg>'
                            ]
                        ],
                        'bg-image-attachment' => [
                            'name' => '背景固定',
                            'type' => 't',
                            'desc' => '背景图片固定，不跟随滚动，若开启则需要确保图片高度足够'
                        ],
                        'bg-image-shadow' => [
                            'name' => '背景处理',
                            'type' => 'r',
                            'ux' => 1,
                            'desc' => '优化处理背景图片',
                            'value'  => '0',
                            'o' => [
                                '0' => '不处理',
                                '1' => '暗化处理',
                                '2' => '亮化处理'
                            ]
                        ]
                    ]
                ],
                'margin' => [
                    'name' => '外边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'use' => 'tb',
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %, vw, vh',
                    'value'  => apply_filters('module_default_margin_value', '20px')
                ],
                'padding' => [
                    'name' => '内边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'desc' => '模块内容区域与边界的距离',
                    'units' => 'px, %, vw, vh',
                    'value'  => '20px 0'
                ]
            ]
        ];
        parent::__construct( 'fullwidth', '全宽模块', $options, 'width_full', '/themer/mod-fullwidth.png' );
    }

    function classes($atts, $depth){
        $classes = '';
        return $classes;
    }

    function style($atts){
        $bg_img = $this->value('bg-image');
        $bg_image = '';
        $bg_color = \WPCOM::gradient_color($this->value('bg-color'));
        if($bg_img && preg_match('/background-image:/i', $bg_color)){
            // 处理渐变色和背景图片问题
            $bg_image = preg_replace('/background-image:/i', 'background-image: url(\''.$bg_img.'\'), ', $bg_color);
            $bg_color = '';
        }else if($bg_img){
            $bg_image = 'background-image: url(\''.$bg_img.'\');';
        }
        $bg_size = $this->value('bg-image-size');
        if($bg_size=='9'){
            $bg_size = $this->value('bg-image-size2');
        }else if($bg_size){
            $bg_size = $bg_size == '1' ? 'cover' : '100% auto';
        }else if($bg_size==='0'){
            $bg_size = 'auto';
        }

        $bg_size_m = $this->value('bg-image-size_mobile');
        if($bg_size_m=='9'){
            $bg_size_m = $this->value('bg-image-size2_mobile')!=='' ? $this->value('bg-image-size2_mobile') : $this->value('bg-image-size2');
        }else if($bg_size_m){
            $bg_size_m = $bg_size_m == '1' ? 'cover' : '100% auto';
        }else if($bg_size_m==='0'){
            $bg_size_m = 'auto';
        }

        return array(
            'bg-color' => array(
                '' => $bg_color
            ),
            'bg-image' => array(
                '' => $bg_image
            ),
            'bg-image-shadow' => array(
                '' => $this->value('bg-image-shadow') ? 'position: relative;' : ''
            ),
            'bg-video' => array(
                '' => $this->value('bg-video') ? 'position: relative;' : ''
            ),
            'bg-image-repeat' => array(
                '' => 'background-repeat: {{value}};'
            ),
            'bg-image-size' => array(
                '' => $this->value('bg-image-repeat')==='no-repeat' && $bg_size!=='' ? ('background-size: ' . $bg_size . ';') : '',
            ),
            'bg-image-size_mobile' => array(
                '@[(max-width: 767px)]' => $this->value('bg-image-repeat')==='no-repeat' && $bg_size_m!=='' ? ('background-size: ' . $bg_size_m . ';') : '',
            ),
            'bg-image-position' => array(
                '' => 'background-position: {{value}};'
            ),
            'bg-image-attachment' => array(
                '' => $this->value('bg-image-attachment') ? 'background-attachment: fixed;-webkit-backface-visibility: hidden;' : ''
            ),
            'mobile-hide' => array(
                '@[(max-width: 767px)]' =>  $this->value('mobile-hide')==1 ? 'display: none;' : '',
                '@[(min-width: 768px)]' =>  $this->value('mobile-hide')==2 ? 'display: none;' : ''
            )
        );
    }

    function template($atts, $depth) {
        global $is_visual_page;
        if($this->value('bg-video')) {
            $video_class = 'module-bg-video';
            if($this->value('bg-image-attachment')=='1') $video_class .= ' module-bg-fixed';
            ?>
        <div class="<?php echo esc_attr($video_class);?>">
            <video muted autoplay loop playsinline preload="auto" src="<?php echo esc_url($this->value('bg-video'));?>"></video>
        </div>
        <?php } if($this->value('bg-image-shadow')=='1'){?><div class="module-shadow"></div><?php } ?>
        <?php if($this->value('bg-image-shadow')=='2'){?><div class="module-shadow module-shadow-white"></div><?php } ?>
        <div class="container<?php echo $this->value('fluid')?'':'-fluid'; echo $is_visual_page ? ' j-modules-inner' : '';?>"<?php echo $this->value('bg-image-shadow')||$this->value('bg-video') ? ' style="position: relative;"':''; ?>>
            <?php if($this->value('modules')){ foreach ($this->value('modules') as $module) {
                $module['settings']['modules-id'] = $module['id'];
                $module['settings']['parent-id'] = $this->value('modules-id');
                $module['settings']['fullwidth'] = $this->value('fluid') ? 0 : 1;
                do_action('wpcom_modules_' . $module['type'], $module['settings'], $depth+1);
            } } ?>
        </div>
    <?php }
}

register_module( Fullwidth::class );