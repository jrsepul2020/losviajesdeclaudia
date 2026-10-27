<?php
/**
 * Analítica y monetización, con la misma configuración que tenía la web anterior:
 * - Google Tag Manager GTM-MPG35LN con el dataLayer de GTM4WP (tipo, categoría y autor). No se carga a administradores.
 * - AdSense: 3 anuncios en entradas (tras el párrafo 3, tras el 8 y al final), como en Adsensei.
 * - Stay22 (Let Me Allez) para monetizar enlaces de alojamiento.
 * AdSense y Stay22 se cargan en la primera interacción del usuario, igual que hacía WP Rocket.
 */

defined( 'ABSPATH' ) || exit;

function claudia_monetization_config(): array {
	return apply_filters( 'claudia_monetization', [
		'gtm'       => 'GTM-MPG35LN',
		'adsense'   => 'ca-pub-1305382296668483',
		'ad_slot'   => '7273689976',
		'ad_after'  => [ 3, 8 ],   // párrafos tras los que va un anuncio; además, uno al final
		'ad_end'    => true,
		'stay22'    => '68dc3f92adade822823d7dbe',
	] );
}

function claudia_tracking_enabled(): bool {
	return ! current_user_can( 'manage_options' ) && ! is_preview();
}

/* ---------- Google Tag Manager ---------- */
add_action( 'wp_head', function () {
	$c = claudia_monetization_config();
	if ( empty( $c['gtm'] ) || ! claudia_tracking_enabled() ) {
		return;
	}
	$data = [];
	if ( is_singular() ) {
		$post               = get_queried_object();
		$data['pagePostType']  = get_post_type( $post );
		$data['pagePostType2'] = 'single-' . get_post_type( $post );
		$data['pagePostAuthor'] = get_the_author_meta( 'display_name', $post->post_author );
		if ( is_singular( 'post' ) ) {
			$data['pageCategory'] = wp_list_pluck( get_the_category( $post->ID ), 'slug' );
		}
	} elseif ( is_category() || is_tag() ) {
		$data['pagePostType']  = 'post';
		$data['pagePostType2'] = is_category() ? 'category-post' : 'tag-post';
	} elseif ( is_front_page() ) {
		$data['pagePostType'] = 'frontpage';
	}
	?>
<script>
window.dataLayer=window.dataLayer||[];dataLayer.push(<?php echo wp_json_encode( $data ); ?>);
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s);j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',<?php echo wp_json_encode( $c['gtm'] ); ?>);
</script>
	<?php
}, 1 );

add_action( 'wp_body_open', function () {
	$c = claudia_monetization_config();
	if ( ! empty( $c['gtm'] ) && claudia_tracking_enabled() ) {
		printf( '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>', esc_attr( $c['gtm'] ) );
	}
} );

/* ---------- AdSense dentro de las entradas ---------- */
function claudia_ad_unit(): string {
	$c = claudia_monetization_config();
	return sprintf(
		'<div class="ad-slot" aria-hidden="true"><ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" data-ad-format="auto" data-full-width-responsive="true"></ins></div>',
		esc_attr( $c['adsense'] ),
		esc_attr( $c['ad_slot'] )
	);
}

add_filter( 'the_content', function ( string $html ): string {
	$c = claudia_monetization_config();
	if ( empty( $c['adsense'] ) || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || is_feed() ) {
		return $html;
	}
	$after = array_flip( $c['ad_after'] );
	$count = 0;
	$html  = preg_replace_callback( '#</p>#i', function ( $m ) use ( &$count, $after ) {
		$count++;
		return isset( $after[ $count ] ) ? '</p>' . claudia_ad_unit() : '</p>';
	}, $html );
	return $c['ad_end'] ? $html . claudia_ad_unit() : $html;
}, 25 ); // después del índice (20), para no contar ni partir su HTML

/* ---------- Carga diferida de AdSense y Stay22 ---------- */
add_action( 'wp_footer', function () {
	$c = claudia_monetization_config();
	if ( ! claudia_tracking_enabled() ) {
		return;
	}
	$scripts = [];
	if ( ! empty( $c['adsense'] ) && is_singular( 'post' ) ) {
		$scripts[] = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( $c['adsense'] );
	}
	if ( ! empty( $c['stay22'] ) ) {
		$scripts[] = 'https://scripts.stay22.com/letmeallez.js';
	}
	if ( ! $scripts ) {
		return;
	}
	?>
<script>
(function(){
	var done=false, ev=['scroll','pointerdown','keydown','touchstart','mousemove'];
	window.Stay22=window.Stay22||{};Stay22.params={lmaID:<?php echo wp_json_encode( $c['stay22'] ); ?>};
	function load(){
		if(done)return;done=true;ev.forEach(function(e){removeEventListener(e,load,{passive:true});});
		<?php echo wp_json_encode( $scripts ); ?>.forEach(function(src){var s=document.createElement('script');s.async=true;s.src=src;if(src.indexOf('googlesyndication')>-1)s.crossOrigin='anonymous';document.head.appendChild(s);});
		document.querySelectorAll('ins.adsbygoogle').forEach(function(){(window.adsbygoogle=window.adsbygoogle||[]).push({});});
	}
	ev.forEach(function(e){addEventListener(e,load,{passive:true});});
})();
</script>
	<?php
}, 50 );
