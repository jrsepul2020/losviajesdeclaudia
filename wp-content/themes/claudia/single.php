<?php
/**
 * Entrada: foto a todo lo ancho con onda, título en píldora, contenido con índice y barra lateral de afiliados.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cat     = claudia_primary_category();
	$updated = get_the_modified_date( 'U' ) > get_the_date( 'U' ) + DAY_IN_SECONDS;
	?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-hero<?php echo has_post_thumbnail() ? '' : ' entry-hero--plain'; ?>">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'full', [ 'class' => 'entry-hero__img', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => '' ] );
				echo claudia_wave();
			}
			?>
			<div class="entry-hero__content wrap">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( $cat ) : ?>
					<a class="pill" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
				<?php endif; ?>
			</div>
		</header>

		<div class="wrap entry-layout">
			<div class="entry-main">
				<?php claudia_breadcrumbs(); ?>
				<p class="entry-meta">
					<span>Por <a href="<?php echo esc_url( claudia_page_url( 'sobre-mi' ) ); ?>"><?php the_author(); ?></a></span>
					<span><?php echo $updated ? 'Actualizado el ' : 'Publicado el '; ?><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( $updated ? get_the_modified_date() : get_the_date() ); ?></time></span>
					<span><?php echo claudia_icon( 'clock' ); ?> <?php echo esc_html( claudia_reading_time() ); ?> min de lectura</span>
				</p>

				<div class="entry-content">
					<?php the_content(); ?>
				</div>

				<?php
				$tags = get_the_tags();
				if ( $tags ) :
					?>
					<ul class="entry-tags">
						<?php foreach ( array_slice( $tags, 0, 8 ) as $tag ) : ?>
							<li><a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<aside class="author-box">
					<?php echo wp_get_attachment_image( (int) apply_filters( 'claudia_avatar_image_id', 71110 ), 'thumbnail', false, [ 'class' => 'author-box__img', 'loading' => 'lazy', 'alt' => '' ] ); ?>
					<div>
						<p class="author-box__name">Escrito por <?php the_author(); ?></p>
						<p>Viajera sevillana que lleva años recorriendo el mundo por libre y contándolo aquí con rutas, precios y consejos reales.</p>
						<a class="link-arrow" href="<?php echo esc_url( claudia_page_url( 'sobre-mi' ) ); ?>">Más sobre mí <?php echo claudia_icon( 'arrow' ); ?></a>
					</div>
				</aside>
			</div>

			<aside class="entry-sidebar" aria-label="Planifica tu viaje">
				<div class="entry-sidebar__sticky">
					<p class="eyebrow">Planifica tu viaje</p>
					<?php foreach ( claudia_affiliates() as $a ) { claudia_affiliate_card( $a ); } ?>
				</div>
			</aside>
		</div>
	</article>

	<?php
	if ( $cat ) :
		$related = new WP_Query( [
			'cat'                 => $cat->term_id,
			'post__not_in'        => [ get_the_ID() ],
			'posts_per_page'      => 3,
			'orderby'             => 'date',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		] );
		if ( $related->have_posts() ) :
			?>
			<section class="section section--sand">
				<div class="wrap">
					<header class="section__head">
						<h2 class="section__title">Más sobre <?php echo esc_html( $cat->name ); ?></h2>
						<a class="link-arrow" href="<?php echo esc_url( claudia_destination_link( $cat ) ); ?>">Ver todo <?php echo claudia_icon( 'arrow' ); ?></a>
					</header>
					<div class="post-grid">
						<?php
						while ( $related->have_posts() ) {
							$related->the_post();
							claudia_post_card( null, false );
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;

	if ( comments_open() || get_comments_number() ) :
		?>
		<div class="wrap wrap--narrow"><?php comments_template(); ?></div>
		<?php
	endif;
endwhile;

get_footer();
