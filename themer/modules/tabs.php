<?php
namespace WPCOM\Modules;

class Tabs extends Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'items' => array(
                    't' => 'rp',
                    'name' => '选项卡',
                    'options' => array(
                        'tab-title' => array(
                            'name' => '选项卡标题'
                        ),
                        'tab-icon' => array(
                            'name' => '选项卡图标',
                            't' => 'ic',
                            'img' => 1,
                            'desc' => '可选项，不设置请留空'
                        )
                    )
                ),
                'tab-switch' => array(
                    'name' => '切换方式',
                    'type' => 'r',
                    'ux' => 1,
                    's' => '0',
                    'o' => [
                        '0' => '点击切换',
                        '1' => '鼠标移入'
                    ]
                )
            ),
            array(
                'tab-name' => '风格样式',
                'tab-style' => [
                    'name' => '标题栏风格',
                    't' => 'r',
                    'ux' => 2,
                    'value' => '0',
                    'o' => [
                        '0' => '/themer/tabs-style-0.png',
                        '1' => '/themer/tabs-style-1.png',
                        '2' => '/themer/tabs-style-2.png',
                        '3' => '/themer/tabs-style-3.png',
                    ]
                ],
                'tab-align' => [
                    'name' => '标题栏对齐',
                    't' => 'r',
                    'ux' => 1,
                    'value' => 'center',
                    'd' => '选项卡标题对齐方式',
                    'mobile' => 1,
                    'o' => [
                        'left' => '<i class="material-icons">format_align_left</i>',
                        'center' => '<i class="material-icons">format_align_center</i>',
                        'right' => '<i class="material-icons">format_align_right</i>'
                    ]
                ],
                'tab-size' => [
                    'name' => '标题栏字体大小',
                    't' => 'l',
                    'ux' => 1,
                    'value' => '18px',
                    'mobile' => 1,
                    'units' => 'px'
                ],
                'tab-gap' => [
                    'name' => '标题栏间距',
                    'd' => '选项卡标题之间的间距',
                    't' => 'l',
                    'ux' => 1,
                    'value' => '30px',
                    'mobile' => 1,
                    'units' => 'px'
                ],
                'color' => array(
                    'name' => '标题栏文字颜色',
                    'type' => 'c',
                ),
                'active-color' => array(
                    'name' => '标题栏选中文字颜色',
                    'type' => 'c',
                ),
                'active-btn-color' => array(
                    'name' => '按钮选中背景颜色',
                    'type' => 'c',
                    'd' => '可选，当标题栏风格为按钮时可单独设置按钮的背景颜色'
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
        parent::__construct('tabs', '选项卡', $options, 'tab', '/themer/mod-tabs.png');
    }
    function style($atts) {
        return array(
            'tab-gap' => [
                ' > .tabs-switch' => '--module-tab-gap: {{value}};'
            ],
            'tab-size' => [
                ' > .tabs-switch' => '--module-font-size: {{value}};'
            ],
            'color' => [
                ' > .tabs-switch' => '--module-color: {{value}};'
            ],
            'active-color' => [
                ' > .tabs-switch' => '--module-active-color: {{value}};'
            ],
            'active-btn-color' => [
                ' > .tabs-switch' => '--module-active-btn-color: {{value}};'
            ]
        );
    }
    function template($atts, $depth) {
        global $is_visual_page;
        $items = $this->value('items');
        $tabs = $this->value('tabs');
        if (is_array($items) && !empty($items)) { ?>
            <ul class="tabs-switch tabs-align-<?php echo $this->value('tab-align'); ?> tabs-switch-<?php echo $this->value('tab-switch') == 1 ? 'hover' : 'click'; ?> tabs-style-<?php echo $this->value('tab-style');?>">
                <?php foreach ($items as $i => $item) {
                    echo '<li class="tabs-switch-item' . ($i === 0 ? ' active' : '') . '">' . ($item['tab-icon'] ? \WPCOM::icon($item['tab-icon'], 0) : '') . $item['tab-title'] . '</li>';
                } ?>
            </ul>
            <div class="tabs-content">
                <?php foreach ($items as $i => $item) { ?>
                    <div class="tabs-content-item<?php echo $is_visual_page ? ' j-modules-inner' : ''; echo $i === 0 ? ' active' : ''; ?>">
                        <?php if ($tabs && isset($tabs[$i])) {
                            foreach ($tabs[$i] as $mod) {
                                $mod['settings']['modules-id'] = $mod['id'];
                                $mod['settings']['parent-id'] = $this->value('modules-id');
                                do_action('wpcom_modules_' . $mod['type'], $mod['settings'], $depth + 1);
                            }
                        } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
<?php }
}

register_module( Tabs::class );