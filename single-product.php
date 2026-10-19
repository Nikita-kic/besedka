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

// Модальное окно для быстрого просмотра похожих товаров (см. index.php).
?>
<div class="modal" data-product-modal aria-hidden="true">
	<div class="modal__overlay" data-modal-close></div>
	<div class="modal__window" role="dialog" aria-modal="true">
		<button type="button" class="modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Закрыть', 'besedka' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
		<div class="modal__loading" data-modal-content><?php esc_html_e( 'Загрузка…', 'besedka' ); ?></div>
	</div>
</div>

<?php
get_footer();
