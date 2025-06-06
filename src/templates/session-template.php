<?php
/**
 * The template for displaying the single session posts
 *
 * @package wp_conference_schedule
 * @since 1.0.0
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();
				$time_format           = 'H:i';
				$session_post          = get_post();
				$session_time          = absint( get_post_meta( $session_post->ID, '_wpcs_session_time', true ) );
				$session_date          = ( $session_time ) ? gmdate( 'F j, Y', $session_time ) : '';
				$session_type          = get_post_meta( $session_post->ID, '_wpcs_session_type', true );
				$session_speakers_text = get_post_meta( $session_post->ID, '_wpcs_session_speakers', true );
				// translators: speaker information for this session.
				$session_speakers_i18n = sprintf( __( '<strong>Speaker:</strong> %s', 'wpa-conference' ), $session_speakers_text );
				$session_speakers_html = ( $session_speakers_text ) ? '<div class="wpsc-single-session-speakers">' . $session_speakers_i18n . '</div>' : null;
				$session_speakers      = apply_filters( 'wpcs_filter_single_session_speakers', $session_speakers_html, $session_post->ID );
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'wpsc-single-session' ); ?>>

					<header class="entry-header">

						<?php the_title( '<h1 class="entry-title wpsc-single-session-title">', '</h1>' ); ?>

						<?php
						if ( $session_date ) {
							$datatime = gmdate( 'Y-m-d\TH:i:s\Z', $session_time );
							echo '<h2 class="wpsc-single-session-time talk-time" data-time="' . $datatime . '"> ' . $session_date . ' at <span class="time-wrapper">' . gmdate( $time_format, $session_time ) . ' UTC</span></h2>';
						} else {
							$parent_session = get_post_meta( $session_post->ID, '_wpad_session', true );
							$session_time   = absint( get_post_meta( $parent_session, '_wpcs_session_time', true ) );
							$session_date   = ( $session_time ) ? gmdate( 'F j, Y', $session_time ) : '';
							$datatime       = gmdate( 'Y-m-d\TH:i:s\Z', $session_time );
							echo '<h2 class="wpsc-single-session-time talk-time" data-time="' . $datatime . '"> ' . $session_date . ' at <span class="time-wrapper">' . gmdate( $time_format, $session_time ) . '</span></h2>';
						}
						?>

						<div class="entry-meta wpsc-single-session-meta">
							<ul class="wpsc-single-session-taxonomies">
								<?php
								$terms = get_the_terms( get_the_ID(), 'wpcs_track' );
								if ( ! is_wp_error( $terms ) ) {
									$term_names = wp_list_pluck( $terms, 'name' );
									$terms      = implode( ', ', $term_names );
									if ( $terms ) {
										echo '<li class="wpsc-single-session-taxonomies-taxonomy wpsc-single-session-tracks"><i class="fas fa-columns" aria-hidden="true"></i>' . $terms . '</li>';
									}
								}

								$terms = get_the_terms( get_the_ID(), 'wpcs_location' );
								if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
									$term_names = wp_list_pluck( $terms, 'name' );
									$terms      = implode( ', ', $term_names );
									if ( $terms ) {
										echo '<li class="wpsc-single-session-taxonomies-taxonomy  wpsc-single-session-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>' . $terms . '</li>';
									}
								}

								do_action( 'wpsc_single_taxonomies' );

								?>
							</ul>

						</div><!-- .meta-info -->

					</header>
					<div class="entry-content">
						<?php
						$sponsor_list = get_post_meta( $session_post->ID, 'wpcsp_session_sponsors', true );
						if ( ! empty( $sponsor_list ) ) {
							?>
							<div class="wpcsp-sponsor-single">
								<h2>Presented by</h2>
								<div class="wpcsp-sponsor-single-row">
									<?php
									$sponsor_url = '';
									$rel         = '';
									foreach ( $sponsor_list as $sponsor_li ) {
										$sponsor_img = get_the_post_thumbnail( $sponsor_li, 'full', array( 'alt' => get_the_title( $sponsor_li ) ) );
										if ( ! empty( $sponsor_img ) ) {
											$wpcsp_website_url = get_post_meta( $sponsor_li, 'wpcsp_website_url', true );

											if ( 'sponsor_site' === $sponsor_url ) {
												if ( ! empty( $wpcsp_website_url ) ) {
													$sponsor_url = $wpcsp_website_url;
												} else {
													$sponsor_url = '#';
												}
												$rel = ' rel="sponsored"';
											} else {

												$sponsor_url = get_the_permalink( $sponsor_li );
												$rel         = '';
											}
											?>
												<div class="wpcsp-sponsor-single-image">
													<a href="<?php echo $sponsor_url; ?>"<?php echo $rel; ?>><?php echo $sponsor_img; ?></a>
												</div>
											<?php
										}
									}
									?>
								</div>
							</div>
							<?php
						}
							the_content();
						?>
					</div><!-- .entry-content -->

					<?php if ( get_option( 'wpcs_field_schedule_page_url' ) ) { ?>
						<footer class="entry-footer">
							<p><a href="<?php echo get_option( 'wpcs_field_schedule_page_url' ); ?>">Return to Schedule</a></p>
						</footer>
					<?php } ?>

				</article><!-- #post-${ID} -->

				<?php

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
