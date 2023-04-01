<?
  $page_data = get_page_by_path('blog/');
  $page_id = $page_data->ID;
  $post_gallery_title = get_field('post_gallery_title', $page_id);
  $post_gallery_posts_per_page = get_field('post_gallery_posts_per_page', $page_id);

  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  $param_category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

  $categories = get_categories( 'product_cat', array( 'child_of' => $child_term->term_id, 'hide_empty' => true ) );

  $categories = get_categories([
    'orderby' => 'term_id',
    'parent'     => 0,
    'hide_empty' => 0, 
  ]);

  $args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'post_modified',
    'order' => 'DESC',
    'posts_per_page' => $post_gallery_posts_per_page,
  ];
  if($param_category_id != '') $args['cat'] = $param_category_id;
  $post_list = new WP_query($args);
?>

<section id="post_gallery">
  <div class="post-gallery-inner">
    <div class="title text-center">
      <h2 class="sm-title title-font"><?=$post_gallery_title?></h2>
    </div>
    <div class="category-list-wrapper">
      <div class="category-list">
        <? foreach ($categories as $category_item) { ?>
          <? if($category_item->cat_ID == 1) continue; ?>
          <div class="category-item">
            <span data-category-id="<?=$category_item->cat_ID?>" class="category-button hover-point sm-content black-color <? echo ($category_item->cat_ID == $param_category_id ? 'active' : '') ?>">
              <?=strtoupper($category_item->name)?>
            </span>
          </div>
        <? } ?>
      </div>
    </div>
    <div class="post-list">
      <? get_template_part('segments/blog-home/post-gallery-items', null, ['posts' => $post_list->posts]) ?>
    </div>
    <div class="see-more-work text-center">
      <a href="javascript:;" class="btn-see-more-work btn-normal trans-button-hover xs-content btn-load-more" 
        data-action="get_more_posts" data-category-id="<?=$param_category_id?>" 
        data-paged="1" 
        style="display: <? echo ($post_list->found_posts > $post_gallery_posts_per_page) ? 'inline-block' : 'none' ?>"
      >
        LOAD MORE
      </a>
    </div>
  </div>
</section>
