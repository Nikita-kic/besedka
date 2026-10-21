<?php
/**
 * Шаблон подробной карточки товара (страница отдельного товара WooCommerce).
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	global $product;
	if ( ! is_a( $product, 'WC_Product' ) && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! $product ) {
		continue;
	}
	?>

	<section class="product-page">
		<div class="container">

			<!-- Хлебные крошки -->
			<?php besedka_render_breadcrumbs(); ?>

			<!-- Подробная карточка: галерея слева, информация и покупка справа -->
			<div class="product-page__grid">
				<?php besedka_render_product_details( $product ); ?>
			</div>

		</div>
	</section>

	<?php
	// Похожие товары.
	if ( function_exists( 'wc_get_related_products' ) ) {
		$related_ids = wc_get_related_products( $product->get_id(), 8 );
		if ( ! empty( $related_ids ) ) {
			?>
			<section class="section">
				<div class="container">
					<div class="section__head">
						<h2 class="section__title"><?php esc_html_e( 'Похожие товары', 'besedka' ); ?></h2>
					</div>
					<div class="section__scroll">
						<?php foreach ( $related_ids as $related_id ) : ?>
							<?php besedka_render_product_card( $related_id ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php
		}
	}

endwhile;

get_footer();
