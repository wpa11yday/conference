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
						<?php the_title( '<h1>', '</h1>' ); ?>
						<div class="media-partner-description">
							<?php the_post_thumbnail( 'medium' ); ?>
							<div class="description">
							<?php the_content(); ?>
							</div>
						</div>
						<?php
						$link = get_post_meta( get_the_ID(), 'wpcsp_website_url', true );
						?>
						<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
							<div class="wp-block-button is-style-fill">
								<a class="wp-block-button__link wp-element-button" href="<?php echo esc_attr( $link ); ?>">Visit <?php the_title(); ?></span></a>
							</div>
						</div>
					</div><!-- .entry-content -->

				</article><!-- #post-${ID} -->

				<?php

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
