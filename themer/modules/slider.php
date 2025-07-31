<?php
namespace WPCOM\Modules;

class Default_Slider extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'slides' => array(
                    'name' => '滑块',
                    'type' => 'rp',
                    'o' => array(
                        'image' => array(
                            'name' => '图片',
                            't' => 'at',
                            'd' => '图片是必填的，否则会跳过不展示当前滑块'
                        ),
                        'title' => array(
                            'name' => '标题',
                            'd' => '可选，也会作为图片的alt属性'
                        ),
                        'url' => array(
                            'name' => '链接',
                            't' => 'url',
                            'd' => '可选，点击后跳转页面地址'
                        ),
                    )
                )
            ),
            array(
                'tab-name' => '风格样式',
                'show-title' => array(
                    'name' => '显示标题',
                    't' => 't',
                    's' => '0',
                    'd' => '默认展示标题'
                ),
                'effect' => array(
                    'name' => '轮播效果',
                    't' => 's',
                    's' => 'slide',
                    'o' => array(
                        'slide' => '左右滑动',
                        'fade' => '淡入淡出',
                        'cube' => '立体旋转',
                        'coverflow' => 'CoverFlow',
                        'cards' => '卡片堆叠'
                    )
                ),
                'loop' => array(
                    'name' => '循环轮播',
                    't' => 't',
                    's' => '1'
                ),
                'autoplay' => array(
                    'name' => '轮播间隔',
                    's' => '5000',
                    'd' => '单位：毫秒，默认为5秒，即5000'
                ),
                'auto-height' => array(
                    'name' => '高度自适应',
                    't' => 't',
                    's' => '1',
                    'd' => '根据当前滑块图片尺寸自定义高度；温馨提示：轮播效果为立体旋转时开启自适应高度阴影显示会有异常'
                ),
                'navigation' => array(
                    'name' => '上下切换图标',
                    't' => 't',
                    's' => '1',
                    'd' => '是否展示上下切换的图标'
                ),
                'pagination' => array(
                    'name' => '分页小圆点',
                    't' => 't',
                    's' => '1',
                    'd' => '是否展示图片分页小圆点'
                ),
                'radius' => array(
                    'name' => '圆角',
                    'type' => 'length',
                    'mobile' => 1,
                    'units' => 'px, %'
                ),
                'thumbs' => array(
                    'name' => '显示缩略图',
                    't' => 't',
                    's' => 0,
                    'd' => '开启后可展示缩略图'
                ),
                'thumbs-perview' => array(
                    'f' => 'thumbs:1',
                    'name' => '缩略图每行展示',
                    't' => 'range',
                    'max' => 15,
                    'min' => 2,
                    'step' => 1,
                    'mobile' => 1,
                    's' => 6
                ),
                'thumbs-space' => array(
                    'f' => 'thumbs:1',
                    'name' => '缩略图间距',
                    'type' => 'length',
                    'mobile' => 1,
                    's' => '10px',
                    'units' => 'px'
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
        parent::__construct( 'default-slider', '图片滑块', $options, 'slideshow', '/themer/mod-slider.png');
    }

    function style($atts){
        $res = array(
            'radius' => array(
                '.swiper-el, .swiper-slide' => 'border-radius: {{value}};',
                '.swiper-thumbs .swiper-slide' => 'border-radius: calc({{value}} * 0.6);'
            )
        );
        if($this->value('thumbs', 0)){
            $res['thumbs-perview'] = array(
                '.swiper-thumbs .swiper-slide' => 'width: calc(100% / {{value}});'
            );
            $res['thumbs-space'] = array(
                '' => '--module-thumbs-margin-top: {{value}}',
                '.swiper-thumbs .swiper-slide' => 'margin-right: {{value}};'
            );
            if($this->value('effect') === 'cube'){
                $res['thumbs-space'][''] = '--module-thumbs-margin-top: calc({{value}} + 15px);';
            }
            if($this->value('effect') === 'cards'){
                $res['thumbs-space'][''] = '--module-thumbs-margin-top: calc({{value}} + 10px);';
            }
        }
        if($this->value('show-title', 0)){
            $res['show-title'] = array(
                '.swiper-pagination' => 'bottom: 1em; right: 2em; left: auto; transform: translateZ(0);width:auto;',
                '@[(max-width: 767px)] .swiper-pagination' => 'bottom: 0.8em; right: 1.2em;'
            );
        }
        return $res;
    }

    function template($atts, $depth){
        $slides = $this->value('slides');
        if(is_array($slides) && !empty($slides)){
            $_ids = [];
            foreach($slides as $slide){
                if($slide['image']) $_ids[] = $slide['image'];
            }
            $_ids = array_unique(array_filter($_ids));
            _prime_post_caches($_ids, 0, 1);
        }
        ?>
        <div class="swiper-container swiper-el">
            <div class="swiper-wrapper">
                <?php if(is_array($slides) && !empty($slides)){
                    foreach($slides as $slide){ if($slide['image']){ ?>
                    <div class="swiper-slide">
                        <?php if(isset($slide['url']) && $slide['url']){ ?>
                            <a <?php echo \WPCOM::url($slide['url']);?>><img src="<?php echo \WPCOM::get_image_url($slide['image']);?>" alt="<?php echo esc_attr($slide['title']);?>"></a>
                        <?php }else{ ?>
                            <img src="<?php echo \WPCOM::get_image_url($slide['image']);?>" alt="<?php echo esc_attr($slide['title']);?>">
                        <?php }
                        if($this->value('show-title', 0) && $slide['title']){ ?>
                        <div class="swiper-slide-title"><?php echo wp_kses_post($slide['title']);?></div>
                        <?php } ?>
                    </div>
                    <?php } }
                } ?>
            </div>
            <?php if($this->value('pagination', 1)){ ?><div class="swiper-pagination"></div><?php } ?>
            <?php if($this->value('navigation', 1)){ ?><div class="swiper-button-prev"></div><div class="swiper-button-next"></div><?php } ?>
        </div>
        <?php if($this->value('thumbs', 0)){ ?>
            <div class="swiper-container swiper-thumbs">
                <div class="swiper-wrapper">
                    <?php if(is_array($slides) && !empty($slides)){
                        foreach($slides as $slide){ if($slide['image']){ ?>
                        <div class="swiper-slide">
                            <img src="<?php echo \WPCOM::get_image_url($slide['image']);?>" alt="<?php echo esc_attr($slide['title']);?>">
                        </div>
                        <?php } }
                    } ?>
                </div>
            </div>
        <?php } ?>
        <script>
            jQuery(function($){
                var args = {
                    autoHeight: <?php echo $this->value('auto-height', 1) ? 'true' : 'false';?>,
                    loop: <?php echo $this->value('loop', 1) ? 'true' : 'false';?>,
                    effect: '<?php echo $this->value('effect', 'slide');?>',
                    autoplay: {
                        delay: <?php echo intval($this->value('autoplay', 5000));?>
                    },
                    <?php if($this->value('navigation', 1)){ ?>
                    navigation:{
                        nextEl: '#modules-<?php echo $atts['modules-id'];?> .swiper-button-next',
                        prevEl: '#modules-<?php echo $atts['modules-id'];?> .swiper-button-prev',
                    },
                    <?php }
                    if($this->value('pagination', 1)){ ?>
                    pagination:{
                        el: '#modules-<?php echo $atts['modules-id'];?> .swiper-pagination',
                        dynamicBullets: <?php echo is_array($slides) && !empty($slides) && count($slides) > 6 ? 'true' : 'false';?>,
                        clickable: true
                    }
                    <?php } ?>
                };
                if(args.effect === 'fade'){
                    args.fadeEffect = {
                        crossFade: true
                    };
                }else if(args.effect === 'cube'){
                    args.cubeEffect = {};
                }else if(args.effect === 'coverflow'){
                    args.coverflowEffect = {};
                }else if(args.effect === 'cards'){
                    args.cardsEffect = {};
                }
                <?php if($this->value('thumbs', 0)){ ?>
                    var _swiper_thumbs_<?php echo $atts['modules-id'];?> = {
                        spaceBetween: <?php echo intval($this->value('thumbs-space_mobile', $this->value('thumbs-space')));?>,
                        slidesPerView: <?php echo $this->value('thumbs-perview_mobile', $this->value('thumbs-perview'));?>,
                        freeMode: true,
                        watchSlidesProgress: true,
                        loop: <?php echo $this->value('loop', 1) ? 'true' : 'false';?>,
                        breakpoints: {
                            768: {
                                spaceBetween: <?php echo intval($this->value('thumbs-space'));?>,
                                slidesPerView: <?php echo $this->value('thumbs-perview');?>
                            }
                        },
                        _callback: function(swiper){
                            args.thumbs = {
                                swiper: swiper
                            }
                            $(document.body).trigger('swiper', { el: '#modules-<?php echo $this->value('modules-id');?> .swiper-el', args: args });
                        }
                    };
                    $(document.body).trigger('swiper', { el: '#modules-<?php echo $this->value('modules-id');?> .swiper-thumbs', args: _swiper_thumbs_<?php echo $atts['modules-id'];?> });
                <?php }else{ ?>
                $(document.body).trigger('swiper', { el: '#modules-<?php echo $this->value('modules-id');?> .swiper-el', args: args });
                <?php } ?>
            });
        </script>
    <?php }
}

register_module( Default_Slider::class );