<?php
/**
 * Главная страница: каталог с фильтрами, сетка товаров и дополнительные блоки.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$product_list = besedka_get_filtered_products();
$products     = ! empty( $product_list->products ) ? $product_list->products : array();
$total_found  = ! empty( $product_list->total ) ? $product_list->total : count( $products );

// Без поиска блок показывает подборку «Популярные товары» (если она задана).
$popular_fixed = empty( $_GET['search'] ) ? besedka_get_popular_products() : array();
if ( ! empty( $popular_fixed ) ) {
	$products    = $popular_fixed;
	$total_found = count( $products );
}
?>

<?php if ( empty( $_GET['search'] ) ) : ?>
<!-- ==========================================================================
     БАННЕРЫ: АКЦИЯ С ТАЙМЕРОМ (СЛЕВА) И ДУБОВЫЕ КУБИКИ (СПРАВА)
     ========================================================================== -->
<section class="banners">
	<div class="container">
		<div class="banners__grid">

			<div class="banner banner--promo">
				<a class="banner__link" href="<?php echo esc_url( besedka_category_url( 'drozhzhi' ) ); ?>" aria-label="<?php esc_attr_e( 'Скидка 10% на все турбо-дрожжи', 'besedka' ); ?>">
					<img class="banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/img/banners/opening.jpg' ); ?>" alt="<?php esc_attr_e( '−10% на все турбо-дрожжи в честь открытия интернет-магазина', 'besedka' ); ?>" width="2000" height="667">
				</a>
				<div class="banner__timer countdown" data-countdown="<?php echo esc_attr( besedka_get_promo_end() ); ?>">
					<div class="countdown__row">
						<div class="countdown__cell"><span class="countdown__value" data-countdown-days>00</span><span class="countdown__label"><?php esc_html_e( 'дней', 'besedka' ); ?></span></div>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-hours>00</span><span class="countdown__label"><?php esc_html_e( 'часов', 'besedka' ); ?></span></div>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-minutes>00</span><span class="countdown__label"><?php esc_html_e( 'минут', 'besedka' ); ?></span></div>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-seconds>00</span><span class="countdown__label"><?php esc_html_e( 'секунд', 'besedka' ); ?></span></div>
					</div>
					<p class="countdown__title"><?php esc_html_e( 'До конца акции', 'besedka' ); ?></p>
				</div>
			</div>

			<div class="banner banner--cubes">
				<a class="banner__link" href="<?php echo esc_url( besedka_category_url( 'kubiki' ) ); ?>" aria-label="<?php esc_attr_e( 'Дубовые кубики для выдержки', 'besedka' ); ?>">
					<img class="banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/img/banners/cubes.jpg' ); ?>" alt="<?php esc_attr_e( 'Дубовые кубики для выдержки', 'besedka' ); ?>" width="1324" height="737">
				</a>
			</div>

		</div>
	</div>
</section>
<?php endif; ?>

<!-- ==========================================================================
     КАТАЛОГ ТОВАРОВ С ФИЛЬТРАМИ
     ========================================================================== -->
<section class="catalog">
	<div class="container">

		<!-- Категории: отдельный блок с иконками над фильтрами -->
		<?php besedka_render_category_selector(); ?>

		<?php /* Панель фильтров скрыта (функция besedka_render_filters_panel() сохранена): besedka_render_filters_panel( home_url( '/' ) ); */ ?>

		<!-- Сетка товаров -->
		<div class="products__grid">
			<div class="products__intro" style="--intro-rows: <?php echo esc_attr( max( 1, (int) ceil( count( $products ) / 3 ) ) ); ?>">
				<?php $products_title_tag = empty( $_GET['search'] ) ? 'h2' : 'h1'; // На главной H1 — заголовок SEO-блока, на странице поиска — заголовок результатов. ?>
				<<?php echo esc_attr( $products_title_tag ); ?> class="products__title<?php echo empty( $_GET['search'] ) ? ' products__title--nowrap' : ''; ?>">
				<?php
				if ( ! empty( $_GET['search'] ) ) {
					echo esc_html( sprintf( __( 'Результаты поиска: «%s»', 'besedka' ), sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) );
				} else {
					esc_html_e( 'Популярные товары', 'besedka' );
				}
				?>
			</<?php echo esc_attr( $products_title_tag ); ?>>
				<span class="products__count"><?php echo esc_html( sprintf( _n( '%d товар', '%d товаров', $total_found, 'besedka' ), $total_found ) ); ?></span>
			</div>
			<?php if ( ! empty( $products ) ) : ?>
				<?php foreach ( $products as $product ) : ?>
					<?php besedka_render_product_card( $product ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="products__empty"><?php esc_html_e( 'Товары не найдены. Попробуйте изменить запрос или параметры фильтров.', 'besedka' ); ?></p>
			<?php endif; ?>
		</div>

	</div>
</section>

<?php if ( function_exists( 'wc_get_products' ) ) : ?>

	<!-- ==========================================================================
	     ГОТОВЫЕ НАБОРЫ
	     ========================================================================== -->
	<?php besedka_render_sets_section(); ?>

	<!-- ==========================================================================
	     РЕЦЕПТЫ
	     ========================================================================== -->
	<?php get_template_part( 'template-parts/home/recipes' ); ?>

	<!-- ==========================================================================
	     НОВИНКИ
	     ========================================================================== -->
	<?php
	$new_products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 8,
			'orderby' => 'date',
			'order'   => 'DESC',
			'exclude' => array_map( function ( $p ) { return $p->get_id(); }, besedka_get_sets() ),
		)
	);
	if ( ! empty( $new_products ) ) :
		?>
		<section class="section">
			<div class="container">
					<div class="section__scroll">
						<div class="section__intro">
							<h2 class="section__title"><?php esc_html_e( 'Новинки', 'besedka' ); ?></h2>
							<p class="section__subtitle"><?php esc_html_e( 'Свежие поступления в нашем каталоге', 'besedka' ); ?></p>
							<a class="section__link-all" href="<?php echo esc_url( besedka_catalog_url() ); ?>"><?php esc_html_e( 'Смотреть все', 'besedka' ); ?></a>
						</div>
					<?php foreach ( $new_products as $product ) : ?>
						<?php besedka_render_product_card( $product ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

<?php endif; ?>


<!-- ==========================================================================
     ПОЧЕМУ БЕСЕДКА: три белые карточки на границе белого и тёплого фона
     ========================================================================== -->
<section class="why">
	<div class="container">
		<div class="why__head">
			<h2 class="why__title"><?php esc_html_e( 'Почему Беседка', 'besedka' ); ?></h2>
			<p class="why__subtitle"><?php esc_html_e( 'Делаем сами, доставляем по всей стране и даём выбрать, как платить.', 'besedka' ); ?></p>
		</div>

		<div class="why__stage">
			<ul class="why__cards">
				<li class="why__card">
					<span class="why__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20V9l5 3V9l5 3V6h3l1 6h3v8z"/></svg></span>
					<h3 class="why__card-title"><?php esc_html_e( 'Собственное производство', 'besedka' ); ?></h3>
					<p class="why__card-text"><?php esc_html_e( 'Производим более 200 позиций: спиртовые дрожжи, эссенции, ВАД и составы. Сами отвечаем за сырьё и рецептуру, поэтому качество одинаковое от партии к партии.', 'besedka' ); ?></p>
				</li>
				<li class="why__card">
					<span class="why__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/></svg></span>
					<h3 class="why__card-title"><?php esc_html_e( 'Доставка по всей России и СНГ', 'besedka' ); ?></h3>
					<p class="why__card-text"><?php esc_html_e( 'Доставляем в 83 региона России и страны СНГ Почтой России. Каждую посылку отправляем с трек-номером, чтобы заказ можно было отследить.', 'besedka' ); ?></p>
				</li>
				<li class="why__card">
					<span class="why__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10.5h18M7 15h4"/></svg></span>
					<h3 class="why__card-title"><?php esc_html_e( 'Оплата как удобно', 'besedka' ); ?></h3>
					<p class="why__card-text"><?php esc_html_e( 'Картой онлайн на сайте, при получении на почте или частями. Способ оплаты выбираете при оформлении заказа.', 'besedka' ); ?></p>
				</li>
			</ul>
		</div>
	</div>
</section>

<!-- ==========================================================================
     SEO-ТЕКСТ
     ========================================================================== -->
<section class="section">
	<div class="container">
		<div class="seo-text">
			<?php echo wp_kses_post( wpautop( get_theme_mod( 'besedka_seo_text', sprintf(
				'<%4$s>%1$s</%4$s><p>%2$s</p><p>%3$s</p>',
				__( 'Товары для самогоноварения с доставкой по России и СНГ', 'besedka' ),
				__( 'Мы производим и поставляем более 700 позиций для самогоноварения: спиртовые дрожжи, дубовые кубики и чипсы, эссенции, бонификаторы, уголь для очистки и комплектующие для дистилляции. К каждому товару — подробная инструкция по применению.', 'besedka' ),
				__( 'Собственное производство гарантирует стабильное качество сырья и рецептуры в каждой партии. Оплата картой онлайн, при получении или частями — выбирайте удобный способ, доставляем в 83 региона России и страны СНГ.', 'besedka' ),
				empty( $_GET['search'] ) ? 'h1' : 'h2' // Один H1 на странице: на главной это заголовок SEO-блока.
			) ) ) ); ?>
		</div>
	</div>
</section>

<!-- Контейнер модального окна карточки товара (заполняется через script.js) -->
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
