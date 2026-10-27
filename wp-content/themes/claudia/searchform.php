<?php defined( 'ABSPATH' ) || exit; $claudia_sid = wp_unique_id( 's-' ); ?>
<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $claudia_sid ); ?>">Buscar</label>
	<input id="<?php echo esc_attr( $claudia_sid ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Buscar un destino…">
	<button type="submit" aria-label="Buscar"><?php echo claudia_icon( 'search' ); ?></button>
</form>
