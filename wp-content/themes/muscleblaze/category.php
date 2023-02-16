<?php
get_header();

$category = get_queried_object();
$args = array(
	'post_type' => 'post',
	'post_status' => 'publish',
	'posts_per_page' => 5000,
	'paged' => $paged,
	'category__in' => $category->term_id,
);
$my_query = new WP_Query($args);
?>

<div class="body-container">
	<div class="blogs-banner">
		<?php if (get_theme_mod('header_image')) : ?>
			<img src="<?php echo esc_url(get_theme_mod('header_image')); ?>" alt="Header Image" class="img-main">
		<?php endif; ?>
	</div>
	<div class="about-container">
		<div class="blog-list">
			<div class="row-same-height" id="postContainer" data-type="post" data-count="<?php echo $my_query->found_posts; ?>">
				<?php
				if ($my_query->have_posts()) :
					while ($my_query->have_posts()) : $my_query->the_post(); ?>
						<a href="<?php the_permalink(); ?>" alt="<?php the_title(); ?>" title="<?php the_title(); ?>" class="link-post">
							<div class="col-same-height">
								<div class="content">
									<div class="TopArticleCard-Container">
										<div class="img-container">
											<?php
											$thumbnail = get_post_meta(get_the_id(), 'large_img', true);
											if ($thumbnail) { ?>
												<img src="<?php echo $thumbnail; ?>" alt="<?php the_title(); ?>" title="<?php the_title(); ?>" loading="lazy" />
											<?php } else if (has_post_thumbnail()) {
												the_post_thumbnail('large', ['title' => get_the_title()], ['alt' => get_the_title()]); ?>
											<?php
											} else { ?>
												<img src="<?php echo get_site_url(); ?>/wp-content/uploads/2020/09/default.jpg" alt="<?php the_title(); ?>" title="<?php the_title(); ?>" loading="lazy" />
											<?php } ?>
										</div>
										<div class="article-description">
											<div class="article-tag">
												<?php $cat = get_the_category();
												echo $cat[0]->cat_name; ?>
											</div>
											<div class="content">
												<div class="article-title">
													<?php the_title(); ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</a>
				<?php endwhile;
				endif;
				wp_reset_postdata();
				?>
			</div>

		</div>
	</div>

</div>


<?php get_footer();
