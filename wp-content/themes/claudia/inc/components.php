<?php
/**
 * Componentes de la portada: carruseles (flechas en ordenador, deslizar y puntos en móvil),
 * tarjetas tipo “story” y banners a sangre con la parte baja oscurecida (estilo Apple).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Carrusel: cabecera con título y flechas + pista con scroll-snap. Los puntos los genera main.js.
 *
 * @param string   $title  Título H2 de la sección.
 * @param callable $render Pinta las tarjetas.
 * @param array    $args   link (url “ver todo”), link_label, eyebrow, id.
 */
function claudia_rail( string $title, callable $render, array $args = [] ): void {
	$a = wp_parse_args( $args, [ 'link' => '', 'link_label' => 'Ver todo', 'eyebrow' => '', 'id' => '' ] );
	?>
	<section class="rail"<?php echo $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : ''; ?>>
		<header class="wrap rail__head">
			<div>
				<?php if ( $a['eyebrow'] ) : ?><p class="eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></p><?php endif; ?>
				<h2 class="section__title"><?php echo esc_html( $title ); ?></h2>
			</div>
			<div class="rail__tools">
				<?php if ( $a['link'] ) : ?>
					<a class="link-arrow" href="<?php echo esc_url( $a['link'] ); ?>"><?php echo esc_html( $a['link_label'] ); ?> <?php echo claudia_icon( 'arrow' ); ?></a>
				<?php endif; ?>
				<button class="rail__btn" type="button" data-dir="-1" aria-label="Anteriores"><?php echo claudia_icon( 'chevron' ); ?></button>
				<button class="rail__btn" type="button" data-dir="1" aria-label="Siguientes"><?php echo claudia_icon( 'chevron' ); ?></button>
			</div>
		</header>
		<div class="rail__track" role="list">
			<?php $render(); ?>
		</div>
		<div class="rail__dots" aria-hidden="true"></div>
	</section>
	<?php
}

/**
 * Tarjeta vertical con foto, degradado oscuro abajo, texto blanco y botón (estilo Apple TV).
 *
 * @param array $args title, url, image_id, eyebrow, text, cta, eager.
 */
function claudia_story_card( array $args ): void {
	$a = wp_parse_args( $args, [ 'title' => '', 'url' => '#', 'image_id' => 0, 'eyebrow' => '', 'text' => '', 'cta' => 'Ver guía', 'eager' => false, 'heading' => 'span', 'sizes' => '(min-width: 1100px) 330px, (min-width: 700px) 42vw, 82vw', 'role' => 'listitem' ] );
	?>
	<a class="story"<?php echo $a['role'] ? ' role="' . esc_attr( $a['role'] ) . '"' : ''; ?> href="<?php echo esc_url( $a['url'] ); ?>">
		<?php
		if ( $a['image_id'] ) {
			echo claudia_with_max_srcset( 800, fn() => wp_get_attachment_image( $a['image_id'], 'medium_large', false, [
				'class'    => 'story__img',
				'sizes'    => $a['sizes'],
				'loading'  => $a['eager'] ? 'eager' : 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			] ) );
		}
		?>
		<span class="story__body">
			<?php if ( $a['eyebrow'] ) : ?><span class="story__eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></span><?php endif; ?>
			<<?php echo tag_escape( $a['heading'] ); ?> class="story__title"><?php echo esc_html( $a['title'] ); ?></<?php echo tag_escape( $a['heading'] ); ?>>
			<?php if ( $a['text'] ) : ?><span class="story__text"><?php echo esc_html( $a['text'] ); ?></span><?php endif; ?>
			<span class="story__cta"><?php echo esc_html( $a['cta'] ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Rejilla de subdestinos con tarjetas oscurecidas (hubs y categorías).
 *
 * @param array $items Cada uno: title, url, image_id, eyebrow.
 */
function claudia_story_grid( array $items, string $label = 'Subdestinos' ): string {
	if ( ! $items ) {
		return '';
	}
	ob_start();
	echo '<div class="guides story-grid" aria-label="' . esc_attr( $label ) . '">';
	foreach ( $items as $item ) {
		claudia_story_card( $item + [
			'cta'   => 'Ver destino',
			'role'  => '',
			'sizes' => '(min-width: 1100px) 330px, (min-width: 700px) 31vw, 46vw',
		] );
	}
	echo '</div>';
	return ob_get_clean();
}

/**
 * Paneles de destinos a sangre (número, título, texto y flecha sobre la foto).
 * En ordenador, 4 columnas sin separación; en móvil, se deslizan en horizontal.
 *
 * @param array $items Cada uno: title, url, image_id, text.
 */
function claudia_destination_panels( array $items ): void {
	?>
	<div class="panels" role="list">
		<?php foreach ( array_values( $items ) as $i => $item ) : ?>
			<a class="panel" role="listitem" href="<?php echo esc_url( $item['url'] ); ?>">
				<?php
				if ( ! empty( $item['image_id'] ) ) {
					echo claudia_with_max_srcset( 1200, fn() => wp_get_attachment_image( $item['image_id'], 'full', false, [
						'class'    => 'panel__img',
						'sizes'    => '(min-width: 900px) 25vw, 84vw',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					] ) );
				}
				?>
				<span class="panel__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<span class="panel__body">
					<h3 class="panel__title"><?php echo esc_html( $item['title'] ); ?></h3>
					<?php if ( ! empty( $item['text'] ) ) : ?><span class="panel__text"><?php echo esc_html( $item['text'] ); ?></span><?php endif; ?>
					<span class="panel__arrow"><?php echo claudia_icon( 'arrow' ); ?><span class="screen-reader-text">Ver <?php echo esc_html( $item['title'] ); ?></span></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Banner mosaico a sangre: tres fotos en columnas (cada una ocupa un tercio, así fotos de 800 px
 * se ven nítidas), parte baja oscurecida, título blanco, botón principal y accesos rápidos.
 * En móvil solo se muestra la primera foto.
 *
 * @param array $args title, eyebrow, text, url, cta, images (IDs), links ([label, url, count]).
 */
function claudia_mosaic_banner( array $args ): void {
	$a = wp_parse_args( $args, [ 'title' => '', 'eyebrow' => '', 'text' => '', 'url' => '', 'cta' => 'Descubrir', 'images' => [], 'links' => [] ] );
	?>
	<section class="mosaic-banner">
		<div class="mosaic-banner__images" aria-hidden="true">
			<?php
			foreach ( array_slice( array_filter( $a['images'] ), 0, 3 ) as $i => $image_id ) {
				echo claudia_with_max_srcset( 1200, fn() => wp_get_attachment_image( $image_id, 'full', false, [
					'sizes'    => '(min-width: 900px) 34vw, 100vw',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => '',
					'class'    => 0 === $i ? 'is-first' : '',
				] ) );
			}
			?>
		</div>
		<div class="mosaic-banner__body">
			<?php if ( $a['eyebrow'] ) : ?><p class="banner__eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></p><?php endif; ?>
			<h2 class="banner__title"><?php echo esc_html( $a['title'] ); ?></h2>
			<?php if ( $a['text'] ) : ?><p class="banner__text"><?php echo esc_html( $a['text'] ); ?></p><?php endif; ?>
			<?php if ( $a['links'] ) : ?>
				<ul class="mosaic-banner__links">
					<?php foreach ( $a['links'] as $link ) : ?>
						<li><a class="ghost-pill" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?><?php echo ! empty( $link['count'] ) ? ' <span>' . (int) $link['count'] . '</span>' : ''; ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $a['url'] ) : ?>
				<a class="mosaic-banner__cta" href="<?php echo esc_url( $a['url'] ); ?>"><?php echo esc_html( $a['cta'] ); ?> <?php echo claudia_icon( 'arrow' ); ?></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Banner con foto, parte baja oscurecida y texto blanco centrado.
 *
 * @param array $args title, url, image_id, eyebrow, text, cta, variant (full|half), position (object-position).
 */
function claudia_banner( array $args ): void {
	$a = wp_parse_args( $args, [ 'title' => '', 'url' => '#', 'image_id' => 0, 'eyebrow' => '', 'text' => '', 'cta' => 'Descubrir', 'variant' => 'full', 'position' => 'center', 'heading' => 'h2' ] );
	?>
	<a class="banner banner--<?php echo esc_attr( $a['variant'] ); ?>" href="<?php echo esc_url( $a['url'] ); ?>">
		<?php
		if ( $a['image_id'] ) {
			echo wp_get_attachment_image( $a['image_id'], 'full', false, [
				'class'    => 'banner__img',
				'sizes'    => 'full' === $a['variant'] ? '100vw' : '(min-width: 900px) 50vw, 100vw',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
				'style'    => 'object-position:' . esc_attr( $a['position'] ),
			] );
		}
		?>
		<span class="banner__body">
			<?php if ( $a['eyebrow'] ) : ?><span class="banner__eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></span><?php endif; ?>
			<<?php echo tag_escape( $a['heading'] ); ?> class="banner__title"><?php echo esc_html( $a['title'] ); ?></<?php echo tag_escape( $a['heading'] ); ?>>
			<?php if ( $a['text'] ) : ?><span class="banner__text"><?php echo esc_html( $a['text'] ); ?></span><?php endif; ?>
			<span class="story__cta"><?php echo esc_html( $a['cta'] ); ?></span>
		</span>
	</a>
	<?php
}
