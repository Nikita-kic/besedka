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
?>

<?php if ( empty( $_GET['search'] ) ) : ?>
<!-- ==========================================================================
     БАННЕРЫ: АКЦИЯ С ТАЙМЕРОМ (СЛЕВА) И ДУБОВЫЕ КУБИКИ (СПРАВА)
     ========================================================================== -->
<section class="banners">
	<div class="container">
		<div class="banners__grid">

			<div class="banner banner--promo">
				<a class="banner__link" href="<?php echo esc_url( home_url( '/product-category/drozhzhi/' ) ); ?>" aria-label="<?php esc_attr_e( 'Скидка 10% на все спиртовые дрожжи', 'besedka' ); ?>">
					<img class="banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/img/banners/opening.jpg' ); ?>" alt="<?php esc_attr_e( 'Мы открылись! Дарим скидку 10% на все спиртовые дрожжи', 'besedka' ); ?>" width="2000" height="667">
				</a>
				<div class="banner__timer countdown" data-countdown="<?php echo esc_attr( besedka_get_promo_end() ); ?>">
					<p class="countdown__title"><?php esc_html_e( 'До конца акции', 'besedka' ); ?></p>
					<div class="countdown__row">
						<div class="countdown__cell"><span class="countdown__value" data-countdown-days>00</span><span class="countdown__label"><?php esc_html_e( 'дней', 'besedka' ); ?></span></div>
						<span class="countdown__sep" aria-hidden="true"></span>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-hours>00</span><span class="countdown__label"><?php esc_html_e( 'часов', 'besedka' ); ?></span></div>
						<span class="countdown__sep" aria-hidden="true"></span>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-minutes>00</span><span class="countdown__label"><?php esc_html_e( 'минут', 'besedka' ); ?></span></div>
						<span class="countdown__sep" aria-hidden="true"></span>
						<div class="countdown__cell"><span class="countdown__value" data-countdown-seconds>00</span><span class="countdown__label"><?php esc_html_e( 'секунд', 'besedka' ); ?></span></div>
					</div>
				</div>
			</div>

			<div class="banner banner--cubes">
				<a class="banner__link" href="<?php echo esc_url( home_url( '/product-category/kubiki/' ) ); ?>" aria-label="<?php esc_attr_e( 'Дубовые кубики для выдержки', 'besedka' ); ?>">
					<img class="banner__image" src="<?php echo esc_url( get_template_directory_uri() . '/img/banners/cubes.jpg' ); ?>" alt="<?php esc_attr_e( 'Дубовые кубики для выдержки', 'besedka' ); ?>" width="1681" height="936">
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

		<!-- Панель фильтров: закрепляется при скролле (см. script.js) -->
		<?php besedka_render_filters_panel( home_url( '/' ) ); ?>

		<!-- Сетка товаров -->
		<div class="products__header">
			<h1 class="products__title">
				<?php
				if ( ! empty( $_GET['search'] ) ) {
					echo esc_html( sprintf( __( 'Результаты поиска: «%s»', 'besedka' ), sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) );
				} else {
					esc_html_e( 'Каталог товаров', 'besedka' );
				}
				?>
			</h1>
			<span class="products__count"><?php echo esc_html( sprintf( _n( '%d товар', '%d товаров', $total_found, 'besedka' ), $total_found ) ); ?></span>
		</div>

		<div class="products__grid">
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
	     ПОПУЛЯРНЫЕ ТОВАРЫ
	     ========================================================================== -->
	<?php
	$popular = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 8,
			'orderby' => 'popularity',
		)
	);
	if ( ! empty( $popular ) ) :
		?>
		<section class="section">
			<div class="container">
				<div class="section__head">
					<div>
						<h2 class="section__title"><?php esc_html_e( 'Популярные товары', 'besedka' ); ?></h2>
						<p class="section__subtitle"><?php esc_html_e( 'Товары, которые чаще всего заказывают наши клиенты', 'besedka' ); ?></p>
					</div>
					<a class="section__link-all" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Смотреть все', 'besedka' ); ?></a>
				</div>
				<div class="section__scroll">
					<?php foreach ( $popular as $product ) : ?>
						<?php besedka_render_product_card( $product ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- ==========================================================================
	     ТОВАРЫ СО СКИДКОЙ
	     ========================================================================== -->
	<?php
	$sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
	if ( ! empty( $sale_ids ) ) :
		$sale_products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 8,
				'include' => $sale_ids,
			)
		);
		?>
		<section class="section section--soft">
			<div class="container">
				<div class="section__head">
					<div>
						<h2 class="section__title"><?php esc_html_e( 'Товары со скидкой', 'besedka' ); ?></h2>
						<p class="section__subtitle"><?php esc_html_e( 'Выгодные предложения ограниченное время', 'besedka' ); ?></p>
					</div>
					<a class="section__link-all" href="<?php echo esc_url( home_url( '/sale/' ) ); ?>"><?php esc_html_e( 'Смотреть все', 'besedka' ); ?></a>
				</div>
				<div class="section__scroll">
					<?php foreach ( $sale_products as $product ) : ?>
						<?php besedka_render_product_card( $product ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

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
		)
	);
	if ( ! empty( $new_products ) ) :
		?>
		<section class="section">
			<div class="container">
				<div class="section__head">
					<div>
						<h2 class="section__title"><?php esc_html_e( 'Новинки', 'besedka' ); ?></h2>
						<p class="section__subtitle"><?php esc_html_e( 'Свежие поступления в нашем каталоге', 'besedka' ); ?></p>
					</div>
					<a class="section__link-all" href="<?php echo esc_url( home_url( '/new/' ) ); ?>"><?php esc_html_e( 'Смотреть все', 'besedka' ); ?></a>
				</div>
				<div class="section__scroll">
					<?php foreach ( $new_products as $product ) : ?>
						<?php besedka_render_product_card( $product ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

<?php endif; ?>


<!-- ==========================================================================
     ПОЧЕМУ ВЫБИРАЮТ НАС
     ========================================================================== -->
<section class="section">
	<div class="container">
		<div class="section__head">
			<h2 class="section__title"><?php esc_html_e( 'Почему выбирают нас', 'besedka' ); ?></h2>
		</div>
		<div class="advantages__grid">
			<div class="advantages__item">
				<span class="advantages__icon"><svg viewBox="0 0 24 24"><path d="M12 2l2.5 6.5L21 11l-6.5 2.5L12 20l-2.5-6.5L3 11l6.5-2.5L12 2z"/></svg></span>
				<h3 class="advantages__title"><?php esc_html_e( 'Собственное производство', 'besedka' ); ?></h3>
				<p class="advantages__text"><?php esc_html_e( 'Производим более 700 позиций: дрожжи, эссенции, ВАД и составы', 'besedka' ); ?></p>
			</div>
			<div class="advantages__item">
				<span class="advantages__icon"><svg viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg></span>
				<h3 class="advantages__title"><?php esc_html_e( 'Доставка по всей России и СНГ', 'besedka' ); ?></h3>
				<p class="advantages__text"><?php esc_html_e( 'Поставляем товары в 83 региона России и страны СНГ', 'besedka' ); ?></p>
			</div>
			<div class="advantages__item">
				<span class="advantages__icon"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM4 7l8-4 8 4"/></svg></span>
				<h3 class="advantages__title"><?php esc_html_e( 'Рецепты и инструкции', 'besedka' ); ?></h3>
				<p class="advantages__text"><?php esc_html_e( 'К каждому товару — подробная инструкция по применению', 'besedka' ); ?></p>
			</div>
			<div class="advantages__item">
				<span class="advantages__icon"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
				<h3 class="advantages__title"><?php esc_html_e( 'Удобная оплата', 'besedka' ); ?></h3>
				<p class="advantages__text"><?php esc_html_e( 'Картой онлайн, при получении или частями', 'besedka' ); ?></p>
			</div>
			<div class="advantages__item">
				<span class="advantages__icon"><svg viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg></span>
				<h3 class="advantages__title"><?php esc_html_e( 'Скидки от 20%', 'besedka' ); ?></h3>
				<p class="advantages__text"><?php esc_html_e( 'Закрытые распродажи доступны зарегистрированным пользователям', 'besedka' ); ?></p>
			</div>
		</div>
	</div>
</section>

<!-- ==========================================================================
     КАК ОФОРМИТЬ ЗАКАЗ
     ========================================================================== -->
<section class="section section--soft">
	<div class="container">
		<div class="section__head">
			<h2 class="section__title"><?php esc_html_e( 'Как оформить заказ', 'besedka' ); ?></h2>
		</div>
		<div class="steps__list">
			<div class="steps__item">
				<span class="steps__number">1</span>
				<h3 class="steps__title"><?php esc_html_e( 'Выберите товар', 'besedka' ); ?></h3>
				<p class="steps__text"><?php esc_html_e( 'Найдите нужную позицию в каталоге', 'besedka' ); ?></p>
			</div>
			<div class="steps__item">
				<span class="steps__number">2</span>
				<h3 class="steps__title"><?php esc_html_e( 'Оформите заказ', 'besedka' ); ?></h3>
				<p class="steps__text"><?php esc_html_e( 'Укажите адрес, дату и время доставки', 'besedka' ); ?></p>
			</div>
			<div class="steps__item">
				<span class="steps__number">3</span>
				<h3 class="steps__title"><?php esc_html_e( 'Оплатите удобным способом', 'besedka' ); ?></h3>
				<p class="steps__text"><?php esc_html_e( 'Картой онлайн, при получении или частями', 'besedka' ); ?></p>
			</div>
			<div class="steps__item">
				<span class="steps__number">4</span>
				<h3 class="steps__title"><?php esc_html_e( 'Получите заказ', 'besedka' ); ?></h3>
				<p class="steps__text"><?php esc_html_e( 'Доставим точно в срок в ваш регион', 'besedka' ); ?></p>
			</div>
		</div>
	</div>
</section>

<!-- ==========================================================================
     ФОРМА ИНДИВИДУАЛЬНОГО ЗАКАЗА
     ========================================================================== -->
<section class="custom-order">
	<div class="container">
		<div class="custom-order__inner">
			<div class="custom-order__intro">
				<h2 class="custom-order__title"><?php esc_html_e( 'Не нашли то, что искали?', 'besedka' ); ?></h2>
				<p class="custom-order__text"><?php esc_html_e( 'Расскажите, что вам нужно — наш менеджер подберёт подходящие товары и проконсультирует по рецептуре.', 'besedka' ); ?></p>
			</div>

			<form class="custom-order__form" data-custom-order-form>
				<?php wp_nonce_field( 'besedka_nonce', 'custom_order_nonce' ); ?>

				<div class="custom-order__field">
					<label class="custom-order__label" for="co-name"><?php esc_html_e( 'Имя', 'besedka' ); ?></label>
					<input class="custom-order__input" type="text" id="co-name" name="name" required>
				</div>

				<div class="custom-order__field">
					<label class="custom-order__label" for="co-phone"><?php esc_html_e( 'Телефон', 'besedka' ); ?></label>
					<input class="custom-order__input" type="tel" id="co-phone" name="phone" required>
				</div>

				<div class="custom-order__field custom-order__field--full">
					<label class="custom-order__label" for="co-interest"><?php esc_html_e( 'Что вас интересует', 'besedka' ); ?></label>
					<input class="custom-order__input" type="text" id="co-interest" name="interest" placeholder="<?php esc_attr_e( 'Например: турбо-дрожжи, дубовые кубики...', 'besedka' ); ?>">
				</div>

				<div class="custom-order__field custom-order__field--full">
					<label class="custom-order__label" for="co-comment"><?php esc_html_e( 'Комментарий', 'besedka' ); ?></label>
					<textarea class="custom-order__textarea" id="co-comment" name="comment"></textarea>
				</div>

				<button type="submit" class="btn btn--primary custom-order__submit"><?php esc_html_e( 'Отправить заявку', 'besedka' ); ?></button>

				<p class="custom-order__message" data-custom-order-message></p>
			</form>
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
				'<h2>%1$s</h2><p>%2$s</p><p>%3$s</p>',
				__( 'Товары для самогоноварения с доставкой по России и СНГ', 'besedka' ),
				__( 'Мы производим и поставляем более 700 позиций для самогоноварения: спиртовые дрожжи, дубовые кубики и чипсы, эссенции, бонификаторы, уголь для очистки и комплектующие для дистилляции. К каждому товару — подробная инструкция по применению.', 'besedka' ),
				__( 'Собственное производство гарантирует стабильное качество сырья и рецептуры в каждой партии. Оплата картой онлайн, при получении или частями — выбирайте удобный способ, доставляем в 83 региона России и страны СНГ.', 'besedka' )
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
