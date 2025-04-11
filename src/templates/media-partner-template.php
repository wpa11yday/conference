<?php
/**
 * The template for displaying the single media partner posts
 *
 * @package wp_conference_schedule_pro
 * @since 1.0.0
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php
			while ( have_posts() ) :
				the_post();
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<div class="entry-content">
						<?php the_title( '<h1>About ', '</h1>' ); ?>
						<?php the_content(); ?>
					</div><!-- .entry-content -->

				</article><!-- #post-${ID} -->

				<?php

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
