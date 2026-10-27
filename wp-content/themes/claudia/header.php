<?php
/**
 * Cabecera: logo centrado y menú repartido a ambos lados. Sobre las fotos de portada y entradas
 * se vuelve transparente y blanca.
 */

defined( 'ABSPATH' ) || exit;

// La portada usa una cabecera blanca con la foto en tarjeta debajo; entradas, hubs y categorías van sobre la foto.
$overlay = ( is_singular( 'post' ) && has_post_thumbnail() )
	|| ( is_page() && ! is_front_page() && claudia_page_hero_image_id( get_queried_object_id() ) )
	|| ( is_category() && claudia_destination_image_id( get_queried_object() ) );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1f6b34">
<?php if ( ! has_site_icon() ) : ?>
<link rel="icon" href="<?php echo esc_url( CLAUDIA_URI . '/assets/img/favicon-64.png' ); ?>" sizes="64x64">
<link rel="apple-touch-icon" href="<?php echo esc_url( CLAUDIA_URI . '/assets/img/apple-touch-icon.png' ); ?>">
<?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class( $overlay ? 'has-overlay-header' : '' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="site-header<?php echo $overlay ? ' site-header--overlay' : ''; ?>">
	<div class="site-header__inner">
		<nav class="nav nav--left" aria-label="Principal">
			<?php claudia_menu( 'primary-left' ); ?>
		</nav>

		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php echo esc_url( CLAUDIA_URI . '/assets/img/logo.png' ); ?>" width="653" height="120" alt="<?php bloginfo( 'name' ); ?>">
		</a>

		<div class="nav nav--right">
			<?php claudia_menu( 'primary-right' ); ?>
			<ul class="socials">
				<?php foreach ( array_slice( claudia_socials(), 0, 2, true ) as $net => $url ) : ?>
					<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php echo claudia_icon( $net ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<details class="search-toggle">
				<summary aria-label="Buscar"><?php echo claudia_icon( 'search' ); ?></summary>
				<div class="search-toggle__panel"><?php get_search_form(); ?></div>
			</details>
		</div>

		<details class="mobile-nav">
			<summary aria-label="Abrir menú"><?php echo claudia_icon( 'menu' ); ?></summary>
			<div class="mobile-nav__panel">
				<?php get_search_form(); ?>
				<?php claudia_menu( 'primary-left', 'menu menu--mobile' ); ?>
				<?php claudia_menu( 'primary-right', 'menu menu--mobile' ); ?>
			</div>
		</details>
	</div>
</header>

<main id="contenido" class="site-main">
