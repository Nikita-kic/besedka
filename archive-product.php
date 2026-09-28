<?php
/**
 * Архив категории товаров: /product-category/{категория}/ и общий архив /shop/.
 * Та же панель фильтров и сетка карточек, что и на главной, но в рамках
 * текущей категории/тега.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$queried_object   = get_queried_object();
$current_category = '';
$archive_title    = __( 'Каталог товаров', 'besedka' );
$archive_desc     = '';

if ( $queried_object instanceof WP_Term ) {
	$current_category = $queried_object->slug;
	$archive_title     = $queried_object->name;
	$archive_desc      = term_description( $queried_object );
} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
	$archive_title = function_exists( 'woocommerce_page_title' ) ? get_the_title( wc_get_page_id( 'shop' ) ) : __( 'Магазин', 'besedka' );
}

$args = array();
if ( $current_category && empty( $_GET['category'] ) ) {
	$args['category'] = array( $current_category );
}

$product_list = besedka_get_filtered_products( $args );
$products     = ! empty( $product_list->products ) ? $product_list->products : array();
$total_found  = ! empty( $product_list->total ) ? $product_list->total : count( $products );
?>

<!-- ==========================================================================
     АРХИВ КАТЕГОРИИ ТОВАРОВ
     ========================================================================== -->
<section class="catalog">
	<div class="container">

		<nav class="product-page__breadcrumbs" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'besedka' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Главная', 'besedka' ); ?></a>
			<span>/</span>
			<span><?php echo esc_html( $archive_title ); ?></span>
		</nav>

		<!-- Категории: отдельный блок с иконками над фильтрами -->
		<?php besedka_render_category_selector(); ?>

		<!-- Панель фильтров: закрепляется при скролле (см. script.js) -->
		<?php besedka_render_filters_panel( '', $current_category ); ?>

		<div class="products__header">
			<h1 class="products__title"><?php echo esc_html( $archive_title ); ?></h1>
			<span class="products__count"><?php echo esc_html( sprintf( _n( '%d товар', '%d товаров', $total_found, 'besedka' ), $total_found ) ); ?></span>
		</div>

		<?php if ( $archive_desc ) : ?>
			<div class="seo-text" style="margin-bottom:24px;"><?php echo wp_kses_post( $archive_desc ); ?></div>
		<?php endif; ?>

		<div class="products__grid">
			<?php if ( ! empty( $products ) ) : ?>
				<?php foreach ( $products as $product ) : ?>
					<?php besedka_render_product_card( $product ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="products__empty"><?php esc_html_e( 'В этой категории пока нет товаров. Загляните позже или посмотрите другие разделы каталога.', 'besedka' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $product_list->max_num_pages ) && $product_list->max_num_pages > 1 ) : ?>
			<nav class="products__pagination" aria-label="<?php esc_attr_e( 'Страницы каталога', 'besedka' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'total'   => $product_list->max_num_pages,
							'current' => max( 1, get_query_var( 'paged' ) ),
							'type'    => 'list',
						)
					)
				);
				?>
			</nav>
		<?php endif; ?>

	</div>
</section>

<!-- Контейнер модального окна карточки товара -->
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
