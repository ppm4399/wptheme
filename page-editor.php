<?php
// TEMPLATE NAME: 文章投稿

$is_submit_page = 1;
$current_user = wp_get_current_user();
if(!$current_user->ID){
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

global $options;
$d = apply_filters('wpcom_update_post', array());
$post_id = isset($_GET['post_id'])?$_GET['post_id']:'';

if(!$post_id && !(isset($options['tougao_on']) && $options['tougao_on']=='1')) {
    wp_redirect(get_option('home'));
}

$item = $post_id ? get_post($post_id) : '';
$post_title = '';
$post_excerpt = '';
$post_content = '';
$post_category = array();
$post_tags = array();
$post_thumbnail_id = '';
$post_thumb = '';
if($item && isset($item->ID)){
    $post_title = $item->post_title;
    $post_excerpt = $item->post_excerpt;
    $post_content = $item->post_content;
    $tags = get_the_tags($item->ID);
    if($tags) {
        foreach ($tags as $tag) {
            $post_tags[] = $tag->name;
        }
    }
    $cats = get_the_category($item->ID);
    $post_thumbnail_id = get_post_thumbnail_id( $item->ID );
    $post_thumb = get_the_post_thumbnail($item->ID, 'full').'<div class="thumb-remove j-thumb-remove"></div>';
}
get_header();?>
    <div class="wrap container">
        <?php if(!isset($_GET['post_id']) || ($post_id && $item && ($item->post_status === 'draft' || $item->post_status === 'inherit' || $item->post_status === 'pending' || ($item->post_status === 'publish' && current_user_can('edit_published_posts')))) && $item->post_author == $current_user->ID){ ?>
        <form method="post" class="post-form" id="j-form">
            <?php
            if((isset($_POST['post-title']) || (isset($_GET['submit']) && $_GET['submit'] === 'true')) && $item && isset($item->ID)){
                echo wpcom_tougao_notice($item);
            }
            wp_nonce_field( 'wpcom_update_post', 'wpcom_update_post_nonce' ); ?>
            <input type="hidden" name="ID" value="<?php echo $post_id;?>">
            <div class="post-form-main">
                <div class="pf-side-label"><h3><?php WPCOM::icon('quill-pen'); _e('Post Content', 'wpcom');?></h3></div>
                <div class="pf-item">
                    <div class="pf-item-input">
                        <input type="text" class="form-control" maxlength="200" id="post-title" name="post-title" placeholder="<?php _e('Please enter the post title', 'wpcom'); ?>" value="<?php echo $post_title;?>" autocomplete="off">
                    </div>
                </div>
                <div class="pf-item">
                    <div class="pf-item-input">
                        <textarea id="post-excerpt" name="post-excerpt" class="form-control" rows="3" placeholder="<?php _e('Please enter the post excerpt (optional)', 'wpcom'); ?>"><?php echo $post_excerpt;?></textarea>
                    </div>
                </div>
                <div class="pf-item">
                    <div class="pf-item-input">
                        <?php wp_editor( $post_content, 'post-content', post_editor_settings(array('textarea_name'=>'post-content')) );?>
                    </div>
                </div>
            </div>
            <div class="post-form-sidebar">
                <div class="pf-submit-wrap">
                    <button type="submit" class="wpcom-btn btn-primary btn-block btn-lg pf-submit"><?php echo $post_id ? __('Update', 'wpcom') : __('Publish', 'wpcom');?></button>
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3><?php WPCOM::icon('folder-open'); _e('Category', 'wpcom');?></h3></div>
                    <div class="pf-side-input">
                        <div class="pf-cat-inner">
                            <div class="pf-cat-list">
                                <?php
                                if(!empty($cats)){
                                    foreach($cats as $cat){
                                        echo '<span class="pf-cat-item" data-value="' . $cat->term_id . '">' . $cat->name . '<i class="close"></i><input type="hidden" name="post-category[]" value="' . $cat->term_id . '"></span>';
                                    }
                                }else{
                                    echo '<span class="pf-cat-placeholder">' . __('Please select a category', 'wpcom') . '</span>';
                                }
                                ?>
                            </div>
                            <ul class="pf-cat-select"></ul>
                            <?php
                            $tougao_cats = isset($options['tougao_cats']) && $options['tougao_cats'] ? $options['tougao_cats'] : array();
                            echo wp_dropdown_categories(array(
                                'include' => $tougao_cats,
                                'hierarchical' => 1,
                                'name' => '',
                                'id'=>'post-category',
                                'class' => 'hide',
                                'select' => '',
                                'echo' => false,
                                'hide_empty' => 0
                            ));
                            ?>
                        </div>
                        <p class="pf-notice"><?php _e('At least one category must be selected', 'wpcom');?></p>
                    </div>
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3><?php WPCOM::icon('tag'); _e('Tags', 'wpcom');?></h3></div>
                    <div class="pf-side-input">
                        <ul id="tag-container"></ul>
                        <p class="pf-notice"><?php _e('Keywords for the post, press Enter to confirm (optional)', 'wpcom');?></p>
                    </div>
                </div>
                <?php if(current_user_can('upload_files')){ ?>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3><?php WPCOM::icon('image'); _e('Thumbnail', 'wpcom'); ?></h3></div>
                    <div class="pf-side-input">
                        <div id="j-thumb-wrap" class="thumb-wrap"><?php echo $post_thumb;?></div>
                        <a class="thumb-selector j-thumb" href="javascript:;"><?php WPCOM::icon('add'); _e('Set Thumbnail Image', 'wpcom');?></a>
                        <p class="pf-notice"><?php _e('The thumbnail will appear in the post list. Setting one is recommended.', 'wpcom');?></p>
                    </div>
                    <input type="hidden" name="_thumbnail_id" id="_thumbnail_id" value="<?php echo $post_thumbnail_id;?>">
                </div>
                <?php } ?>
            </div>
        </form>
        <?php }else{ ?>
            <div class="hentry">
                <p style="text-align:center;padding: 15px 0;font-size:16px;color:#999;"><?php _e('You do not have permission to access this page!', 'wpcom');?></p>
            </div>
        <?php } ?>
    </div>
    <script>
        var postTags = <?php echo json_encode($post_tags);?>;
    </script>
<?php get_footer();?>