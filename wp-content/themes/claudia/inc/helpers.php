<?php
/**
 * Funciones auxiliares reutilizadas por las plantillas.
 */

defined( 'ABSPATH' ) || exit;

function claudia_minify_css( string $css ): string {
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
	return trim( str_replace( ';}', '}', $css ) );
}

/**
 * Categoría principal de una entrada: la marcada en Yoast o, si no hay, la primera.
 * Es la misma que decide el permalink, así que migas, etiquetas y relacionados coinciden con la URL.
 */
function claudia_primary_category( $post = null ): ?WP_Term {
	$post = get_post( $post );
	$id   = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_category', true );
	if ( $id ) {
		$term = get_term( $id, 'category' );
		if ( $term instanceof WP_Term && has_term( $term->term_id, 'category', $post ) ) {
			return $term;
		}
	}
	$cats = get_the_category( $post->ID );
	return $cats[0] ?? null;
}

function claudia_reading_time( $post = null ): int {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post ) ) );
	return max( 1, (int) round( $words / 220 ) );
}

/**
 * Página hub de una categoría: la vinculada en la migración (_claudia_hub_category) o, si no hay,
 * una página con su mismo slug o “viajar-a-slug”.
 */
function claudia_hub_page( WP_Term $term ): ?WP_Post {
	static $cache = [];
	if ( ! array_key_exists( $term->term_id, $cache ) ) {
		$found = get_posts( [ 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 1, 'meta_key' => '_claudia_hub_category', 'meta_value' => $term->slug, 'no_found_rows' => true ] );
		$page  = $found[0] ?? ( get_page_by_path( $term->slug ) ?: get_page_by_path( 'viajar-a-' . $term->slug ) );
		$cache[ $term->term_id ] = $page && 'publish' === $page->post_status ? $page : null;
	}
	return $cache[ $term->term_id ];
}

/**
 * Enlace preferente de un destino: su página hub si existe; si no, el archivo de la categoría.
 */
function claudia_destination_link( WP_Term $term ): string {
	$hub = claudia_hub_page( $term );
	return $hub ? get_permalink( $hub ) : get_term_link( $term );
}

/**
 * Destinos principales (categorías de primer nivel) ordenados por número total de guías,
 * sumando sus subcategorías (España incluye Cádiz, Granada…). Cacheado 12 horas.
 *
 * @return array<int, array{term: WP_Term, total: int}>
 */
function claudia_top_destinations( int $limit = 9 ): array {
	$data = get_transient( 'claudia_top_destinations' );
	if ( false === $data ) {
		$exclude = array_filter( [ (int) get_option( 'default_category' ), get_cat_ID( 'Blog' ) ] );
		$data    = [];
		foreach ( get_terms( [ 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false, 'exclude' => $exclude, 'fields' => 'ids' ] ) as $id ) {
			$ids   = array_merge( [ $id ], get_term_children( $id, 'category' ) );
			$total = ( new WP_Query( [ 'category__in' => $ids, 'posts_per_page' => 1, 'fields' => 'ids' ] ) )->found_posts;
			$data[ $id ] = $total;
		}
		arsort( $data );
		set_transient( 'claudia_top_destinations', $data, 12 * HOUR_IN_SECONDS );
	}
	$out = [];
	foreach ( array_slice( $data, 0, $limit, true ) as $id => $total ) {
		$term = get_term( $id, 'category' );
		if ( $term instanceof WP_Term ) {
			$out[] = [ 'term' => $term, 'total' => $total ];
		}
	}
	return $out;
}
add_action( 'save_post_post', fn() => delete_transient( 'claudia_top_destinations' ) );

/**
 * Imagen de un destino: la destacada de su hub o, en su defecto, la de una entrada reciente.
 * $offset permite saltarse entradas (en el archivo, la primera ya sale destacada debajo).
 */
function claudia_destination_image_id( WP_Term $term, int $offset = 0 ): int {
	$hub = claudia_hub_page( $term );
	if ( $hub && has_post_thumbnail( $hub ) ) {
		return (int) get_post_thumbnail_id( $hub );
	}
	$recent = get_posts( [ 'cat' => $term->term_id, 'numberposts' => $offset + 1, 'meta_key' => '_thumbnail_id', 'fields' => 'ids', 'no_found_rows' => true ] );
	$pick   = $recent[ min( $offset, count( $recent ) - 1 ) ] ?? 0;
	return $pick ? (int) get_post_thumbnail_id( $pick ) : 0;
}

/**
 * Tarjeta de foto con el título sobre la imagen (patrón principal del diseño).
 *
 * @param array $args title, url, image_id, label, ratio (portrait|landscape), sizes.
 */
function claudia_photo_card( array $args ): void {
	$a = wp_parse_args( $args, [ 'title' => '', 'url' => '#', 'image_id' => 0, 'label' => '', 'ratio' => 'portrait', 'sizes' => '(min-width: 1100px) 300px, 70vw', 'heading' => 'h3' ] );
	?>
	<a class="photo-card photo-card--<?php echo esc_attr( $a['ratio'] ); ?>" href="<?php echo esc_url( $a['url'] ); ?>">
		<?php
		if ( $a['image_id'] ) {
			echo claudia_with_max_srcset( 800, fn() => wp_get_attachment_image( $a['image_id'], 'medium_large', false, [ 'sizes' => $a['sizes'], 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ] ) );
		}
		?>
		<span class="photo-card__body">
			<?php if ( $a['label'] ) : ?><span class="pill pill--small"><?php echo esc_html( $a['label'] ); ?></span><?php endif; ?>
			<<?php echo tag_escape( $a['heading'] ); ?> class="photo-card__title"><?php echo esc_html( $a['title'] ); ?></<?php echo tag_escape( $a['heading'] ); ?>>
		</span>
	</a>
	<?php
}

/**
 * Tarjeta de artículo clásica: imagen, categoría, título y extracto.
 */
function claudia_post_card( $post = null, bool $excerpt = true ): void {
	$post = get_post( $post );
	$cat  = claudia_primary_category( $post );
	?>
	<article class="post-card">
		<a class="post-card__media" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo claudia_with_max_srcset( 800, fn() => get_the_post_thumbnail( $post, 'medium_large', [ 'sizes' => '(min-width: 1100px) 360px, (min-width: 700px) 45vw, 92vw', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ] ) ); ?>
		</a>
		<div class="post-card__body">
			<?php if ( $cat ) : ?>
				<a class="pill pill--small" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
			<?php endif; ?>
			<h3 class="post-card__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
			<?php if ( $excerpt ) : ?>
				<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 20, '…' ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Socios afiliados que Claudia ya enlaza en sus guías. Un único sitio para cambiar textos y enlaces.
 *
 * - need / desc: para qué sirve, antes de hacer clic.
 * - guide: entrada del blog que lo explica (botón secundario, enlace interno).
 * - quote: recomendación de Claudia en primera persona. Solo se publica con quote_ok = true;
 *   mientras tanto la ven únicamente los administradores, marcada como borrador.
 * - offer: descuento o código. Vacío hasta que Claudia confirme las condiciones reales.
 */
function claudia_affiliates(): array {
	return apply_filters( 'claudia_affiliates', [
		[
			'name'        => 'IATI Seguros',
			'brand'       => 'IATI',
			'domain'      => 'iatiseguros.com',
			'tag'         => 'Seguro de viaje',
			'need'        => '¿Y si te pones enfermo o pierdes la maleta?',
			'desc'        => 'Seguro de viaje con asistencia médica, cancelación y equipaje para viajar por todo el mundo.',
			'guide'       => 37202, // ¿Necesito un seguro para viajar por Europa? Mi consejo
			'guide_label' => 'Por qué lo uso',
			'quote'       => 'Es el seguro que contrato en mis viajes.', // respaldado por «Mejor seguro de viaje para Marruecos: el que yo uso»
			'quote_ok'    => false,
			'offer'       => '',
			'url'         => 'https://www.iatiseguros.com/contrato_agencia.phtml?id=425&r=12076015707290',
			'tone'        => 'teal',
		],
		[
			'name'        => 'Holafly',
			'brand'       => 'Holafly',
			'domain'      => 'holafly.com',
			'tag'         => 'Internet en el extranjero',
			'need'        => '¿Quieres internet nada más aterrizar?',
			'desc'        => 'eSIM de datos que instalas en el móvil antes de salir: sin cambiar de tarjeta ni pagar roaming.',
			'guide'       => 71263, // Cómo tener internet en Italia (y en Roma) sin estrés usando eSIM de Holafly
			'guide_label' => 'Por qué lo uso',
			'quote'       => 'La uso para tener datos desde que bajo del avión.',
			'quote_ok'    => false,
			'offer'       => '',
			'url'         => 'https://holafly.sjv.io/PzyDZj',
			'tone'        => 'pink',
		],
		[
			'name'        => 'N26',
			'brand'       => 'N26',
			'domain'      => 'n26.com',
			'tag'         => 'Pagar en otra moneda',
			'need'        => '¿Cómo pagar fuera sin perder dinero?',
			'desc'        => 'Banco online con tarjeta para pagar y sacar efectivo en el extranjero, gestionada desde la app.',
			'guide'       => 72306, // Cómo pagar en tus viajes al extranjero sin perder dinero en comisiones (recomienda Imagin, no N26)
			'guide_label' => 'Leer la guía',
			'quote'       => '',
			'quote_ok'    => false,
			'offer'       => '',
			'url'         => 'https://n26-eu.c2nwa3.net/DyQDmo',
			'tone'        => 'ink',
		],
		[
			'name'        => 'Civitatis',
			'brand'       => 'Civitatis',
			'domain'      => 'civitatis.com',
			'tag'         => 'Visitas y excursiones',
			'need'        => '¿Visitas guiadas y excursiones en español?',
			'desc'        => 'Free tours, excursiones y entradas en español en cientos de destinos, con reserva online.',
			'guide'       => 33323, // Las mejores excursiones desde Nueva York de un día
			'guide_label' => 'Ver un ejemplo',
			'quote'       => 'Reservo así las excursiones que no puedo hacer por mi cuenta.',
			'quote_ok'    => false,
			'offer'       => '',
			'url'         => 'https://www.civitatis.com/es/?aid=1143',
			'tone'        => 'orange',
		],
	] );
}

/**
 * Tarjeta de afiliado: primero la necesidad y para qué sirve; después, dos salidas claras:
 * la web externa (enlace de afiliado, con su dominio visible) y la guía del blog que lo explica.
 */
function claudia_affiliate_card( array $a ): void {
	$guide      = ! empty( $a['guide'] ) ? get_post( $a['guide'] ) : null;
	$guide_url  = $guide && 'publish' === $guide->post_status ? get_permalink( $guide ) : '';
	$show_quote = ! empty( $a['quote'] ) && ( ! empty( $a['quote_ok'] ) || current_user_can( 'edit_theme_options' ) );
	$label_id   = wp_unique_id( 'aff-' );
	?>
	<article class="aff-card aff-card--<?php echo esc_attr( $a['tone'] ); ?>" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
		<p class="aff-card__meta">
			<span class="aff-card__tag"><?php echo esc_html( $a['tag'] ); ?></span>
			<span class="aff-card__badge">Enlace de afiliado</span>
		</p>
		<h3 class="aff-card__need" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $a['need'] ); ?></h3>
		<p class="aff-card__desc"><strong><?php echo esc_html( $a['name'] ); ?>:</strong> <?php echo esc_html( $a['desc'] ); ?></p>
		<?php if ( ! empty( $a['offer'] ) ) : ?>
			<p class="aff-card__offer"><?php echo esc_html( $a['offer'] ); ?></p>
		<?php endif; ?>
		<?php if ( $show_quote ) : ?>
			<blockquote class="aff-card__quote<?php echo empty( $a['quote_ok'] ) ? ' is-draft' : ''; ?>">
				«<?php echo esc_html( $a['quote'] ); ?>» <cite>Claudia</cite>
				<?php if ( empty( $a['quote_ok'] ) ) : ?><span class="aff-card__draft" title="Solo lo ven los administradores hasta que Claudia lo confirme">Borrador</span><?php endif; ?>
			</blockquote>
		<?php endif; ?>
		<div class="aff-card__actions">
			<a class="aff-card__go" href="<?php echo esc_url( $a['url'] ); ?>" rel="sponsored nofollow noopener" target="_blank">
				Ver en <?php echo esc_html( $a['brand'] ); ?> <span aria-hidden="true">↗</span>
				<span class="screen-reader-text">(abre <?php echo esc_html( $a['domain'] ); ?> en otra pestaña)</span>
			</a>
			<?php if ( $guide_url ) : ?>
				<a class="aff-card__guide" href="<?php echo esc_url( $guide_url ); ?>"><?php echo esc_html( $a['guide_label'] ); ?> <?php echo claudia_icon( 'arrow' ); ?></a>
			<?php endif; ?>
		</div>
		<p class="aff-card__domain">Te lleva a <?php echo esc_html( $a['domain'] ); ?></p>
	</article>
	<?php
}

/**
 * Correo de contacto: el primer mailto: de la página de contacto (así siempre coincide con el que publica Claudia).
 */
function claudia_contact_email(): string {
	$page = get_page_by_path( 'contacto-los-viajes-de-claudia-campos' );
	if ( $page && preg_match( '/mailto:([^"\'?\s>]+)/i', $page->post_content, $m ) ) {
		return sanitize_email( $m[1] );
	}
	return '';
}

function claudia_socials(): array {
	return [
		'instagram' => 'https://www.instagram.com/lvdclaudia/',
		'facebook'  => 'https://www.facebook.com/losviajesdeclaudia/',
		'youtube'   => 'https://youtube.com/channel/UCkhypBmC1ZZvtqLfyN0585w',
		'x'         => 'https://twitter.com/lvdclaudia',
	];
}

/**
 * Iconos SVG en línea (sin fuentes de iconos ni peticiones extra).
 */
function claudia_icon( string $name ): string {
	$paths = [
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v7h4v-7h3l1-4h-4V8z"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z" fill="currentColor"/>',
		'x'         => '<path d="M4 4l16 16M20 4 4 20"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'chevron'   => '<path d="m6 9 6 6 6-6"/>',
		'send'      => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
	];
	return '<svg class="icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/**
 * Onda inferior de las cabeceras con foto (firma visual del diseño de referencia).
 */
function claudia_wave(): string {
	return '<svg class="wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path d="M0 60c180-40 360-50 560-20s400 50 600 10c110-22 200-30 280-25V90H0z"/></svg>';
}
