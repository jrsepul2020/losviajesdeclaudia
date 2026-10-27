<?php
/**
 * 404: devuelve el código 404 (lo pone WordPress) y ofrece buscador y destinos para no perder la visita.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="entry-hero entry-hero--plain error-hero">
	<div class="entry-hero__content wrap">
		<p class="eyebrow">Error 404</p>
		<h1 class="entry-title">Este camino no lleva a ninguna parte</h1>
		<p class="error-hero__lead">Puede que la guía haya cambiado de dirección o que el enlace tenga una errata. Busca el destino que te interesa:</p>
		<div class="search-big"><?php get_search_form(); ?></div>
	</div>
</header>

<div class="wrap archive-body">
	<?php get_template_part( 'template-parts/suggestions' ); ?>
</div>
<?php
get_footer();
