<?php
/**
 * Páginas: hubs de destino (foto con onda, título en píldora y rejillas de guías) y páginas sencillas.
 * La foto sale de la imagen destacada de la página o, si no tiene, de la última guía de su categoría.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hub      = claudia_hub_category();
	$image_id = claudia_page_hero_image_id();
	$guides   = $hub ? claudia_hub_count( $hub ) : 0;
	?>
	<article <?php post_class( 'page-entry' ); ?>>
		<header class="entry-hero<?php echo $image_id ? '' : ' entry-hero--plain'; ?>">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'full', false, [ 'class' => 'entry-hero__img', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => '' ] );
				echo claudia_wave();
			}
			?>
			<div class="entry-hero__content wrap">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( $hub ) : ?>
					<span class="pill"><?php echo esc_html( sprintf( _n( '%d guía', '%d guías', $guides, 'claudia' ), $guides ) ); ?></span>
				<?php endif; ?>
			</div>
		</header>

		<div class="wrap hub">
			<?php claudia_breadcrumbs(); ?>
			<div class="entry-content hub__content">
				<?php the_content(); ?>
			</div>
		</div>
	</article>

	<?php if ( $hub ) : ?>
		<section id="planifica" class="section section--sand">
			<div class="wrap">
				<header class="section__head">
					<h2 class="section__title">Planifica tu viaje</h2>
				</header>
				<div class="aff-grid aff-grid--flat">
					<?php foreach ( claudia_affiliates() as $a ) { claudia_affiliate_card( $a ); } ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
