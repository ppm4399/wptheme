<?php
namespace WPCOM\Modules;

class Grid extends Module {
    function __construct() {
        $options = [
            [
                'tab-name' => '常规设置',
                'columns' => [
                    'name' => '栅格列数',
                    'mobile' => 1,
                    'type' => 'columns',
                    'desc' => '设置栅格的列数，然后在下面设置每列对应的宽度，页面采用24列计算，下面所有栅格相加等于24即可，超过24将会换行，小于24页面无法填满',
                    'value'  => ['12', '12']
                ],
                'offset' => [
                    'name' => '栅格偏移',
                    'mobile' => 1,
                    'desc' => '栅格向右边偏移的格数，例如需要添加一个居中的20格宽度栅格，则此处可以偏移2格',
                    'value'  => '0'
                ]
            ],
            [
                'tab-name' => '风格样式',
                'padding' => [
                    'name' => '左右内间距',
                    'type' => 'length',
                    'mobile' => 1,
                    'value' => '15px',
                    'desc' => '通过修改左右内间距可以改变栅格左右之间的距离，设置为0则无间距'
                ],
                'margin' => [
                    'name' => '外边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'use' => 'tb',
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => apply_filters('module_default_margin_value', '20px')
                ]
            ]
        ];
        add_filter('wpcom_module_grid_default_style', array($this, 'default_style'));
        parent::__construct( 'grid', '栅格布局', $options, 'view_column', '/themer/mod-grid.png' );
    }

    function default_style($style){
        if($style && isset($style['padding'])) {
            unset($style['padding']);
            unset($style['padding_mobile']);
        }
        return $style;
    }

    function style($atts){
        return array(
            'padding' => array(
                '.row > [class*=col-]' => 'padding: 0 {{value}};',
                '.row' => 'margin-left: -{{value}};margin-right: -{{value}};'
            )
        );
    }

    function template($atts, $depth){
        global $is_visual_page;
        $columns = $this->value('columns');
        $columns_mobile = $this->value('columns_mobile');
        $grids = $this->value('grids', []);
        ?>
        <div class="row">
        <?php for($i=0;$i<count($columns);$i++){
            $class = $is_visual_page ? 'j-modules-inner ' : '';
            if( $columns[$i] == '0'){
                $class .= 'hidden-md hidden-lg';
            }else{
                $class .= 'col-md-'.$columns[$i];
            }
            if($i==0 && $this->value('offset')) $class .= ' col-md-offset-'.$this->value('offset');
            if( $columns_mobile && isset($columns_mobile[$i]) ){
                if( $columns_mobile[$i] == '0'){
                    $class .= ' hidden-sm hidden-xs';
                }else{
                    $class .= ' col-sm-' . $columns_mobile[$i] . ' col-xs-' . $columns_mobile[$i];
                }
                if($i==0 && $this->value('offset_mobile'))
                    $class .= ' col-sm-offset-'.$this->value('offset_mobile').' col-xs-offset-'.$this->value('offset_mobile');
            } ?>
            <div class="<?php echo $class;?>">
                <?php if($grids && isset($grids[$i])){ foreach ($grids[$i] as $v) {
                    $v['settings']['modules-id'] = $v['id'];
                    $v['settings']['parent-id'] = $this->value('modules-id');
                    do_action('wpcom_modules_' . $v['type'], $v['settings'], $depth+1);
                } } ?>
            </div>
        <?php } ?>
        </div>
    <?php }
}

register_module( Grid::class );