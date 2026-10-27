<?php
/**
 * Portada (pensada primero para móvil): cabecera con foto y buscador, afiliados, carruseles
 * estilo Apple, banners a sangre alternando con secciones de ancho normal y un texto SEO final.
 * El H1 se mantiene igual que en la web anterior.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hero_id  = (int) apply_filters( 'claudia_hero_image_id', 42174 );   // Claudia en los baños Széchenyi
$about_id = (int) apply_filters( 'claudia_about_banner_id', 30315 ); // Claudia frente a los acantilados de Moher
$seasonal = apply_filters( 'claudia_seasonal', [ 'title' => 'Viajes de Navidad', 'search' => 'navid', 'link' => claudia_page_url( 'donde-viajar-en-navidad' ) ] );
$top      = claudia_top_destinations( 10 );
$total    = (int) wp_count_posts( 'post' )->publish;
?>

<section class="hero-card" aria-labelledby="hero-title">
	<?php echo wp_get_attachment_image( $hero_id, 'full', false, [ 'class' => 'hero-card__img', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => '' ] ); ?>
	<div class="hero-card__content">
		<p class="hero-card__eyebrow">Los Viajes de Claudia</p>
		<h1 id="hero-title" class="hero-card__title">Las mejores guías y rutas para viajar por libre</h1>
		<form class="hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="hero-s">Busca tu destino</label>
			<input id="hero-s" type="search" name="s" placeholder="Busca tu destino soñado…" autocomplete="off" enterkeyhint="search">
			<button type="submit"><?php echo claudia_icon( 'search' ); ?><span>Buscar</span></button>
		</form>
		<ul class="hero-card__chips">
			<?php foreach ( array_slice( $top, 0, 4 ) as [ 'term' => $term ] ) : ?>
				<li><a href="<?php echo esc_url( claudia_destination_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
$season_q = new WP_Query( [
	's'                   => $seasonal['search'],
	'search_columns'      => [ 'post_title' ],
	'post_type'           => 'post',
	'posts_per_page'      => 10,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
] );
if ( $season_q->have_posts() ) {
	claudia_rail( $seasonal['title'], function () use ( $season_q ) {
		while ( $season_q->have_posts() ) {
			$season_q->the_post();
			$cat = claudia_primary_category();
			claudia_story_card( [
				'title'    => get_the_title(),
				'url'      => get_permalink(),
				'image_id' => (int) get_post_thumbnail_id(),
				'eyebrow'  => $cat ? $cat->name : '',
			] );
		}
		wp_reset_postdata();
	}, [ 'eyebrow' => 'Especial temporada', 'link' => $seasonal['link'] ] );
}

if ( $top ) {
	[ 'term' => $first, 'total' => $first_total ] = $top[0];

	// Provincias (subcategorías) del destino con más guías: dan las fotos del mosaico y los accesos rápidos.
	$provinces = [];
	foreach ( get_terms( [ 'taxonomy' => 'category', 'parent' => $first->term_id, 'hide_empty' => false ] ) as $child ) {
		$provinces[] = [ 'term' => $child, 'count' => claudia_hub_count( $child ) ];
	}
	usort( $provinces, fn( $x, $y ) => $y['count'] <=> $x['count'] );
	$provinces = array_slice( $provinces, 0, 8 );

	$images = [];
	foreach ( $provinces as $p ) {
		$img = claudia_destination_image_id( $p['term'] );
		if ( $img && ! in_array( $img, $images, true ) ) {
			$images[] = $img;
		}
		if ( count( $images ) === 3 ) {
			break;
		}
	}

	claudia_mosaic_banner( [
		'title'   => 'Viajar por ' . $first->name,
		'eyebrow' => sprintf( '%d guías escritas sobre el terreno', $first_total ),
		'text'    => 'Rutas, pueblos, playas y dónde comer, provincia a provincia.',
		'url'     => claudia_destination_link( $first ),
		'cta'     => 'Todas las guías de ' . $first->name,
		'images'  => $images,
		'links'   => array_map( fn( $p ) => [ 'label' => $p['term']->name, 'url' => claudia_destination_link( $p['term'] ), 'count' => $p['count'] ], $provinces ),
	] );
}

claudia_rail( 'Destinos favoritos', function () use ( $top ) {
	foreach ( $top as $i => [ 'term' => $term, 'total' => $count ] ) {
		claudia_story_card( [
			'title'    => $term->name,
			'url'      => claudia_destination_link( $term ),
			'image_id' => claudia_destination_image_id( $term, 1 ),
			'eyebrow'  => sprintf( _n( '%d guía', '%d guías', $count, 'claudia' ), $count ),
			'cta'      => 'Ver destino',
		] );
	}
}, [ 'eyebrow' => 'Guías de viaje por libre', 'link' => claudia_page_url( 'europa' ), 'link_label' => 'Todos los destinos' ] );
?>

<?php
// Experiencias: etiquetas con mucho contenido, cada una con la foto de una guía reciente (sin repetir fotos).
$experiences = [
	[ 'tag' => 'playa', 'label' => 'Playas e islas' ],
	[ 'tag' => 'pueblos-con-encanto', 'label' => 'Pueblos con encanto' ],
	[ 'tag' => 'naturaleza', 'label' => 'Naturaleza y senderismo' ],
	[ 'tag' => 'gastronomia', 'label' => 'Gastronomía local' ],
	[ 'tag' => 'ruta', 'label' => 'Rutas por libre' ],
];
$used_images = [];
?>
<section class="section experiences">
	<header class="wrap section__head section__head--center">
		<p class="eyebrow">Experiencias</p>
		<h2 class="section__title">Elige tu forma de viajar</h2>
	</header>
	<div class="wrap">
		<ul class="exp-row">
			<?php
			foreach ( $experiences as $e ) :
				$tag = get_term_by( 'slug', $e['tag'], 'post_tag' );
				if ( ! $tag ) {
					continue;
				}
				$image_id = 0;
				foreach ( get_posts( [ 'tag_id' => $tag->term_id, 'numberposts' => 6, 'meta_key' => '_thumbnail_id', 'fields' => 'ids', 'no_found_rows' => true ] ) as $pid ) {
					$candidate = (int) get_post_thumbnail_id( $pid );
					if ( ! in_array( $candidate, $used_images, true ) ) {
						$image_id      = $candidate;
						$used_images[] = $candidate;
						break;
					}
				}
				?>
				<li>
					<a class="exp-card" href="<?php echo esc_url( get_term_link( $tag ) ); ?>">
						<span class="exp-card__media">
							<?php echo $image_id ? claudia_with_max_srcset( 800, fn() => wp_get_attachment_image( $image_id, 'medium_large', false, [ 'sizes' => '(min-width: 1100px) 260px, (min-width: 700px) 30vw, 62vw', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ] ) ) : ''; ?>
						</span>
						<span class="exp-card__label"><?php echo esc_html( $e['label'] ); ?></span>
						<span class="exp-card__count"><?php echo esc_html( sprintf( _n( '%d guía', '%d guías', $tag->count, 'claudia' ), $tag->count ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section id="ultimos" class="section section--sand">
	<div class="wrap">
		<header class="section__head">
			<h2 class="section__title">Últimos artículos</h2>
		</header>
		<div class="post-grid post-grid--home">
			<?php
			$latest = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 8, 'no_found_rows' => true, 'ignore_sticky_posts' => true ] );
			while ( $latest->have_posts() ) {
				$latest->the_post();
				claudia_post_card();
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>

<section id="planifica" class="section planifica">
	<div class="wrap">
		<header class="section__head">
			<div>
				<p class="eyebrow">Planifica tu viaje</p>
				<h2 class="section__title">Lo que uso antes de salir</h2>
			</div>
		</header>
		<div class="aff-grid aff-grid--flat">
			<?php foreach ( claudia_affiliates() as $a ) { claudia_affiliate_card( $a ); } ?>
		</div>
	</div>
</section>

<?php
$continents = [
	[ 'page' => 'europa', 'title' => 'Europa', 'cat' => 'italia' ],
	[ 'page' => 'viajar-a-asia', 'title' => 'Asia', 'cat' => 'japon' ],
	[ 'page' => 'viajar-a-america', 'title' => 'América', 'cat' => 'cuba' ],
	[ 'page' => 'viajar-a-africa', 'title' => 'África', 'cat' => 'marruecos' ],
];
$panels = [];
foreach ( $continents as $c ) {
	$page = get_page_by_path( $c['page'] );
	$cat  = get_term_by( 'slug', $c['cat'], 'category' );
	if ( ! $page ) {
		continue;
	}
	$panels[] = [
		'title'    => $c['title'],
		'url'      => get_permalink( $page ),
		'image_id' => $cat ? claudia_destination_image_id( $cat, 2 ) : claudia_page_hero_image_id( $page ),
		'text'     => wp_trim_words( (string) get_post_meta( $page->ID, '_yoast_wpseo_metadesc', true ), 16, '…' ),
	];
}
?>
<section class="continents-block">
	<header class="wrap continents-block__head">
		<p class="eyebrow">Viaja por continentes</p>
		<h2 class="section__title">Destinos populares</h2>
		<p>Guías por libre escritas sobre el terreno en Europa, Asia, América y África, con rutas, presupuestos y lo que de verdad merece la pena ver.</p>
		<a class="link-underline" href="<?php echo esc_url( claudia_page_url( 'europa' ) ); ?>">Ver todos los destinos <?php echo claudia_icon( 'arrow' ); ?></a>
	</header>
	<?php claudia_destination_panels( $panels ); ?>
</section>

<section class="section">
	<div class="wrap banner-pair">
		<?php
		claudia_banner( [
			'title'    => 'Encantada, soy Claudia',
			'eyebrow'  => 'Sobre mí',
			'text'     => 'Sevillana, viajera desde que tenía unos días y enamorada de Irlanda.',
			'url'      => claudia_page_url( 'sobre-mi' ),
			'image_id' => $about_id,
			'cta'      => 'Conóceme',
			'variant'  => 'half',
			'position' => '55% center',
		] );
		claudia_banner( [
			'title'    => 'Sígueme en Instagram',
			'eyebrow'  => '@lvdclaudia',
			'text'     => 'El día a día de cada viaje, en directo y sin filtros.',
			'url'      => claudia_socials()['instagram'],
			'image_id' => (int) apply_filters( 'claudia_instagram_banner_id', 32787 ),
			'cta'      => 'Ver Instagram',
			'variant'  => 'half',
			'position' => 'center 30%',
		] );
		?>
	</div>
</section>

<?php
$follow = [
	'instagram' => [ 'Instagram', 'El día a día de cada viaje' ],
	'youtube'   => [ 'YouTube', 'Vídeos de mis rutas' ],
	'facebook'  => [ 'Facebook', 'Novedades del blog' ],
	'x'         => [ 'X', 'Avisos y artículos nuevos' ],
];
?>
<section class="contact-split" aria-labelledby="follow-title">
	<div class="contact-split__media">
		<?php echo wp_get_attachment_image( (int) apply_filters( 'claudia_contact_image_id', 29138 ), 'large', false, [ 'sizes' => '(min-width: 900px) 50vw, 100vw', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => 'Claudia mirando con prismáticos desde una barca' ] ); ?>
	</div>
	<div class="contact-split__panel">
		<div class="contact-split__inner">
			<span class="contact-split__icon"><?php echo claudia_icon( 'send' ); ?></span>
			<h2 id="follow-title" class="contact-split__title">Viaja conmigo, guía a guía</h2>
			<p class="contact-split__text">Comparto rutas, pueblos y consejos de cada viaje. Léelos en el blog o sígueme en redes para no perderte el próximo destino.</p>
			<div class="contact-split__actions">
				<a class="button button--light" href="#ultimos">Leer las últimas guías <?php echo claudia_icon( 'arrow' ); ?></a>
			</div>
			<ul class="contact-split__items contact-split__items--social">
				<?php foreach ( $follow as $net => [ $name, $desc ] ) : ?>
					<li>
						<a class="contact-split__follow" href="<?php echo esc_url( claudia_socials()[ $net ] ); ?>" rel="noopener" target="_blank">
							<span class="contact-split__badge"><?php echo claudia_icon( $net ); ?></span>
							<span><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( $desc ); ?></span></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<section class="section section--tight-top">
	<div class="wrap home-intro">
		<h2 class="section__title">Un blog de viajes para organizarlo todo por tu cuenta</h2>
		<p>En Los Viajes de Claudia encontrarás <?php echo esc_html( number_format_i18n( $total ) ); ?> guías escritas a partir de mis propios viajes: qué ver en cada destino, rutas día a día, dónde dormir y comer, presupuestos y los consejos que me habría gustado conocer antes de salir.</p>
		<ul class="continents continents--left">
			<?php foreach ( claudia_menu_destinations() as $c ) : ?>
				<li><a class="pill" href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
get_footer();
