<?php
defined( 'ABSPATH' ) || exit;

add_action('wp_footer', 'wpcom_footer', 1);
if(!function_exists('wpcom_footer')){
    function wpcom_footer(){
        global $options;
        $show_action = apply_filters('wpcom_show_action_tool', true);
        if($show_action){
            $style = isset($options['action_style']) && $options['action_style'] ? $options['action_style'] : '0';
            $cstyle = isset($options['action_cstyle']) && $options['action_cstyle'] ? $options['action_cstyle'] : '0';
            $pos = isset($options['action_pos']) && $options['action_pos'] ? $options['action_pos'] : '0';
            ?>
            <div class="action action-style-<?php echo $style;?> action-color-<?php echo $cstyle;?> action-pos-<?php echo $pos;?>"<?php echo isset($options['action_bottom'])?' style="bottom:'.$options['action_bottom'].';"':''?>>
                <?php
                if(isset($options['action_icon']) && $options['action_icon']){
                    foreach ($options['action_icon'] as $i => $icon){
                        if($icon){
                            $title = isset($options['action_title']) && isset($options['action_title'][$i]) ? $options['action_title'][$i] : '';
                            $type = isset($options['action_type']) && isset($options['action_type'][$i]) ? $options['action_type'][$i] : '';
                            $target = isset($options['action_target']) && isset($options['action_target'][$i]) ? $options['action_target'][$i] : '';
                            if($type==='0'){ ?>
                                <a class="action-item" <?php echo WPCOM::url($target, false);?>>
                                    <?php WPCOM::icon($icon, true, 'action-item-icon');?>
                                    <?php if($style) echo '<span>'.$title.'</span>';?>
                                </a>
                            <?php }else{ ?>
                                <div class="action-item">
                                    <?php WPCOM::icon($icon, true, 'action-item-icon');?>
                                    <?php if($style) echo '<span>'.$title.'</span>';?>
                                    <div class="action-item-inner action-item-type-<?php echo $type;?>">
                                        <?php if($type==='1') {
                                            echo '<img class="action-item-img" src="'.esc_url($target).'" alt="'.esc_attr($title).'">';
                                        }else{
                                            echo wpautop($target);
                                        }?>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php }
                    }
                } ?>
                <?php if(isset($options['share'])&&$options['share']=='1'){ ?>
                    <div class="action-item j-share">
                        <?php WPCOM::icon('share', true, 'action-item-icon');?>
                        <?php if($style) echo '<span>'.__('SHARE', 'wpcom').'</span>';?>
                    </div>
                <?php }
                if ((isset($options['gotop']) && $options['gotop'] == '1') || !isset($options['gotop'])) { ?>
                    <div class="action-item gotop j-top">
                        <?php WPCOM::icon('arrow-up-2', true, 'action-item-icon');?>
                        <?php if($style) echo '<span>'.__('TOP', 'wpcom').'</span>';?>
                    </div>
                <?php } ?>
            </div>
        <?php }
        if(isset($options['footer_bar_url']) && is_array($options['footer_bar_url']) && !empty($options['footer_bar_url']) && !(count($options['footer_bar_url'])===1 && current($options['footer_bar_url'])=='')){?>
            <div class="footer-bar">
                <?php foreach($options['footer_bar_url'] as $i => $url){
                    $icon = isset($options['footer_bar_icon'][$i]) && $options['footer_bar_icon'][$i] ? $options['footer_bar_icon'][$i] : '';
                    $type = isset($options['footer_bar_type'][$i]) && $options['footer_bar_type'][$i] ? $options['footer_bar_type'][$i] : '0';
                    $bg = isset($options['footer_bar_bg'][$i]) && $options['footer_bar_bg'][$i] ? ' style="background-color: '.WPCOM::color($options['footer_bar_bg'][$i]).';"' : '';
                    $color = isset($options['footer_bar_color'][$i]) && $options['footer_bar_color'][$i] ? ' style="color: '.WPCOM::color($options['footer_bar_color'][$i]).';"' : '';?>
                    <div class="fb-item<?php echo $icon ? '' : ' fb-item-no-icon';?>"<?php echo $bg;?>>
                        <?php if($type=='0' || $type=='1'){ ?>
                            <a <?php echo WPCOM::url($url);?><?php if($type=='1'){ echo ' class="j-footer-bar-qrcode"';} echo $color;?>>
                                <?php if($icon) WPCOM::icon($icon, true, 'fb-item-icon');?>
                                <span><?php echo $options['footer_bar_title'][$i];?></span>
                            </a>
                        <?php }else if($type=='2'){ ?>
                            <a href="javascript:;" class="j-footer-bar-copy" <?php echo $color;?>>
                                <script class="j-copy-text" type="text/tpl"><?php echo sanitize_textarea_field($url);?></script>
                                <?php if(isset($options['footer_bar_cb']) && isset($options['footer_bar_cb'][$i]) && trim($options['footer_bar_cb'][$i]) !== ''){ ?><script class="j-copy-callback" type="text/tpl"><?php echo wp_kses_post(wpautop($options['footer_bar_cb'][$i]));?></script><?php }?>
                                <?php if($icon) WPCOM::icon($icon, true, 'fb-item-icon');?>
                                <span><?php echo $options['footer_bar_title'][$i];?></span>
                            </a>
                        <?php }else if($type=='3'){ ?>
                            <a href="javascript:;" class="j-footer-bar-text" <?php echo $color;?>>
                                <script type="text/tpl"><?php echo wp_kses_post(wpautop($url));?></script>
                                <?php if($icon) WPCOM::icon($icon, true, 'fb-item-icon');?>
                                <span><?php echo $options['footer_bar_title'][$i];?></span>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php }
    }
}

if(!function_exists('wpcom_footer_class')){
    function wpcom_footer_class($class=''){
        global $options;
        $_class = 'footer';
        if(isset($options['footer_bar_icon']) && is_array($options['footer_bar_icon']) && !empty($options['footer_bar_icon']) && !(count($options['footer_bar_icon'])===1 && current($options['footer_bar_icon'])==''))
            $_class .= ' width-footer-bar';
        if($class) $_class .= ' ' . $class;
        return $_class;
    }
}

add_action('wp_footer', 'wpcom_top_news', 20);
if(!function_exists('wpcom_top_news')){
    function wpcom_top_news(){
        global $options;
        $content = isset($options['top_news']) && trim($options['top_news']) !== '' ? wp_kses_post($options['top_news']) : '';
        $classes = apply_filters('wpcom_top_news_classes', 'top-news');
        if($content && trim(strip_tags($content)) !== ''){ ?>
            <div class="<?php echo esc_attr($classes);?>" style="<?php echo WPCOM::gradient_color($options['top_news_bg']);?>">
                <div class="top-news-content container">
                    <div class="content-text"><?php echo $content; ?></div>
                    <?php WPCOM::icon('close', true, 'top-news-close');?>
                </div>
            </div>
        <?php }
    }
}