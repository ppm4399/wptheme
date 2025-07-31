<?php
namespace WPCOM\Modules;

class Video extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'video' => array(
                    'name' => '视频代码',
                    'type' => 'textarea',
                    'desc' => '可填写第三方视频分享代码（推荐通用代码）、mp4视频地址、视频短代码/shortcode'
                ),
                'ratio' => array(
                    'name' => '模块显示比例',
                    'mobile' => 1,
                    'value'  => '16:9',
                    'desc' => '填写格式：<b>16:9</b>，根据当前模块实际显示宽度以及此处设置的比例自动等比例适配'
                ),
                'cover' => array(
                    'name' => '背景图',
                    'desc' => '注意：如果播放方式选择直接播放，则仅对本地mp4视频生效',
                    'type' => 'u'
                ),
                'type' => array(
                    'name' => '播放方式',
                    'value'  => '0',
                    'type' => 'r',
                    'options' => array(
                        '0' => '弹框播放',
                        '1' => '直接播放'
                    )
                ),
                'width' => array(
                    'name' => '弹窗宽度',
                    'type' => 'length',
                    'f' => 'type:0',
                    'desc' => '视频弹窗宽度，可根据视频尺寸调整',
                    'value'  => '900px'
                ),
                'height' => array(
                    'name' => '弹窗高度',
                    'type' => 'length',
                    'f' => 'type:0',
                    'desc' => '视频弹窗高度，可根据视频尺寸调整',
                    'value'  => '550px'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'radius' => array(
                    'name' => '圆角',
                    'type' => 'length',
                    'mobile' => 1,
                    'value'  => '5px'
                ),
                'play-btn-size' => array(
                    'name' => '播放按钮尺寸',
                    'type' => 'length',
                    'mobile' => 1,
                    'desc' => '播放按钮的直径长度',
                    'value'  => '72px'
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
        parent::__construct( 'video', '视频', $options, 'play_circle_outline', '/themer/mod-video.png' );
    }

    function style( $atts ){
        $width = intval($this->value('width'));
        $height = intval($this->value('height'));
        $bili = $width && $height ? $width . ' / ' . $height : '16 / 9';
        $res = array(
            'radius' => array(
                '.video-wrap,.video-inline-player' => 'border-radius: {{value}};'
            ),
            'width' => array(
                '.modal-dialog' => 'width: {{value}};',
                '@[(max-width: 991px)] .modal-dialog' => 'width: 92%;'
            ),
            'height' => array(
                '.modal-body' => 'aspect-ratio: '. $bili.';',
            ),
            'play-btn-size' => array(
                '.video-wrap' => '--module-play-btn-size: {{value}};',
            )
        );
        if (isset($atts['ratio']) && $atts['ratio']) {
            $ratio = $this->value('ratio', '16:9');
            $ratio_m = $this->value('ratio_mobile', '16:9');
            $ratio = preg_replace('/[:：]/', ' / ', trim($ratio));
            $ratio_m = preg_replace('/[:：]/', ' / ', trim($ratio_m));
            $res['ratio'] = array(
                '.video-wrap' => 'height:auto;aspect-ratio: 16 / 9;aspect-ratio: '. $ratio.';'
            );
            $res['ratio_mobile'] = array(
                '@[(max-width: 767px)] .video-wrap' => 'aspect-ratio: '. $ratio_m.';'
            );
        }else if(isset($atts['mod-height']) && $atts['mod-height']){
            $res['mod-height'] = array(
                '.video-wrap' => 'height: {{value}};',
                '@[(max-width: 1199px)] .video-wrap.video-wrap-vw-0' => 'height: calc({{value}} * 0.83);',
                '@[(max-width: 991px)] .video-wrap.video-wrap-vw-0' => 'height: calc({{value}} * 0.63);'
            );
        }
        return $res;
    }
    function animate($atts){}
    function template($atts, $depth) {
        $type = isset($atts['type']) && $atts['type'] ? $atts['type'] : 0;
        $video = isset($atts['video']) && $atts['video'] ? $atts['video'] : '';

        if (!(isset($atts['ratio']) && $atts['ratio']) && (isset($atts['mod-height']) && $atts['mod-height'])) {
            // 检查模块高度，如果单位是vw可忽略
            $mod_height = $this->value('mod-height');
            $is_vw = preg_match('/([\d]+)vw$/i', $mod_height) ? 1 : 0;
        }

        if($video && preg_match('/^(http:\/\/|https:\/\/|\/\/).*/i', $video) ){
            if($type){
                $poster = isset($atts['cover']) && $atts['cover'] ? $atts['cover'] : '';
                $video = '<video class="modules-video-player" preload="none" src="'.$video.'" poster="'.$poster.'" playsinline></video>';
            }else{
                $width = intval(isset($atts['width'])&&$atts['width']?$atts['width']:'900');
                $height = intval(isset($atts['height'])&&$atts['height']?$atts['height']:'550');
                $video = '[video width="'.$width.'" height="'.$height.'" autoplay="true" src="'.$video.'"][/video]';
            }
        } ?>
        <div <?php echo (!$type && isset($atts['cover']) && $atts['cover'] ? wpcom_lazybg($atts['cover'], 'video-wrap'.(isset($is_vw) ? ' video-wrap-vw-'.$is_vw : '')) : 'class="video-wrap'.(isset($is_vw) ? ' video-wrap-vw-'.$is_vw : '') . '"');?><?php echo $this->animate_item(0);?>>
            <?php if($type){ ?>
                <div class="video-inline-player"><?php echo do_shortcode($video);?></div>
            <?php } else { ?>
                <div class="modal-player" data-toggle="modal" data-target="#vModal-<?php echo $atts['modules-id'];?>"><?php \WPCOM::icon('play');?></div>
                <script class="video-code" type="text/html">
                    <?php echo do_shortcode($video);?>
                </script>
            <?php } ?>
        </div>
        <?php if($type!='1'){ ?>
        <!-- Modal -->
        <div class="wpcom-modal fade modal-video" id="vModal-<?php echo $atts['modules-id'];?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="wpcom-close" data-wpcom-dismiss="modal"><?php \WPCOM::icon('close');?></div>
                    </div>
                    <div class="modal-body"></div>
                </div>
            </div>
        </div>
        <?php }
    }
}

register_module( Video::class );