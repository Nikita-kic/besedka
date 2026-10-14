<?php
/**
 * Архив рецептов (/recipes/).
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="recipes-archive">
	<div class="container">
		<h1 class="recipes-archive__title"><?php esc_html_e( 'Рецепты и инструкции', 'besedka' ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<ul class="recipes__list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="recipes__item"><?php besedka_render_recipe_row( get_the_ID() ); ?></li>
				<?php endwhile; ?>
			</ul>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Рецептов пока нет.', 'besedka' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
