<?php
/**
 * Archivos: categorías (con foto, subdestinos y enlace a su hub), etiquetas y autores.
 * No se toca la consulta principal: misma paginación (10 por página) y mismas URL /page/N/.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term     = get_queried_object();
$is_cat   = is_category() && $term instanceof WP_Term;
$image_id = $is_cat ? claudia_destination_image_id( $term, 1 ) : 0;
$hub_url  = $is_cat ? claudia_destination_link( $term ) : '';
$has_hub  = $hub_url && $hub_url !== get_term_link( $term );
$children = $is_cat ? get_terms( [ 'taxonomy' => 'category', 'parent' => $term->term_id, 'hide_empty' => true ] ) : [];
$paged    = max( 1, (int) get_query_var( 'paged' ) );
?>

<header class="entry-hero archive-hero<?php echo $image_id ? '' : ' entry-hero--plain'; ?>">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'full', false, [ 'class' => 'entry-hero__img', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => '' ] );
		echo claudia_wave();
	}
	?>
	<div class="entry-hero__content wrap">
		<?php if ( ! $image_id ) { claudia_breadcrumbs(); } ?>
		<h1 class="entry-title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<span class="pill">
			<?php
			$total = (int) $wp_query->found_posts;
			echo esc_html( is_category() ? sprintf( _n( '%d guía', '%d guías', $total, 'claudia' ), $total ) : sprintf( _n( '%d artículo', '%d artículos', $total, 'claudia' ), $total ) );
			echo $paged > 1 ? esc_html( ' · página ' . $paged ) : '';
			?>
		</span>
	</div>
</header>

<div class="wrap archive-body">
	<?php if ( $image_id ) { claudia_breadcrumbs(); } ?>

	<?php if ( 1 === $paged && ( get_the_archive_description() || $has_hub || $children ) ) : ?>
		<div class="archive-intro">
			<?php if ( get_the_archive_description() ) : ?>
				<div class="archive-intro__text"><?php the_archive_description(); ?></div>
			<?php endif; ?>

			<?php
			if ( $children ) {
				$items = [];
				foreach ( $children as $child ) {
					$count   = claudia_hub_count( $child );
					$items[] = [
						'title'    => $child->name,
						'url'      => claudia_destination_link( $child ),
						'image_id' => claudia_destination_image_id( $child ),
						'eyebrow'  => sprintf( _n( '%d guía', '%d guías', $count, 'claudia' ), $count ),
						'heading'  => 'h2',
					];
				}
				echo claudia_story_grid( $items, 'Subdestinos de ' . $term->name );
			}
			?>

			<?php if ( $has_hub ) : ?>
				<a class="button" href="<?php echo esc_url( $hub_url ); ?>">Guía completa para viajar a <?php echo esc_html( $term->name ); ?> <?php echo claudia_icon( 'arrow' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="post-grid archive-grid">
			<?php
			while ( have_posts() ) {
				the_post();
				claudia_post_card();
			}
			?>
		</div>
		<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '‹ Anteriores', 'next_text' => 'Siguientes ›' ] ); ?>
	<?php else : ?>
		<p>Todavía no hay guías aquí. Prueba a buscar otro destino:</p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</div>

<?php
get_footer();
