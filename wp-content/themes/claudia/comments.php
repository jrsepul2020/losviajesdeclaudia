<?php
/**
 * Comentarios: los más de 9.000 comentarios existentes se mantienen visibles (contenido útil para SEO).
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="section__title"><?php printf( _n( '%s comentario', '%s comentarios', get_comments_number(), 'claudia' ), number_format_i18n( get_comments_number() ) ); ?></h2>
		<ol class="comment-list">
			<?php wp_list_comments( [ 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 0 ] ); ?>
		</ol>
		<?php the_comments_pagination( [ 'prev_text' => '‹', 'next_text' => '›' ] ); ?>
	<?php endif; ?>

	<?php
	comment_form( [
		'title_reply'        => '¿Tienes alguna duda? Pregúntame',
		'title_reply_before' => '<h2 id="reply-title" class="section__title">',
		'title_reply_after'  => '</h2>',
		'label_submit'       => 'Enviar comentario',
		'class_submit'       => 'button',
	] );
	?>
</section>
