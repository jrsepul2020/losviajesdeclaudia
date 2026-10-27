<?php
/**
 * Plantilla genérica: archivos de categoría, etiqueta, autor, búsqueda y páginas sencillas.
 * Las plantillas específicas (hub de destino, 404…) llegan en las siguientes fases.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<?php claudia_breadcrumbs(); ?>
		<h1 class="page-title">
			<?php
			if ( is_singular() ) {
				single_post_title();
			} elseif ( is_search() ) {
				printf( 'Resultados para “%s”', esc_html( get_search_query() ) );
			} else {
				echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
			}
			?>
		</h1>
		<?php if ( is_archive() && get_the_archive_description() ) : ?>
			<div class="page-intro"><?php the_archive_description(); ?></div>
		<?php endif; ?>
	</div>
</div>

<div class="wrap section">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="entry-content wrap--narrow"><?php the_content(); ?></div>
		<?php endwhile; ?>
	<?php elseif ( have_posts() ) : ?>
		<div class="post-grid">
			<?php
			while ( have_posts() ) {
				the_post();
				claudia_post_card();
			}
			?>
		</div>
		<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '‹ Anteriores', 'next_text' => 'Siguientes ›' ] ); ?>
	<?php else : ?>
		<p>No hay nada por aquí todavía. Prueba a buscar otro destino:</p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
