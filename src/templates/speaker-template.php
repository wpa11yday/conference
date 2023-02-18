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

			<?php
			/* Start the Loop */
			while ( have_posts() ) : the_post();
				$post_id = get_the_ID();
				$first_name = get_post_meta( $post_id, 'wpcsp_first_name', true );
				$last_name = get_post_meta( $post_id, 'wpcsp_last_name', true );
				$full_name = $first_name.' '.$last_name;
				$title = get_post_meta( $post_id, 'wpcsp_title', true );
				$pronouns = get_post_meta( $post_id, 'wpcsp_pronouns', true );
				$country = get_post_meta( $post_id, 'wpcsp_country', true );
				$organization = get_post_meta( $post_id, 'wpcsp_organization', true );
				$schedule_page_url = get_option('wpcs_field_schedule_page_url');
				$speaker_page_url = get_option('wpcsp_field_speakers_page_url');

				$args = [
					'numberposts' => -1,
					'post_type'   => 'wpcs_session',
					'meta_query' => array(
						array(
							'key' => 'wpcsp_session_speakers',
							'value' => $post->ID,
							'compare' => 'LIKE',
						)
					)
				];
				$sessions = get_posts($args);
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<div class="entry-content">

						<div class="wpcsp-speaker-grid">

							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'full' );
							}
							if ( $pronouns ) {
								$pronouns = ' <span class="wpcsp-speaker-pronouns">(' . esc_html( $pronouns ) . ')</span>';
							}
							?>

							<div>
								<h1 class="entry-title"><?php echo esc_html( $full_name ); echo $pronouns; ?></h1>

								<div class="wpcsp-speaker-details">
									<?php
									if ( $title ) {
										echo '<p class="wpcsp-speaker-title">'. esc_html( $title ).'</p>';
									}
									if ( $organization ) {
										echo '<p class="wpcsp-speaker-organization">'. esc_html( $organization ) .'</p>';
									}
									if ( $country ) {
										echo '<p class="wpcsp-speaker-country"><span class="dashicons dashicons-location"></span>' . esc_html( $country ) . '</p>';
									}
									?>
								</div>

								<?php
								$social_icons = wpcsp_get_social_links( get_the_ID() );
								if($social_icons){ ?>
									<ul class="wpcsp-speaker-social">
										<?php foreach ($social_icons as $social_icon) { ?>
											<li class="wpcsp-speaker-social-icon"><?php echo $social_icon; ?></li>
										<?php } ?>
									</ul>
								<?php } ?>

								<h2>About <?php echo esc_html( $full_name ); ?></h2>

								<?php the_content();?>

								<?php if($sessions){ ?>
									<h2>Sessions</h2>
									<ul>
										<?php foreach ($sessions as $session) { ?>
											<li>
												<a href="<?php echo get_the_permalink($session->ID); ?>"><?php echo esc_html( $session->post_title ); ?></a>
											</li>
										<?php } ?>
									</ul>
								<?php } ?>

								<?php if($speaker_page_url || $schedule_page_url){ ?>
									<p class="wpcsp-speaker-links">
										<?php if($speaker_page_url){ ?>
											<a class="wpcsp-speaker-link wpcsp-speaker-link-speakers" href="<?php echo esc_url( $speaker_page_url ); ?>">Go to Speakers List</a>
										<?php } ?>

										<?php if($schedule_page_url){ ?>
											<a class="wpcsp-speaker-link-schedule" href="<?php echo esc_url( get_bloginfo('url') ); ?>">Go to Conference Home</a>
										<?php } ?>
									</p>
								<?php } ?>

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
