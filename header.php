<?php global $options, $is_submit_page; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
    <title><?php wp_title( isset($options['title_sep']) && $options['title_sep'] ? $options['title_sep'] : ' | ', true, 'right' ); ?></title>
    <?php wp_head();?>
    <!--[if lte IE 11]><script src="<?php echo get_template_directory_uri()?>/js/update.js"></script><![endif]-->
</head>
<body <?php body_class()?>>
<header class="header<?php echo wpcom_header_class();?>">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse" aria-label="menu">
                <span class="icon-bar icon-bar-1"></span>
                <span class="icon-bar icon-bar-2"></span>
                <span class="icon-bar icon-bar-3"></span>
            </button>
            <?php $h1_tag = 'div'; if(is_home()||is_front_page()) $h1_tag = 'h1'; ?>
            <<?php echo $h1_tag;?> class="logo">
                <a href="<?php echo home_url('/');?>" rel="home">
                    <img src="<?php echo wpcom_logo()?>" alt="<?php echo esc_attr(get_bloginfo( 'name' ));?>">
                </a>
            </<?php echo $h1_tag;?>>
        </div>
        <div class="<?php echo wpcom_nav_menu_classes();?>">
            <?php
            wp_nav_menu( array(
                    'theme_location'    => 'primary',
                    'depth'             => 3,
                    'container'         => 'nav',
                    'container_class'   => 'primary-menu',
                    'menu_class'        => 'nav navbar-nav',
                    'advanced_menu'     => true,
                    'fallback_cb'       => 'WPCOM_Nav_Walker::fallback',
                    'walker'            => new WPCOM_Nav_Walker())
            ); ?>
            <div class="navbar-action">
                <?php if(isset($options['dark_style_toggle']) && $options['dark_style_toggle'] == '1' && $options['dark_style'] != '2'){ ?>
                    <div class="dark-style-toggle<?php echo $options['dark_style'] == '1' ? ' active' : '';?>">
                        <?php WPCOM::icon($options['dark_style']=='1' ? 'moon-fill' : 'sun-fill');?>
                    </div>
                    <script> if (window.localStorage) { var dark = localStorage.getItem('darkStyle'); var toggle = document.querySelector('.dark-style-toggle');if(dark == 1 && !toggle.classList.contains('active')){ document.body.classList.add('style-for-dark');toggle.classList.add('active'); toggle.querySelector('use').setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '#wi-moon-fill'); }else if(dark == 0 && toggle.classList.contains('active')){ document.body.classList.remove('style-for-dark'); toggle.classList.remove('active'); toggle.querySelector('use').setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '#wi-sun-fill'); } } </script>
                <?php }

                if( !isset($options['search']) || (isset($options['search']) && $options['search'] == '1') ) {
                    wpcom_header_search_form();
                }

                do_action('wpcom_woo_cart_icon');

                if( defined('WPMX_VERSION') ) { ?>
                    <div id="j-user-wrap">
                        <a class="login" href="<?php echo wp_login_url(); ?>"><?php _e('Sign in', 'wpcom');?></a>
                        <a class="login register" href="<?php echo wp_registration_url(); ?>"><?php _e('Sign up', 'wpcom');?></a>
                    </div>
                    <?php if( !isset($is_submit_page) && isset($options['tougao_on']) && $options['tougao_on'] == '1' ){ ?>
                        <a class="wpcom-btn btn-primary btn-xs publish" href="<?php echo esc_url(wpcom_addpost_url());?>">
                            <?php echo (isset($options['tougao_btn']) && $options['tougao_btn'] ? $options['tougao_btn'] : __('Submit Post', 'wpcom'));?>
                        </a>
                    <?php }
                } ?>
            </div>
        </div>
    </div><!-- /.container -->
</header>

<div id="wrap">