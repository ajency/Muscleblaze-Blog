<?php

/**
 *
 * Template Name: Blogs
 */
get_header();
?>
<div class="blogs-banner">
	<?php
	$image = get_field('image');
	if (!empty($image)) : ?>
		<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" class="img-main" />
	<?php endif; ?>
</div>
<div class="alignfull">
	<div class="alignwide">

		<!-- blogs -->
		<?php
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

		$blogsArgs = [
			"post_type"     => 'post',
			"order_by"      => 'date',
			"order"         => 'DESC',
			'paged' => $paged,
			'posts_per_page' => 9, // limit of posts
		];
		$blogsQuery = new WP_Query($blogsArgs);
		?>

		<?php if ($blogsQuery->have_posts()) : ?>
			<div class="dd-card-list">
				<?php while ($blogsQuery->have_posts()) : $blogsQuery->the_post(); ?>
					<?php echo get_the_title() ?><br />
				<?php endwhile; ?>
			</div>

			<?php post_pagination($blogsQuery); ?>
		<?php else :
			//empty screen code
		?>
		<?php endif; ?>

	</div>
</div>
