<?php
/**
 * The template for displaying the single session posts
 *
 * @package wp_conference_schedule_pro
 * @since 1.0.0
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php while ( have_posts() ) : the_post(); 
			$post_id      = get_the_ID();
			$website_url  = get_post_meta( $post_id, 'wpcsp_website_url', true );
			$swag         = wpautop( get_post_meta( $post_id, 'wpcsp_sponsor_swag', true ));
			$terms        = get_the_terms($post_id, 'wpcsp_sponsor_level');
			$register_url = get_option( 'wpcs_field_registration' );
			if( !is_wp_error($terms)){
				$levels = wp_list_pluck($terms,'name');
				$levels_label = ' Level Sponsor';
				$levels = implode(', ',$levels);
			}
			?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				
					<div class="entry-content">
						<?php the_title('<h1 class="entry-title sr-only">','</h1>'); ?>
						<div class="wpcsp-sponsor-grid">
							<div class="wpcsp-media">
								<?php if(has_post_thumbnail()) the_post_thumbnail( 'full', array( 'alt' => get_the_title() ) ); ?>

								<?php
								$social_icons = wpcsp_get_social_links( get_the_ID() );
								if($social_icons){ ?>
									<ul class="wpcsp-sponsor-social">
										<?php foreach ($social_icons as $social_icon) { ?>
											<li class="wpcsp-sponsor-social-icon"><?php echo $social_icon; ?></li>
										<?php } ?>
									</ul>
								<?php } ?>

								<?php if($website_url){ ?>
									<p class="wpcsp-sponsor-website-link"><a rel="sponsored nofollow" href="<?php echo esc_url( $website_url ); ?>">Visit the <?php echo get_the_title(); ?> Website</a></p>
								<?php } ?>
								<?php if($swag){ ?>
									<div class="wpcsp-sponsor-swag">
										<h2>Attendee Swag</h2> 
										<?php
										if ( is_user_logged_in() ) {
											echo $swag;
										} else {
											echo '<p>You must be logged into your attendee account to view swag. <a href="' . wp_login_url() . '">Login</a> or <a href="' . esc_url( $register_url ) . '">get a free ticket</a></p>';
										}
										?>
									</div>
								<?php } ?>
								
							</div>
							<div>
								<?php the_title('<h2>About ','</h1>'); ?>
								<?php if($levels){ ?>
									<p class="wpcsp-sponsor-level"><?php echo $levels.$levels_label; ?></p>
								<?php } ?>
						
								<?php the_content();?>

							</div>

						</div>

					</div><!-- .entry-content -->
						
				</article><!-- #post-${ID} -->

				<?php

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php get_footer();
