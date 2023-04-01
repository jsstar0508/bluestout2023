<?
get_header(null, ['body_id' => 'blog_post', 'header_class' => 'no-header-bg']);
$categories = get_the_category();
$category_names = [];
foreach ($categories as $category_id) {
  $category = get_category($category_id);
  $category_name = $category->name;
  $category_names[] = $category_name;
}

$category_name = isset($categories[0]->name) ? $categories[0]->name : 'undefined';
$category_slug = isset($categories[0]->slug) ? $categories[0]->slug : 'undefined';
$category_id = '';
if($category_slug != '') {
  $category = get_category_by_slug($category_slug);
  $category_id = $category->cat_ID;
}
?>

<div class="page-hero fluid-padding">
  <div class="container">
    <div class="main-content">
      <article class="article">
        <div class="breadcrumb">
          <ul class="breadmenu">
            <li class="breadcrumb-item all"><a href="<?=get_full_url('blog')?>"><img src="<?=bloginfo('template_url');?>/assets/img/gray-prev.png"></a> <a href="<?=get_full_url('blog')?>">All Articles</a></li>
            <li class="breadcrumb-item"><a href="<?=get_full_url('blog').'?category_id='.$category_id?>"><?=$category_name?></a></li>
          </ul>
          <div class="category">
              <? foreach ($category_names as $category_name) { ?>
                <span class="mini-border-radius category-sign xxs-content"><?=strtoupper($category_name)?></span>
              <? } ?>
            <span class="category-sign"><?=$category_sign?></span>
          </div>
        </div>
        <h1 class="title sm-title title-font">
          <? the_title(); ?>
        </h1>
        <div class="content">
          <? the_content(); ?>
        </div>
      </article>
      <? get_template_part('segments/blog-post/newsletter-form', null, ['type' => 'sidebar', 'class' => 'd-none d-lg-block']); ?>
    </div>
  </div>
  <? get_template_part('segments/blog-post/continue-learning'); ?>
  <? get_template_part('segments/blog-post/newsletter-form', null, ['type' => 'footer', 'class' => 'd-none d-md-block']); ?>
  <? get_template_part('segments/blog-post/newsletter-form', null, ['type' => 'sidebar', 'class' => 'd-sm-block d-md-none']); ?>
</div>

<? get_footer();
