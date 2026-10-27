<?php
/**
 * Pie de página: tarjeta grande con bordes redondeados y foto de fondo oscurecida.
 * Arriba, llamada a la acción con buscador y redes; abajo, columnas de navegación.
 */

defined( 'ABSPATH' ) || exit;

$footer_bg = (int) apply_filters( 'claudia_footer_image_id', 31513 ); // globos al amanecer en Capadocia (foto de Claudia, 2018)
?>
</main>

<footer class="site-footer">
	<?php
	if ( $footer_bg ) {
		echo wp_get_attachment_image( $footer_bg, 'full', false, [ 'class' => 'site-footer__bg', 'sizes' => '100vw', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ] );
	}
	?>

	<div class="site-footer__cta">
		<p class="site-footer__eyebrow">¿Preparamos tu próximo viaje?</p>
		<p class="site-footer__headline">El mundo se recorre mejor por libre</p>
		<form class="hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="footer-s">Busca tu destino</label>
			<input id="footer-s" type="search" name="s" placeholder="Busca tu próximo destino…" autocomplete="off" enterkeyhint="search">
			<button type="submit"><?php echo claudia_icon( 'search' ); ?><span>Buscar</span></button>
		</form>
		<ul class="site-footer__socials">
			<?php foreach ( claudia_socials() as $net => $url ) : ?>
				<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php echo claudia_icon( $net ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="wrap">
		<div class="site-footer__grid">
			<div class="site-footer__brand">
				<img class="site-footer__logo" src="<?php echo esc_url( CLAUDIA_URI . '/assets/img/logo.png' ); ?>" width="653" height="120" alt="<?php bloginfo( 'name' ); ?>" loading="lazy">
				<p>Guías, rutas y consejos para viajar por libre, escritos desde la experiencia. Desde Sevilla al resto del mundo.</p>
			</div>

			<div>
				<h2 class="site-footer__title">Destinos</h2>
				<ul class="menu menu--footer">
					<?php foreach ( claudia_menu_destinations() as $c ) : ?>
						<li><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div>
				<h2 class="site-footer__title">Planifica tu viaje</h2>
				<ul class="menu menu--footer">
					<?php foreach ( claudia_affiliates() as $a ) : ?>
						<li><a href="<?php echo esc_url( $a['url'] ); ?>" rel="sponsored nofollow noopener" target="_blank"><?php echo esc_html( $a['tag'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div>
				<h2 class="site-footer__title">Información</h2>
				<?php claudia_menu( 'footer', 'menu menu--footer' ); ?>
			</div>
		</div>

		<p class="site-footer__copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Los Viajes de Claudia · Algunos enlaces son de afiliado: si reservas a través de ellos, el blog recibe una pequeña comisión sin que a ti te cueste más.</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
