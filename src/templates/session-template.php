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
			while ( have_posts() ) : the_post(); 
				$time_format      = 'H:i';
				$post             = get_post();
				$session_time     = absint( get_post_meta( $post->ID, '_wpcs_session_time', true ) );
				$session_date     = ( $session_time ) ? date( 'F j, Y', $session_time ) : '';
				$session_type     = get_post_meta( $post->ID, '_wpcs_session_type', true );
				$session_speakers_text = get_post_meta( $post->ID, '_wpcs_session_speakers',  true );
				$session_speakers_html = ($session_speakers_text ) ? '<div class="wpsc-single-session-speakers"><strong>Speaker:</strong> '.$session_speakers_text .'</div>' : null;
				$session_speakers = apply_filters( 'wpcs_filter_single_session_speakers', $session_speakers_html, $post->ID);
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class('wpsc-single-session'); ?>>

					<header class="entry-header">

						<?php the_title( '<h1 class="entry-title wpsc-single-session-title">', '</h1>' ); ?>

						<?php
						if ( $session_date ) {
							$datatime  = gmdate( 'Y-m-d\TH:i:s\Z', $session_time );
							echo '<h2 class="wpsc-single-session-time talk-time" data-time="' . $datatime . '"> '.$session_date.' at <span class="time-wrapper">'.gmdate($time_format, $session_time).' UTC</span></h2>';
						} else {
							$parent_session = get_post_meta( $post->ID, '_wpad_session', true );
							$session_time   = absint( get_post_meta( $parent_session, '_wpcs_session_time', true ) );
							$session_date   = ( $session_time ) ? gmdate( 'F j, Y', $session_time ) : '';
							$datatime       = gmdate( 'Y-m-d\TH:i:s\Z', $session_time );
							echo '<h2 class="wpsc-single-session-time talk-time" data-time="' . $datatime . '"> '.$session_date.' at <span class="time-wrapper">'.gmdate($time_format, $session_time).'</span></h2>';
						}
						?>
						
						<div class="entry-meta wpsc-single-session-meta">
							<ul class="wpsc-single-session-taxonomies">
								<?php 
								$terms = get_the_terms(get_the_ID(), 'wpcs_track');
								if ( !is_wp_error($terms)){
									$term_names = wp_list_pluck($terms, 'name'); 
									$terms = implode(", ", $term_names);
									if($terms)
										echo '<li class="wpsc-single-session-taxonomies-taxonomy wpsc-single-session-tracks"><i class="fas fa-columns"></i>'.$terms.'</li>';
								}

								$terms = get_the_terms(get_the_ID(), 'wpcs_location');
								if ( !is_wp_error($terms) && !empty($terms)){
									$term_names = wp_list_pluck($terms, 'name'); 
									$terms = implode(", ", $term_names);
									if($terms)
										echo '<li class="wpsc-single-session-taxonomies-taxonomy  wpsc-single-session-location"><i class="fas fa-map-marker-alt"></i>'.$terms.'</li>';
								}

								do_action('wpsc_single_taxonomies');
								
								?>
							</ul>

						</div><!-- .meta-info -->

					</header>
					<div class="entry-content">
						<?php
						$sponsor_list = get_post_meta($post->ID,'wpcsp_session_sponsors',true);
						if ( ! empty( $sponsor_list ) ){
							?>
							<div class="wpcsp-sponsor-single">
								<h2>Presented by</h2>
								<div class="wpcsp-sponsor-single-row">
									<?php
										$sponser_url = "";
										$target = "";
										foreach($sponsor_list as $sponser_li){ 
											$sponsor_img = get_the_post_thumbnail( $sponser_li, 'full', array( 'alt' => get_the_title( $sponser_li ) ) );
											if(!empty($sponsor_img)){
												$sponsor_url = get_option('wpcsp_field_sponsor_page_url');
												$wpcsp_website_url = get_post_meta($sponser_li,'wpcsp_website_url',true);
					
												if($sponsor_url == "sponsor_site"){
													if(!empty($wpcsp_website_url)){
														$sponser_url = $wpcsp_website_url;
														$target = "_blank";
													}else{
														$sponser_url = "#";
														$target = "";
													}
												}else{

													$sponser_url =  get_the_permalink($sponser_li);
												}
												?>
												<div class="wpcsp-sponsor-single-image">
													<a href="<?php echo $sponser_url;?>"><?php echo $sponsor_img; ?></a>
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
							<div class="wpcs-session-links">
								<?php
								wpcs_slides( get_the_ID() );
								wpcs_resources( get_the_ID() );
								?>
							</div>
							<?php
							$speakers  = wpad_session_speakers( get_the_ID(), $session_type );
							echo $speakers['html'];
						?>
					</div><!-- .entry-content -->

					<?php if(get_option('wpcs_field_schedule_page_url')){ ?>
						<footer class="entry-footer">
							<p><a href="<?php echo get_option('wpcs_field_schedule_page_url'); ?>">Return to Schedule</a></p>
						</footer>
					<?php } ?>

				</article><!-- #post-${ID} -->

				<?php

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php get_footer();
