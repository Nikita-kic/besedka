<?php
/**
 * Шаблон обычной страницы (в том числе «Корзина» и «Оформление заказа» WooCommerce).
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page">
		<div class="container">
			<h1 class="page__title"><?php the_title(); ?></h1>
			<div class="page__content"><?php the_content(); ?></div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
