<?php
/**
 * Блок главной «Рецепты и инструкции — Как сделать пошагово».
 * Выводится, только если опубликовано не меньше 4 рецептов.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_recipes = besedka_get_home_recipes();
if ( empty( $home_recipes ) ) {
	return;
}

$featured_id = $home_recipes['featured'];
$duration    = besedka_recipe_field( 'duration', $featured_id );
$difficulty  = besedka_recipe_field( 'difficulty', $featured_id );
$steps       = (int) besedka_recipe_field( 'steps_count', $featured_id );
$is_beginner = besedka_recipe_field( 'is_beginner', $featured_id );
$archive_url = get_post_type_archive_link( 'recipe' );
?>
<section class="section recipes">
	<div class="container">

		<div class="recipes__head">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Рецепты и инструкции', 'besedka' ); ?></p>
				<h2 class="section__title recipes__title"><?php esc_html_e( 'Как сделать — пошагово', 'besedka' ); ?></h2>
			</div>
			<a class="section__link-all recipes__all" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Все рецепты', 'besedka' ); ?> &rarr;</a>
		</div>

		<div class="recipes__grid">

			<article class="recipe-featured">
				<a class="recipe-featured__media" href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php
					if ( has_post_thumbnail( $featured_id ) ) {
						echo get_the_post_thumbnail( $featured_id, 'large', array( 'class' => 'recipe-featured__image', 'loading' => 'lazy', 'alt' => get_the_title( $featured_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
					<?php if ( $is_beginner ) : ?>
						<span class="recipe-featured__badge"><?php esc_html_e( 'Для новичка', 'besedka' ); ?></span>
					<?php endif; ?>
				</a>

				<div class="recipe-featured__meta">
					<?php if ( $duration ) : ?>
						<span class="recipe-featured__meta-item"><?php echo besedka_recipe_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $duration ); ?></span>
					<?php endif; ?>
					<?php if ( $difficulty ) : ?>
						<span class="recipe-featured__meta-item"><?php echo besedka_recipe_icon( 'bars' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( sprintf( __( 'Сложность: %s', 'besedka' ), mb_strtolower( $difficulty ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( $steps ) : ?>
						<span class="recipe-featured__meta-item"><?php echo besedka_recipe_icon( 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $steps . ' ' . besedka_plural( $steps, array( 'шаг', 'шага', 'шагов' ) ) ); ?></span>
					<?php endif; ?>
				</div>

				<h3 class="recipe-featured__title"><a href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>"><?php echo esc_html( get_the_title( $featured_id ) ); ?></a></h3>

				<?php if ( has_excerpt( $featured_id ) ) : ?>
					<p class="recipe-featured__excerpt"><?php echo esc_html( get_the_excerpt( $featured_id ) ); ?></p>
				<?php endif; ?>

				<?php besedka_render_recipe_kit( $featured_id ); ?>
			</article>

			<ul class="recipes__list">
				<?php foreach ( $home_recipes['others'] as $recipe_id ) : ?>
					<li class="recipes__item"><?php besedka_render_recipe_row( $recipe_id ); ?></li>
				<?php endforeach; ?>
			</ul>

		</div>

		<a class="section__link-all recipes__more" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Все рецепты', 'besedka' ); ?> &rarr;</a>

	</div>
</section>
