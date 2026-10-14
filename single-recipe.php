<?php
/**
 * Страница рецепта.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$recipe_id  = get_the_ID();
	$duration   = besedka_recipe_field( 'duration', $recipe_id );
	$row_meta   = besedka_recipe_row_meta( $recipe_id );
	?>
	<article class="recipe-single">
		<div class="container">
			<div class="recipe-single__inner">
				<nav class="product-page__breadcrumbs" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'besedka' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Главная', 'besedka' ); ?></a>
					<span>/</span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'recipe' ) ); ?>"><?php esc_html_e( 'Рецепты', 'besedka' ); ?></a>
				</nav>

				<h1 class="recipe-single__title"><?php the_title(); ?></h1>

				<?php if ( $duration || $row_meta ) : ?>
					<p class="recipe-featured__meta">
						<?php if ( $duration ) : ?>
							<span class="recipe-featured__meta-item"><?php echo besedka_recipe_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $duration ); ?></span>
						<?php endif; ?>
						<?php if ( $row_meta ) : ?>
							<span class="recipe-featured__meta-item"><?php echo besedka_recipe_icon( 'bars' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $row_meta ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="recipe-single__media"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>

				<div class="recipe-single__content"><?php the_content(); ?></div>

				<?php besedka_render_recipe_kit( $recipe_id, false ); ?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
