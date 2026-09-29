<?php
/**
 * Тема «Беседка» — подключения, поддержка WordPress/WooCommerce, вспомогательные функции.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Прямой доступ к файлу запрещён.
}

define( 'BESEDKA_VERSION', '1.0.0' );
define( 'BESEDKA_DIR', get_template_directory() );
define( 'BESEDKA_URI', get_template_directory_uri() );

/* ==========================================================================
   1. БАЗОВАЯ НАСТРОЙКА ТЕМЫ
   ========================================================================== */
if ( ! function_exists( 'besedka_setup' ) ) {
	function besedka_setup() {
		// Переводы темы.
		load_theme_textdomain( 'besedka', BESEDKA_DIR . '/languages' );

		// Заголовок страницы через wp_head.
		add_theme_support( 'title-tag' );

		// Миниатюры записей и товаров.
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 600, 600, true );
		add_image_size( 'besedka-card', 600, 600, true );
		add_image_size( 'besedka-thumb', 120, 120, true );
		add_image_size( 'besedka-gallery', 900, 900, true );

		// HTML5-разметка для стандартных узлов WordPress.
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);

		// RSS-ссылки в head.
		add_theme_support( 'automatic-feed-links' );

		// Поддержка логотипа через Кастомайзер.
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 60,
				'width'       => 200,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		// Совместимость с WooCommerce.
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		// Меню WordPress.
		register_nav_menus(
			array(
				'top-links'  => __( 'Верхнее меню (О нас / Контакты)', 'besedka' ),
				'categories' => __( 'Меню категорий каталога', 'besedka' ),
				'footer-info'    => __( 'Футер: Информация', 'besedka' ),
				'footer-catalog' => __( 'Футер: Каталог', 'besedka' ),
			)
		);
	}
	add_action( 'after_setup_theme', 'besedka_setup' );
}

/* ==========================================================================
   2. ПОДКЛЮЧЕНИЕ СТИЛЕЙ И СКРИПТОВ
   ========================================================================== */
if ( ! function_exists( 'besedka_scripts' ) ) {
	function besedka_scripts() {
		// Основной файл стилей темы.
		wp_enqueue_style( 'besedka-style', get_stylesheet_uri(), array(), BESEDKA_VERSION );

		// Основной файл скриптов темы.
		wp_enqueue_script( 'besedka-script', BESEDKA_URI . '/script.js', array(), BESEDKA_VERSION, true );

		// Данные для script.js: адреса AJAX, nonce, ссылки.
		wp_localize_script(
			'besedka-script',
			'besedkaData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'wcAjaxAddToCartUrl' => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'add_to_cart' ) : '',
				'nonce'         => wp_create_nonce( 'besedka_nonce' ),
				'cartUrl'       => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
				'checkoutUrl'   => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
				'favoritesUrl'  => home_url( '/favorites/' ),
				'currency'      => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₽',
				'i18n'          => array(
					'added'        => __( 'Добавлено', 'besedka' ),
					'error'        => __( 'Ошибка, попробуйте ещё раз', 'besedka' ),
					'outOfStock'   => __( 'Нет в наличии', 'besedka' ),
					'sending'      => __( 'Отправляем…', 'besedka' ),
				),
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'besedka_scripts' );
}

/* ==========================================================================
   3. ВИДЖЕТЫ (ОПЦИОНАЛЬНО, ДЛЯ ГИБКОСТИ ФУТЕРА)
   ========================================================================== */
if ( ! function_exists( 'besedka_widgets_init' ) ) {
	function besedka_widgets_init() {
		register_sidebar(
			array(
				'name'          => __( 'Футер — колонка «О магазине»', 'besedka' ),
				'id'            => 'footer-about',
				'before_widget' => '<div class="footer-col__widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="footer-col__title">',
				'after_title'   => '</h3>',
			)
		);
	}
	add_action( 'widgets_init', 'besedka_widgets_init' );
}

/* ==========================================================================
   4. WOOCOMMERCE: НАСТРОЙКА ВЫВОДА
   ========================================================================== */

// Отключаем стандартные обёртки и хлебные крошки WooCommerce — своя вёрстка в шаблонах темы.
add_action(
	'wp',
	function () {
		if ( function_exists( 'is_woocommerce' ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
			remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_breadcrumb', 20 );
			remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		}
	}
);

// Ширина миниатюр товара в каталоге WooCommerce.
if ( ! function_exists( 'besedka_woocommerce_image_dimensions' ) ) {
	function besedka_woocommerce_image_dimensions() {
		$catalog = array(
			'width'  => 600,
			'height' => 600,
			'crop'   => 1,
		);
		update_option( 'shop_catalog_image_size', $catalog );
	}
	add_action( 'after_setup_theme', 'besedka_woocommerce_image_dimensions' );
}

// Количество товаров в ряд для стандартных WooCommerce-хуков (используем свои шаблоны, но оставляем фильтр для совместимости).
add_filter( 'loop_shop_columns', function () { return 4; } );
add_filter( 'loop_shop_per_page', function () { return 12; }, 20 );

/* ==========================================================================
   5. WALKER ДЛЯ МЕНЮ КАТЕГОРИЙ (БЭМ-РАЗМЕТКА ПУНКТОВ)
   ========================================================================== */
if ( ! class_exists( 'Besedka_Categories_Walker' ) ) {
	class Besedka_Categories_Walker extends Walker_Nav_Menu {
		public function start_lvl( &$output, $depth = 0, $args = null ) {}
		public function end_lvl( &$output, $depth = 0, $args = null ) {}

		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$classes = 'nav-categories__item';
			if ( in_array( 'current-menu-item', $item->classes, true ) ) {
				$classes .= ' is-active';
			}
			$link_classes = 'nav-categories__link' . ( in_array( 'current-menu-item', $item->classes, true ) ? ' is-active' : '' );

			$output .= '<li class="' . esc_attr( $classes ) . '">';
			$output .= '<a class="' . esc_attr( $link_classes ) . '" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
			$output .= '</li>';
		}

		public function end_el( &$output, $item, $depth = 0, $args = null ) {}
	}
}

/* ==========================================================================
   6. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ШАБЛОНА
   ========================================================================== */

/**
 * Массив категорий товаров (product_cat) для меню каталога и фильтров.
 * Возвращает пусто, если WooCommerce не активен.
 */
if ( ! function_exists( 'besedka_get_product_categories' ) ) {
	function besedka_get_product_categories() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}
		return get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
	}
}

/**
 * Список городов с пунктами выдачи (slug => array('name', 'address')),
 * подгружается один раз из inc/cities.php.
 */
if ( ! function_exists( 'besedka_get_cities' ) ) {
	function besedka_get_cities() {
		static $cities = null;
		if ( null === $cities ) {
			$file   = BESEDKA_DIR . '/inc/cities.php';
			$cities = file_exists( $file ) ? (array) include $file : array();
		}
		return $cities;
	}
}

/**
 * Верхняя тёмная полоса над хедером: текущий город (открывает модалку
 * выбора города) и ссылки "О компании", "Доставка", "Оплата".
 */
if ( ! function_exists( 'besedka_render_topbar' ) ) {
	function besedka_render_topbar() {
		$cities       = besedka_get_cities();
		$default_slug = 'moskva';
		$default_name = isset( $cities[ $default_slug ] ) ? $cities[ $default_slug ]['name'] : __( 'Москва', 'besedka' );
		?>
		<div class="topbar">
			<div class="container">
				<div class="topbar__inner">
					<button type="button" class="topbar__city" data-city-trigger>
						<svg class="topbar__city-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-6-5.686-6-10.5A6 6 0 0 1 18 10.5C18 15.286 12 21 12 21z"/><circle cx="12" cy="10.5" r="2"/></svg>
						<span data-city-current><?php echo esc_html( $default_name ); ?></span>
					</button>
					<nav class="topbar__links" aria-label="<?php esc_attr_e( 'Дополнительное меню', 'besedka' ); ?>">
						<a class="topbar__link" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'О компании', 'besedka' ); ?></a>
						<a class="topbar__link" href="<?php echo esc_url( home_url( '/delivery/' ) ); ?>"><?php esc_html_e( 'Доставка', 'besedka' ); ?></a>
						<a class="topbar__link" href="<?php echo esc_url( home_url( '/payment/' ) ); ?>"><?php esc_html_e( 'Оплата', 'besedka' ); ?></a>
					</nav>
				</div>
			</div>
		</div>
		<?php
	}
}

/**
 * Модальное окно "Выбор города": поиск + список городов, сгруппированный
 * по первой букве. Открывается кликом по текущему городу в топбаре.
 */
if ( ! function_exists( 'besedka_render_city_modal' ) ) {
	function besedka_render_city_modal() {
		$cities = besedka_get_cities();
		if ( empty( $cities ) ) {
			return;
		}

		$groups = array();
		foreach ( $cities as $slug => $city ) {
			$letter = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $city['name'], 0, 1 ) ) : strtoupper( substr( $city['name'], 0, 1 ) );
			if ( ! isset( $groups[ $letter ] ) ) {
				$groups[ $letter ] = array();
			}
			$groups[ $letter ][ $slug ] = $city['name'];
		}
		ksort( $groups, SORT_STRING | SORT_FLAG_CASE );
		foreach ( $groups as &$group ) {
			asort( $group, SORT_STRING | SORT_FLAG_CASE );
		}
		unset( $group );
		?>
		<div class="city-modal" data-city-modal aria-hidden="true">
			<div class="city-modal__overlay" data-city-modal-close></div>
			<div class="city-modal__window" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Выбор города', 'besedka' ); ?>">
				<button type="button" class="city-modal__close" data-city-modal-close aria-label="<?php esc_attr_e( 'Закрыть', 'besedka' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6l12 12M18 6L6 18"/></svg>
				</button>
				<h2 class="city-modal__title"><?php esc_html_e( 'Выбор города', 'besedka' ); ?></h2>
				<p class="city-modal__current"><?php esc_html_e( 'Ваш город:', 'besedka' ); ?> <span data-city-current></span></p>
				<input class="city-modal__search" type="text" placeholder="<?php esc_attr_e( 'Введите ваш город', 'besedka' ); ?>" data-city-search>
				<p class="city-modal__subtitle"><?php esc_html_e( 'Города с пунктами выдачи заказов:', 'besedka' ); ?></p>
				<div class="city-modal__list" data-city-list>
					<?php foreach ( $groups as $letter => $group ) : ?>
						<div class="city-modal__group">
							<span class="city-modal__letter"><?php echo esc_html( $letter ); ?>:</span>
							<ul class="city-modal__cities">
								<?php foreach ( $group as $slug => $name ) : ?>
									<li><button type="button" class="city-modal__item" data-city-item data-city-slug="<?php echo esc_attr( $slug ); ?>" data-city-name="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></button></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="city-modal__empty" data-city-empty hidden><?php esc_html_e( 'Город не найден.', 'besedka' ); ?></p>
			</div>
		</div>
		<?php
	}
}

/**
 * Выводит логотип магазина: сначала логотип, загруженный через Кастомайзер
 * (Внешний вид → Настроить → Свойства сайта), затем встроенный в тему файл
 * img/logo/logo.svg, и только если ничего нет — текстовое название сайта.
 */
if ( ! function_exists( 'besedka_logo' ) ) {
	function besedka_logo() {
		if ( has_custom_logo() ) {
			the_custom_logo();
			return;
		}

		$logo_path = get_template_directory() . '/img/logo/logo.svg';
		if ( file_exists( $logo_path ) ) {
			printf(
				'<img class="logo__image" src="%s" alt="%s" width="152" height="40">',
				esc_url( get_template_directory_uri() . '/img/logo/logo.svg' ),
				esc_attr( get_bloginfo( 'name' ) )
			);
			return;
		}

		echo '<span class="logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}
}

/**
 * Округлённый рейтинг товара в виде звёзд (SVG).
 *
 * @param float $rating Рейтинг от 0 до 5.
 */
if ( ! function_exists( 'besedka_render_stars' ) ) {
	function besedka_render_stars( $rating = 0 ) {
		$rating = max( 0, min( 5, round( floatval( $rating ) ) ) );
		$out    = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$fill = $i <= $rating ? 'currentColor' : 'none';
			$out .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="' . esc_attr( $fill ) . '" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><polygon points="12 2 15 9 22 9 16.5 13.5 18.5 21 12 16.8 5.5 21 7.5 13.5 2 9 9 9"/></svg>';
		}
		return $out;
	}
}

/**
 * Отдельный блок-переключатель "Категории" с иконками — выводится
 * над панелью фильтров (не является частью формы фильтров). Ссылки
 * ведут прямо на архив соответствующей категории товаров.
 */
if ( ! function_exists( 'besedka_render_category_selector' ) ) {
	function besedka_render_category_selector() {
		$categories = array(
			'ugol'        => array( 'label' => __( 'Уголь', 'besedka' ), 'icon' => 'ugol.png' ),
			'kubiki'      => array( 'label' => __( 'Дубовые кубики', 'besedka' ), 'icon' => 'kubiki.png' ),
			'essencii'    => array( 'label' => __( 'Эссенции', 'besedka' ), 'icon' => 'essencii.png' ),
			'drozhzhi'    => array( 'label' => __( 'Спиртовые дрожжи', 'besedka' ), 'icon' => 'drozhzhi.png' ),
			'komplekt'    => array( 'label' => __( 'Комплектующие', 'besedka' ), 'icon' => 'komplekt.png' ),
			'bonifikator' => array( 'label' => __( 'Бонификаторы', 'besedka' ), 'icon' => 'bonifikator.png' ),
		);

		$current_category = get_queried_object();
		$selected_slug     = ( $current_category instanceof WP_Term ) ? $current_category->slug : '';
		?>
		<div class="type-selector">
			<div class="type-selector__list">
				<?php foreach ( $categories as $slug => $category ) :
					$is_active = ( $selected_slug === $slug );
					?>
					<a class="type-selector__item <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/product-category/' . $slug . '/' ) ); ?>">
						<img class="type-selector__icon" src="<?php echo esc_url( get_template_directory_uri() . '/img/categories/' . $category['icon'] ); ?>" alt="" width="66" height="66" loading="lazy">
						<span class="type-selector__label"><?php echo esc_html( $category['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Панель фильтров каталога (категория, цена) — используется и на главной
 * странице (index.php), и в архиве категории (archive-product.php).
 *
 * @param string $action_url        Куда отправлять форму (текущий URL страницы/архива).
 * @param string $forced_category   Категория, зафиксированная текущим архивом (если есть).
 */
if ( ! function_exists( 'besedka_render_filters_panel' ) ) {
	function besedka_render_filters_panel( $action_url = '', $forced_category = '' ) {
		$action_url = $action_url ? $action_url : home_url( '/' );
		$categories = besedka_get_product_categories();

		$selected_category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : $forced_category;
		$price_min          = isset( $_GET['price_min'] ) ? intval( $_GET['price_min'] ) : '';
		$price_max          = isset( $_GET['price_max'] ) ? intval( $_GET['price_max'] ) : '';
		?>
		<div class="filters" data-filters-panel>
			<button type="button" class="filters__toggle" data-filters-toggle>
				<span><?php esc_html_e( 'Фильтры', 'besedka' ); ?></span>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
			</button>

			<div class="filters__inner">
				<button type="button" class="filters__close" data-filters-close aria-label="<?php esc_attr_e( 'Закрыть фильтры', 'besedka' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
				</button>

				<form class="filters__form" method="get" action="<?php echo esc_url( $action_url ); ?>" data-filters-form>

					<div class="filters__group">
						<label class="filters__label" for="filter-category"><?php esc_html_e( 'Категория товара', 'besedka' ); ?></label>
						<select class="filters__select" id="filter-category" name="category">
							<option value=""><?php esc_html_e( 'Все категории', 'besedka' ); ?></option>
							<?php foreach ( $categories as $category ) : ?>
								<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $selected_category, $category->slug ); ?>>
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filters__group">
						<span class="filters__label"><?php esc_html_e( 'Цена, ₽', 'besedka' ); ?></span>
						<div class="filters__price">
							<input class="filters__price-input" type="number" min="0" name="price_min" placeholder="<?php esc_attr_e( 'от', 'besedka' ); ?>" value="<?php echo esc_attr( $price_min ); ?>">
							<span class="filters__price-sep">—</span>
							<input class="filters__price-input" type="number" min="0" name="price_max" placeholder="<?php esc_attr_e( 'до', 'besedka' ); ?>" value="<?php echo esc_attr( $price_max ); ?>">
						</div>
					</div>

					<div class="filters__actions">
						<button type="submit" class="btn btn--primary filters__apply"><?php esc_html_e( 'Показать', 'besedka' ); ?></button>
						<a class="filters__reset" href="<?php echo esc_url( $action_url ); ?>" data-filters-reset><?php esc_html_e( 'Сбросить фильтры', 'besedka' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<div class="filters__overlay" data-filters-overlay></div>
		<?php
	}
}

/**
 * Карточка товара в сетке каталога (используется в index.php, archive-product.php
 * и во всех блоках выборок «Популярные», «Скидки», «Новинки» и т.д.).
 *
 * Работает как с товарами WooCommerce ($product — WC_Product), так и мягко
 * деградирует, если WooCommerce не активен.
 *
 * @param int|WC_Product $product Товар или его ID.
 */
if ( ! function_exists( 'besedka_render_product_card' ) ) {
	function besedka_render_product_card( $product ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );
		if ( ! $product ) {
			return;
		}

		$id          = $product->get_id();
		$permalink   = get_permalink( $id );
		$title       = $product->get_name();
		$image_id    = $product->get_image_id();
		$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'besedka-card' ) : wc_placeholder_img_src( 'besedka-card' );
		$rating      = $product->get_average_rating();
		$review_count = $product->get_review_count();
		$in_stock    = $product->is_in_stock();
		$is_on_sale  = $product->is_on_sale();
		$regular     = $product->get_regular_price();
		$sale        = $product->get_sale_price();
		$composition = get_post_meta( $id, '_besedka_composition', true );
		$size        = get_post_meta( $id, '_besedka_size', true );
		$is_new      = get_post_meta( $id, '_besedka_is_new', true );
		$is_hit      = get_post_meta( $id, '_besedka_is_hit', true );
		$today       = get_post_meta( $id, '_besedka_delivery_today', true );

		?>
		<article class="product-card" data-product-card data-product-id="<?php echo esc_attr( $id ); ?>">
			<a class="product-card__image-wrap" href="<?php echo esc_url( $permalink ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>">
				<img class="product-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="600" height="600">

				<div class="product-card__badges">
					<?php if ( $is_hit ) : ?>
						<span class="product-card__badge product-card__badge--hit"><?php esc_html_e( 'Хит', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $is_new ) : ?>
						<span class="product-card__badge product-card__badge--new"><?php esc_html_e( 'Новинка', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $is_on_sale ) : ?>
						<span class="product-card__badge product-card__badge--sale"><?php esc_html_e( 'Скидка', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $today ) : ?>
						<span class="product-card__badge product-card__badge--today"><?php esc_html_e( 'Доставка сегодня', 'besedka' ); ?></span>
					<?php endif; ?>
				</div>
			</a>

			<button type="button" class="product-card__favorite" data-favorite-btn data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Добавить в избранное', 'besedka' ); ?>">
				<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
			</button>

			<button type="button" class="product-card__quickview btn btn--dark btn--small btn--full" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>">
				<?php esc_html_e( 'Быстрый просмотр', 'besedka' ); ?>
			</button>

			<div class="product-card__body">
				<h3 class="product-card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>

				<?php if ( $composition ) : ?>
					<p class="product-card__composition"><?php echo esc_html( $composition ); ?></p>
				<?php endif; ?>

				<?php if ( $size ) : ?>
					<p class="product-card__size"><?php echo esc_html( $size ); ?></p>
				<?php endif; ?>

				<div class="product-card__rating">
					<span class="product-card__rating-stars"><?php echo besedka_render_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="product-card__rating-count">(<?php echo esc_html( $review_count ); ?>)</span>
				</div>

				<p class="product-card__availability <?php echo $in_stock ? '' : 'product-card__availability--out'; ?>">
					<?php echo $in_stock ? esc_html__( 'В наличии', 'besedka' ) : esc_html__( 'Нет в наличии', 'besedka' ); ?>
				</p>

				<div class="product-card__prices">
					<?php if ( $is_on_sale && $sale ) : ?>
						<span class="product-card__price-old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></span>
						<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $sale ) ); ?></span>
					<?php else : ?>
						<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="product-card__footer">
				<button type="button" class="btn btn--primary product-card__btn-order" data-add-to-cart data-product-id="<?php echo esc_attr( $id ); ?>" <?php disabled( $in_stock, false ); ?>>
					<?php esc_html_e( 'Заказать', 'besedka' ); ?>
				</button>
				<button type="button" class="btn btn--outline btn--icon product-card__btn-quickview-mobile" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Быстрый просмотр', 'besedka' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
				</button>
			</div>
		</article>
		<?php
	}
}

/**
 * Возвращает WP_Query/список товаров с учётом GET-параметров фильтра
 * (категория, цена от/до, тип товара). Используется в index.php и archive-product.php.
 *
 * @param array $extra_args Дополнительные аргументы (например, ограничение по подборке).
 */
if ( ! function_exists( 'besedka_get_filtered_products' ) ) {
	function besedka_get_filtered_products( $extra_args = array() ) {
		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : ( isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );

		$args = array(
			'status'   => 'publish',
			'limit'    => 12,
			'page'     => max( 1, $paged ),
			'paginate' => true,
		);

		if ( ! empty( $_GET['category'] ) ) {
			$args['category'] = array( sanitize_title( wp_unslash( $_GET['category'] ) ) );
		}

		if ( ! empty( $_GET['price_min'] ) || ! empty( $_GET['price_max'] ) ) {
			$min = ! empty( $_GET['price_min'] ) ? floatval( $_GET['price_min'] ) : 0;
			$max = ! empty( $_GET['price_max'] ) ? floatval( $_GET['price_max'] ) : 999999;
			$args['meta_query'] = array(
				array(
					'key'     => '_price',
					'value'   => array( $min, $max ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			);
		}

		$args = wp_parse_args( $extra_args, $args );

		if ( function_exists( 'wc_get_products' ) ) {
			return wc_get_products( $args );
		}

		return (object) array( 'products' => array(), 'total' => 0 );
	}
}

/* ==========================================================================
   7. ПОДРОБНАЯ КАРТОЧКА ТОВАРА (ИСПОЛЬЗУЕТСЯ В single-product.php И В AJAX-МОДАЛКЕ)
   ========================================================================== */

/**
 * Выводит содержимое подробной карточки товара: галерея слева, информация
 * и покупка справа. Общий рендер для страницы товара (single-product.php)
 * и для модального/полноэкранного окна быстрого просмотра (script.js + AJAX).
 *
 * @param WC_Product $product Товар WooCommerce.
 */
if ( ! function_exists( 'besedka_render_product_details' ) ) {
	function besedka_render_product_details( $product ) {
		if ( ! $product ) {
			return;
		}

		$id           = $product->get_id();
		$title        = $product->get_name();
		$sku          = $product->get_sku();
		$rating       = $product->get_average_rating();
		$review_count = $product->get_review_count();
		$regular      = $product->get_regular_price();
		$sale         = $product->get_sale_price();
		$is_on_sale   = $product->is_on_sale();
		$price        = $product->get_price();
		$discount     = ( $is_on_sale && $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
		$description  = $product->get_description();
		$composition  = get_post_meta( $id, '_besedka_composition', true );
		$size         = get_post_meta( $id, '_besedka_size', true );
		$width        = get_post_meta( $id, '_besedka_width', true );
		$height       = get_post_meta( $id, '_besedka_height', true );
		$care         = get_post_meta( $id, '_besedka_care', true );

		// Галерея: главное изображение + миниатюры товара.
		$image_ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
		if ( empty( $image_ids ) ) {
			$image_ids = array( 0 );
		}
		?>
		<div class="modal__gallery">
			<div class="modal__main-image-wrap">
				<?php foreach ( $image_ids as $index => $img_id ) :
					$url = $img_id ? wp_get_attachment_image_url( $img_id, 'besedka-gallery' ) : wc_placeholder_img_src( 'besedka-gallery' );
					?>
					<img class="modal__main-image" data-gallery-image="<?php echo esc_attr( $index ); ?>" src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $title ); ?>" <?php echo 0 === $index ? '' : 'style="display:none"'; ?>>
				<?php endforeach; ?>

				<?php if ( count( $image_ids ) > 1 ) : ?>
					<button type="button" class="modal__arrow modal__arrow--prev" data-gallery-prev aria-label="<?php esc_attr_e( 'Предыдущее фото', 'besedka' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
					</button>
					<button type="button" class="modal__arrow modal__arrow--next" data-gallery-next aria-label="<?php esc_attr_e( 'Следующее фото', 'besedka' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 18l6-6-6-6"/></svg>
					</button>
				<?php endif; ?>
			</div>

			<?php if ( count( $image_ids ) > 1 ) : ?>
				<div class="modal__thumbs">
					<?php foreach ( $image_ids as $index => $img_id ) :
						$thumb = $img_id ? wp_get_attachment_image_url( $img_id, 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' );
						?>
						<button type="button" class="modal__thumb <?php echo 0 === $index ? 'is-active' : ''; ?>" data-gallery-thumb="<?php echo esc_attr( $index ); ?>">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="">
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $width || $height ) : ?>
				<div class="modal__dimensions">
					<?php if ( $width ) : ?><span><?php esc_html_e( 'Ширина:', 'besedka' ); ?> <?php echo esc_html( $width ); ?> см</span><?php endif; ?>
					<?php if ( $height ) : ?><span><?php esc_html_e( 'Высота:', 'besedka' ); ?> <?php echo esc_html( $height ); ?> см</span><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="modal__info">
			<div class="modal__head">
				<h2 class="modal__title"><?php echo esc_html( $title ); ?></h2>
				<button type="button" class="modal__favorite" data-favorite-btn data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Добавить в избранное', 'besedka' ); ?>">
					<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
				</button>
			</div>

			<?php if ( $sku ) : ?>
				<p class="modal__sku"><?php esc_html_e( 'Артикул:', 'besedka' ); ?> <?php echo esc_html( $sku ); ?></p>
			<?php endif; ?>

			<div class="modal__rating">
				<span class="modal__rating-stars"><?php echo besedka_render_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span><?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'отзывов', 'besedka' ); ?></span>
			</div>

			<div class="modal__price-row">
				<?php if ( $is_on_sale && $sale ) : ?>
					<span class="modal__price"><?php echo wp_kses_post( wc_price( $sale ) ); ?></span>
					<span class="modal__price-old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></span>
					<span class="modal__discount">-<?php echo esc_html( $discount ); ?>%</span>
				<?php else : ?>
					<span class="modal__price"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
				<?php endif; ?>
			</div>
			<p class="modal__installment"><?php esc_html_e( 'Можно оплатить частями при оформлении заказа', 'besedka' ); ?></p>

			<div class="modal__counter-row">
				<div class="modal__counter">
					<button type="button" class="modal__counter-btn" data-counter-minus aria-label="<?php esc_attr_e( 'Уменьшить количество', 'besedka' ); ?>">−</button>
					<input type="number" class="modal__counter-value" data-counter-value value="1" min="1" readonly>
					<button type="button" class="modal__counter-btn" data-counter-plus aria-label="<?php esc_attr_e( 'Увеличить количество', 'besedka' ); ?>">+</button>
				</div>
				<span class="product-card__availability <?php echo $product->is_in_stock() ? '' : 'product-card__availability--out'; ?>">
					<?php echo $product->is_in_stock() ? esc_html__( 'В наличии', 'besedka' ) : esc_html__( 'Нет в наличии', 'besedka' ); ?>
				</span>
			</div>

			<div class="modal__actions">
				<button type="button" class="btn btn--outline" data-add-to-cart data-product-id="<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Добавить в корзину', 'besedka' ); ?>
				</button>
				<button type="button" class="btn btn--primary" data-buy-now data-product-id="<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Купить сейчас', 'besedka' ); ?>
				</button>
			</div>

			<div class="modal__delivery">
				<p class="modal__delivery-title"><?php esc_html_e( 'Доставка', 'besedka' ); ?></p>
				<input type="text" class="modal__delivery-address" data-delivery-address placeholder="<?php esc_attr_e( 'Укажите адрес доставки', 'besedka' ); ?>">
				<div class="modal__delivery-row">
					<input type="date" class="modal__delivery-date" data-delivery-date>
					<select class="modal__delivery-time" data-delivery-time>
						<option value="10-13"><?php esc_html_e( '10:00–13:00', 'besedka' ); ?></option>
						<option value="13-16"><?php esc_html_e( '13:00–16:00', 'besedka' ); ?></option>
						<option value="16-19"><?php esc_html_e( '16:00–19:00', 'besedka' ); ?></option>
						<option value="19-21"><?php esc_html_e( '19:00–21:00', 'besedka' ); ?></option>
					</select>
				</div>
				<p class="modal__delivery-cost" data-delivery-cost><?php esc_html_e( 'Стоимость доставки рассчитывается по адресу: от 300 ₽', 'besedka' ); ?></p>
			</div>

			<div class="modal__tabs" role="tablist">
				<button type="button" class="modal__tab is-active" data-tab="description" role="tab"><?php esc_html_e( 'Описание', 'besedka' ); ?></button>
				<button type="button" class="modal__tab" data-tab="composition" role="tab"><?php esc_html_e( 'Состав', 'besedka' ); ?></button>
				<button type="button" class="modal__tab" data-tab="care" role="tab"><?php esc_html_e( 'Уход', 'besedka' ); ?></button>
			</div>

			<div class="modal__tab-panel is-active" data-tab-panel="description">
				<?php echo wp_kses_post( wpautop( $description ) ); ?>
			</div>
			<div class="modal__tab-panel" data-tab-panel="composition">
				<p><?php echo esc_html( $composition ? $composition : __( 'Информация о составе уточняется у флориста.', 'besedka' ) ); ?></p>
				<?php if ( $size ) : ?><p><?php esc_html_e( 'Размер:', 'besedka' ); ?> <?php echo esc_html( $size ); ?></p><?php endif; ?>
			</div>
			<div class="modal__tab-panel" data-tab-panel="care">
				<p><?php echo esc_html( $care ? $care : __( 'Обрежьте стебли под углом, меняйте воду каждые два дня, избегайте прямых солнечных лучей.', 'besedka' ) ); ?></p>
			</div>

			<p class="modal__disclaimer"><?php esc_html_e( 'Внешний вид букета может немного отличаться от изображения в зависимости от наличия цветов у флориста.', 'besedka' ); ?></p>

			<div class="modal__socials">
				<span class="modal__socials-label"><?php esc_html_e( 'Поделиться:', 'besedka' ); ?></span>
				<a class="modal__social-link" href="https://t.me/share/url?url=<?php echo rawurlencode( get_permalink( $id ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
					<svg viewBox="0 0 24 24"><path d="M9.4 15.5l-.4 5 1.8-1.8 3.6 2.6 4-19-19 7.4 5.4 2 12-7.4z"/></svg>
				</a>
				<a class="modal__social-link" href="https://wa.me/?text=<?php echo rawurlencode( get_permalink( $id ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
					<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2z"/></svg>
				</a>
				<a class="modal__social-link" href="https://vk.com/share.php?url=<?php echo rawurlencode( get_permalink( $id ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="VK">
					<svg viewBox="0 0 24 24"><path d="M2 12c0-5 0-7.5 1.5-9S7 2 12 2s7.5 0 9 1.5S22 7 22 12s0 7.5-1.5 9S17 22 12 22s-7.5 0-9-1.5S2 17 2 12z"/></svg>
				</a>
			</div>
		</div>
		<?php
	}
}

/* ==========================================================================
   8. AJAX: БЫСТРЫЙ ПРОСМОТР ТОВАРА (МОДАЛЬНОЕ ОКНО)
   ========================================================================== */
if ( ! function_exists( 'besedka_ajax_quick_view' ) ) {
	function besedka_ajax_quick_view() {
		check_ajax_referer( 'besedka_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => __( 'Товар не найден', 'besedka' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Товар не найден', 'besedka' ) ) );
		}

		ob_start();
		besedka_render_product_details( $product );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
	add_action( 'wp_ajax_besedka_quick_view', 'besedka_ajax_quick_view' );
	add_action( 'wp_ajax_nopriv_besedka_quick_view', 'besedka_ajax_quick_view' );
}

/* ==========================================================================
   9. AJAX: ФОРМА ИНДИВИДУАЛЬНОГО ЗАКАЗА «ПОДОБРАТЬ БУКЕТ»
   ========================================================================== */
if ( ! function_exists( 'besedka_ajax_custom_order' ) ) {
	function besedka_ajax_custom_order() {
		check_ajax_referer( 'besedka_nonce', 'nonce' );

		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$interest = isset( $_POST['interest'] ) ? sanitize_text_field( wp_unslash( $_POST['interest'] ) ) : '';
		$comment  = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

		if ( empty( $name ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Заполните имя и телефон', 'besedka' ) ) );
		}

		$to      = get_option( 'admin_email' );
		$subject = sprintf( __( 'Новая заявка с сайта от %s', 'besedka' ), $name );
		$body    = sprintf(
			"Имя: %s\nТелефон: %s\nЧто интересует: %s\nКомментарий: %s",
			$name,
			$phone,
			$interest,
			$comment
		);

		$sent = wp_mail( $to, $subject, $body );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Заявка отправлена! Мы свяжемся с вами в ближайшее время.', 'besedka' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Не удалось отправить заявку, попробуйте позвонить нам.', 'besedka' ) ) );
	}
	add_action( 'wp_ajax_besedka_custom_order', 'besedka_ajax_custom_order' );
	add_action( 'wp_ajax_nopriv_besedka_custom_order', 'besedka_ajax_custom_order' );
}

/* ==========================================================================
   10. БЕЗОПАСНАЯ РАБОТА БЕЗ WOOCOMMERCE
   ========================================================================== */
if ( ! function_exists( 'besedka_woocommerce_missing_notice' ) ) {
	function besedka_woocommerce_missing_notice() {
		if ( ! class_exists( 'WooCommerce' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Тема «Беседка» рассчитана на работу с плагином WooCommerce. Установите и активируйте его для полноценной работы каталога.', 'besedka' ) . '</p></div>';
		}
	}
	add_action( 'admin_notices', 'besedka_woocommerce_missing_notice' );
}
