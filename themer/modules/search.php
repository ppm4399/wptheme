<?php
namespace WPCOM\Modules;

class Search extends Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'types' => array(
                    'name' => '搜索方式',
                    't' => 'cb',
                    'o' => [
                        '0' => '内置搜索',
                        '1' => '自定义搜索'
                    ]
                ),
                'type-list' => array(
                    'type' => 'repeat',
                    'f' => 'types:0',
                    'name' => '内置搜索方式',
                    'd' => '可选，可设置搜索内容类型，不设置则为默认搜索',
                    'items' => array(
                        'post_type' => array(
                            'name' => '内容类型',
                            'tax' => 'search_type',
                            'd' => '如需展示全部选项（即默认搜索全部类型文章），可以将此选项留空',
                            't' => 's'
                        ),
                        'category' => array(
                            'name' => '文章分类',
                            'f' => 'post_type:post',
                            'type' => 's',
                            'tax' => 'category',
                            'd' => '可选，不设置则默认搜索全部文章'
                        ),
                        'label' => array(
                            'name' => '自定义名称',
                            'd' => '可选，默认为文章类型或者文类名称，用于多个内容搜索方式时下拉选项展示'
                        )
                    )
                ),
                'search-type' => array(
                    'type' => 'repeat',
                    'f' => 'types:1',
                    'name' => '自定义搜索方式',
                    'items' => array(
                        'search-name' => array(
                            'name' => '搜索名称',
                        ),
                        'search-url' => array(
                            'name' => '搜索地址',
                            'd' => '可根据搜索链接规则使用<b>%KEYWORDS%</b>替换搜索关键词，比如以“测试”作为关键词百度搜索链接是<b>https://www.baidu.com/s?wd=测试</b>，那么选项可以填写<b>https://www.baidu.com/s?wd=%KEYWORDS%</b>'
                        )
                    )
                ),
                'placeholder' => array(
                    'name' => '提示文字',
                    'value' => '输入关键词搜索...'
                ),
                'blank' => array(
                    'name' => '新窗口打开结果页',
                    'value' => '0',
                    't' => 't'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'width' => array(
                    'name' => '宽度',
                    'type' => 'l',
                    'mobile' => 1,
                    'value' => '100%',
                    'units' => 'px, %'
                ),
                'height' => array(
                    'name' => '高度',
                    'type' => 'l',
                    'mobile' => 1,
                    'value' => '42px',
                    'units' => 'px'
                ),
                'btn-style' => array(
                    'name' => '搜索按钮',
                    'type' => 'r',
                    'ux' => 1,
                    'value' => '0',
                    'o' => [
                        '0' => '图标按钮',
                        '1' => '文字按钮'
                    ]
                ),
                'btn-text' => array(
                    'f' => 'btn-style:1',
                    'name' => '搜索按钮文案',
                    'value' => '搜索'
                ),
                'radius' => array(
                    'name' => '圆角半径',
                    'type' => 'l',
                    'value'  => '8px',
                    'mobile' => 1,
                    'desc' => '搜索框的圆角半径，如不需要圆角可设置为0'
                ),
                'border' => array(
                    'name' => '边框',
                    'type' => 'b',
                    'value'  => '',
                    'mobile' => 1,
                    'desc' => '可选，设置搜索框边框'
                ),
                'color' => array(
                    'name' => '文本颜色',
                    'type' => 'c',
                ),
                'bg-color' => array(
                    'name' => '背景颜色',
                    'type' => 'c',
                ),
                'btn-color' => array(
                    'name' => '搜索按钮颜色',
                    'type' => 'c',
                    'd' => '搜索按钮、搜索边框焦点颜色'
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

        add_filter('wpcom_themer_get_taxs', function($res, $taxs){
            if($taxs && in_array('search_type', $taxs)){
                $res['search_type'] = $this->get_search_types();
            }
            return $res;
        }, 10, 2);

        add_action( 'pre_get_posts', function($query){
            if($query->is_main_query() && $query->is_search()){
                if(isset($_GET['post_type']) && trim($_GET['post_type']) !== ''){
                    $query->set('post_type', $_GET['post_type']);
                }
            }
        } );

        parent::__construct( 'search', '搜索框', $options, 'pageview', '/themer/mod-search.png' );
    }

    function get_search_types(){
        $search_type = array(
            'post' => _x('Post', 'search_type', 'wpcom'),
            'page' => _x('Page', 'search_type', 'wpcom')
        );
        if(defined('QAPress_VERSION')){
            $search_type['qa_post'] = _x('Question', 'search_type', 'wpcom');
        }
        if(function_exists('WC')){
            $search_type['product'] = _x('Product', 'search_type', 'wpcom');
        }
        return $search_type;
    }

    function style($atts){
        $res = array(
            'width' => array(
                '' => 'width: {{value}};'
            ),
            'height' => array(
                '' => '--module-height: {{value}};',
            ),
            'border' => array(
                '' =>  '--module-border: {{value}};'
            ),
            'radius' => array(
                '' =>  '--module-radius: {{value}};'
            ),
            'color' => array(
                '' =>  '--module-color: {{value}};'
            ),
            'bg-color' => array(
                '' =>  '--module-bg-color: {{value}};'
            ),
            'btn-color' => array(
                '' =>  '--module-btn-color: {{value}};'
            )
        );
        return $res;
    }

    function template($atts, $depth){
        $url = get_bloginfo('url');
        $searches = $this->value('search-type');
        $types = $this->value('types');
        if(is_array($types) && in_array('1', $types) && is_array($searches) && !empty($searches) && (count($searches) > 1 || in_array('0', $types))){ ?>
        <div class="modules-search-type">
            <?php if(in_array('0', $types)){ ?><div class="modules-search-type-item"><?php _e('Site Search', 'wpcom');?></div><?php } ?>
            <?php foreach ($searches as $search) { if(isset($search['search-url']) && $search['search-url'] && $search['search-name']){ ?>
                <div class="modules-search-type-item" data-search-url="<?php echo esc_attr($search['search-url']);?>"><?php echo $search['search-name']?></div>
            <?php }} ?>
        </div>
        <?php } ?>
        <form class="modules-search-from" action="<?php echo esc_url($url);?>" method="get"<?php if($this->value('blank')) echo ' target="_blank"';?> role="search"<?php echo is_array($types) && !in_array('0', $types) && in_array('1', $types) && is_array($searches) && count($searches) == 1 ? ' data-search-url="'.esc_attr($searches[0]['search-url']).'"' : ''; ?>>
            <?php
            $type_list = $this->value('type-list');
            if($type_list && !empty($type_list)){
                if(count($type_list) > 1){ ?>
                <div class="modules-search-post">
                    <select name="post_type">
                    <?php foreach($type_list as $type){ if(isset($type['post_type'])){ ?>
                        <option value="<?php echo $type['post_type'] ? esc_attr($type['post_type']) : '';?>"<?php echo $type['post_type'] === 'post' ? ' data-cat="'.$type['category'].'"' : '';?>><?php echo $this->get_search_type_label($type);?></option>
                    <?php }} ?>
                    </select>
                </div>
                <?php } else { ?>
                    <input type="hidden" name="post_type" value="<?php echo $type_list[0]['post_type'] ? esc_attr($type_list[0]['post_type']) : '';?>">
                    <?php if($type_list[0]['post_type'] === 'post' && $type_list[0]['category']){?><input type="hidden" name="cat" value="<?php echo $type_list[0]['category'];?>"><?php } ?>
                <?php }
            } ?>
            <input class="modules-search-input" type="search" name="s" placeholder="<?php echo esc_attr( $this->value('placeholder') );?>" autocomplete="off">
            <?php
            if($this->value('btn-style') == 1){ ?>
            <button class="modules-search-button" type="submit"><?php echo $this->value('btn-text');?></button>
            <?php } else { ?>
            <button class="modules-search-button modules-search-icon-button" type="submit" aria-label="<?php echo esc_attr(__('Search', 'wpcom'));?>">
                <?php \WPCOM::icon('search');?>
            </button>
            <?php } ?>
        </form>
    <?php }

    function get_search_type_label($type){
        if($type && isset($type['post_type'])){
            if(isset($type['label']) && trim($type['label']) !== '') return $type['label'];
            $search_types = $this->get_search_types();
            if($type['post_type'] === 'post' && $type['category']){
                $category = get_category($type['category']);
                if($category && !is_wp_error($category)) return $category->name;
            }else{
                return isset($search_types[$type['post_type']]) ? $search_types[$type['post_type']] : $type['post_type'];
            }
        }
    }
}

register_module( Search::class );