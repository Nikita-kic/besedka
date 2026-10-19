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
		add_image_size( 'besedka-card', 600, 600, false );
		add_image_size( 'besedka-thumb', 120, 120, false );
		add_image_size( 'besedka-gallery', 900, 900, false );

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
				'searchUrl'     => add_query_arg( 'search', '', home_url( '/' ) ),
				'currency'      => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₽',
				'i18n'          => array(
					'nothingFound' => __( 'Ничего не найдено', 'besedka' ),
					'allResults'   => __( 'Все результаты', 'besedka' ),
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
/** Ссылки на мессенджеры и соцсети: единый источник для футера и верхней полосы (Customizer / theme_mod). */
if ( ! function_exists( 'besedka_get_social_url' ) ) {
	function besedka_get_social_url( $network ) {
		$defaults = array(
			'telegram' => 'https://t.me/besedka',
			'whatsapp' => 'https://wa.me/74951234567',
			'vk'       => 'https://vk.com/besedka',
		);
		$default = isset( $defaults[ $network ] ) ? $defaults[ $network ] : '';
		return esc_url( get_theme_mod( 'besedka_' . $network . '_url', $default ) );
	}
}

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
					<div class="topbar__help">
						<a class="topbar__social" href="<?php echo besedka_get_social_url( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Написать в Telegram', 'besedka' ); ?>">
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.4 15.5l-.4 5 1.8-1.8 3.6 2.6 4-19-19 7.4 5.4 2 12-7.4z"/></svg>
						</a>
						<a class="topbar__social" href="<?php echo besedka_get_social_url( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Написать в WhatsApp', 'besedka' ); ?>">
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2z"/></svg>
						</a>
					</div>
					<span class="topbar__divider" aria-hidden="true"></span>
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
				<p class="city-modal__current"><?php esc_html_e( 'Сейчас выбран:', 'besedka' ); ?> <strong data-city-current><?php echo esc_html( isset( $cities['moskva'] ) ? $cities['moskva']['name'] : __( 'Москва', 'besedka' ) ); ?></strong></p>
				<input class="city-modal__search" type="text" placeholder="<?php esc_attr_e( 'Введите ваш город', 'besedka' ); ?>" data-city-search>
				<p class="city-modal__subtitle">
					<svg class="city-modal__subtitle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
					<?php esc_html_e( 'Доставляем Почтой России в любой город', 'besedka' ); ?>
				</p>
				<div class="city-modal__list" data-city-list>
					<?php foreach ( $groups as $letter => $group ) : ?>
						<div class="city-modal__group">
							<span class="city-modal__letter"><?php echo esc_html( $letter ); ?></span>
							<ul class="city-modal__cities">
								<?php foreach ( $group as $slug => $name ) : ?>
									<li><button type="button" class="city-modal__item" data-city-item data-city-slug="<?php echo esc_attr( $slug ); ?>" data-city-name="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></button></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="city-modal__empty" data-city-empty hidden>
					<span><?php esc_html_e( 'Доставим и в', 'besedka' ); ?> «<span data-city-empty-name></span>»</span>
					<button type="button" class="btn btn--primary" data-city-custom><?php esc_html_e( 'Выбрать', 'besedka' ); ?></button>
				</div>
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
			<h2 class="type-selector__title"><?php esc_html_e( 'Категории', 'besedka' ); ?></h2>
			<div class="type-selector__list">
				<?php foreach ( $categories as $slug => $category ) :
					$is_active = ( $selected_slug === $slug );
					?>
					<a class="type-selector__item <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/product-category/' . $slug . '/' ) ); ?>">
						<span class="type-selector__card">
							<img class="type-selector__icon" src="<?php echo esc_url( get_template_directory_uri() . '/img/categories/' . $category['icon'] ); ?>" alt="" width="113" height="113" loading="lazy">
						</span>
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
					<?php if ( ! empty( $_GET['search'] ) ) : ?>
						<input type="hidden" name="search" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ); ?>">
					<?php endif; ?>

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
		$discount    = ( $is_on_sale && $regular > 0 && $sale ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
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
						<span class="product-card__badge product-card__badge--sale"><?php echo $discount ? esc_html( '-' . $discount . '%' ) : esc_html__( 'Скидка', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $today ) : ?>
						<span class="product-card__badge product-card__badge--today"><?php esc_html_e( 'Доставка сегодня', 'besedka' ); ?></span>
					<?php endif; ?>
				</div>
			</a>

			<button type="button" class="product-card__favorite" data-favorite-btn data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Добавить в избранное', 'besedka' ); ?>">
				<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
			</button>

			<div class="product-card__body">
				<h3 class="product-card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>

				<div class="product-card__meta">
					<div class="product-card__prices">
						<?php if ( $is_on_sale && $sale ) : ?>
							<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $sale ) ); ?></span>
							<span class="product-card__price-old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></span>
						<?php else : ?>
							<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $review_count > 0 ) : ?>
						<span class="product-card__rating">
							<svg class="product-card__rating-star" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
							<span class="product-card__rating-value"><?php echo esc_html( number_format_i18n( (float) $rating, 2 ) ); ?></span>
							<span class="product-card__rating-count"><?php echo esc_html( $review_count ); ?></span>
						</span>
					<?php else : ?>
						<span class="product-card__rating product-card__rating--empty"><?php esc_html_e( 'Нет отзывов', 'besedka' ); ?></span>
					<?php endif; ?>
				</div>

				<p class="product-card__availability <?php echo $in_stock ? '' : 'product-card__availability--out'; ?>">
					<span class="product-card__availability-dot" aria-hidden="true"></span>
					<?php echo $in_stock ? esc_html__( 'В наличии', 'besedka' ) : esc_html__( 'Нет в наличии', 'besedka' ); ?>
				</p>
			</div>

			<div class="product-card__footer">
				<?php besedka_add_to_cart_button( $product, __( 'В корзину', 'besedka' ), 'btn btn--primary product-card__btn-order' ); ?>
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

		if ( ! empty( $_GET['search'] ) ) {
			$found           = besedka_search_product_ids( sanitize_text_field( wp_unslash( $_GET['search'] ) ), 200 );
			$args['include'] = $found ? $found : array( 0 );
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

/**
 * Товары блока «Популярные товары» на главной: помечены метой _besedka_popular_order
 * (1, 2, 3 …), выводятся по возрастанию. Пустой массив — меты нет ни у одного товара.
 *
 * @return WC_Product[]
 */
if ( ! function_exists( 'besedka_get_popular_products' ) ) {
	function besedka_get_popular_products() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => -1,
				'meta_key'    => '_besedka_popular_order', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'     => 'meta_value_num',
				'order'       => 'ASC',
				'fields'      => 'ids',
			)
		);
		return array_values( array_filter( array_map( 'wc_get_product', $ids ) ) );
	}
}

// Тысячи в ценах отделяются неразрывным пробелом: «1 250 ₽».
add_filter( 'wc_get_price_thousand_separator', function () { return "\xC2\xA0"; } );

// Цены без копеек по всему сайту (карточки, модалка, корзина, оформление заказа).
add_filter( 'wc_get_price_decimals', '__return_zero' );

/* ==========================================================================
   6.4 КОРЗИНА: КНОПКА В ШАПКЕ, СТАНДАРТНЫЙ AJAX-add_to_cart WOOCOMMERCE
   ========================================================================== */

/** Стандартные скрипты WooCommerce для AJAX-добавления в корзину и обновления фрагментов. */
if ( ! function_exists( 'besedka_enqueue_wc_cart_scripts' ) ) {
	function besedka_enqueue_wc_cart_scripts() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-cart-fragments' );
	}
	add_action( 'wp_enqueue_scripts', 'besedka_enqueue_wc_cart_scripts', 20 );
}

/** Кнопка корзины в шапке (та же разметка, что у «Избранного»). Счётчик скрыт при 0. */
if ( ! function_exists( 'besedka_get_cart_button' ) ) {
	function besedka_get_cart_button() {
		$count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
		$url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

		ob_start();
		?>
		<a class="favorites-btn cart-btn" href="<?php echo esc_url( $url ); ?>">
			<span class="favorites-btn__icon">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2.5l2.2 10.2a1 1 0 0 0 1 .8h8.6a1 1 0 0 0 1-.76L20 8H6.2"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg>
				<span class="favorites-btn__count" data-count="<?php echo esc_attr( $count ); ?>"><?php echo $count ? esc_html( $count ) : ''; ?></span>
			</span>
			<span class="favorites-btn__label"><?php esc_html_e( 'Корзина', 'besedka' ); ?></span>
		</a>
		<?php
		return ob_get_clean();
	}
}

/** Обновление счётчика без перезагрузки: WooCommerce подменяет a.cart-btn после добавления товара. */
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['a.cart-btn'] = besedka_get_cart_button();
		return $fragments;
	}
);

/**
 * Кнопка «в корзину» со стандартным AJAX WooCommerce (ajax_add_to_cart, data-product_id, data-quantity).
 * Без JS работает как обычная ссылка ?add-to-cart=ID.
 *
 * @param WC_Product $product Товар.
 * @param string     $label   Текст кнопки.
 * @param string     $classes Классы оформления.
 */
if ( ! function_exists( 'besedka_add_to_cart_button' ) ) {
	function besedka_add_to_cart_button( $product, $label, $classes = 'btn btn--primary' ) {
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			printf( '<span class="%1$s is-disabled" aria-disabled="true">%2$s</span>', esc_attr( $classes ), esc_html( $label ) );
			return;
		}
		printf(
			'<a href="%1$s" data-quantity="1" data-product_id="%2$s" data-product_sku="%3$s" class="%4$s add_to_cart_button%5$s" rel="nofollow">%6$s</a>',
			esc_url( $product->add_to_cart_url() ),
			esc_attr( $product->get_id() ),
			esc_attr( $product->get_sku() ),
			esc_attr( $classes ),
			$product->supports( 'ajax_add_to_cart' ) ? ' ajax_add_to_cart' : '',
			esc_html( $label )
		);
	}
}

/* ==========================================================================
   6.1 ПОИСК ТОВАРОВ (ПО НАЗВАНИЮ И БРЕНДУ)
   ========================================================================== */

/**
 * ID опубликованных товаров, у которых название или бренд содержат все слова запроса.
 * Регистр, «е/ё» и пробелы внутри слов не учитываются («highspirits» найдёт «High Spirits»).
 *
 * @param string $term  Поисковый запрос.
 * @param int    $limit Максимум результатов.
 * @return int[]
 */
if ( ! function_exists( 'besedka_search_product_ids' ) ) {
	function besedka_search_product_ids( $term, $limit = 50 ) {
		global $wpdb;

		$tokens = preg_split( '/\s+/u', trim( $term ), -1, PREG_SPLIT_NO_EMPTY );
		if ( empty( $tokens ) ) {
			return array();
		}

		$where  = '';
		$params = array();
		foreach ( array_slice( $tokens, 0, 6 ) as $token ) {
			$where   .= " AND REPLACE(CONCAT(p.post_title, ' ', COALESCE(b.meta_value, '')), ' ', '') LIKE %s";
			$params[] = '%' . $wpdb->esc_like( $token ) . '%';
		}
		$params[] = absint( $limit );

		$sql = "SELECT p.ID FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} b ON b.post_id = p.ID AND b.meta_key = '_besedka_brand'
			WHERE p.post_type = 'product' AND p.post_status = 'publish'{$where}
			ORDER BY p.post_title ASC LIMIT %d";

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $params ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

/** Строка товара в выпадающем окне поиска. */
if ( ! function_exists( 'besedka_render_search_item' ) ) {
	function besedka_render_search_item( $product, $extra_class = '' ) {
		$pid = $product->get_id();
		$img = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' );
		?>
		<a class="header-search__item<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>" href="<?php echo esc_url( get_permalink( $pid ) ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $pid ); ?>">
			<img class="header-search__thumb" src="<?php echo esc_url( $img ); ?>" alt="">
			<span class="header-search__name"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="header-search__price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
		</a>
		<?php
	}
}

/** Подсказки «Часто ищут»: Customizer → «Поиск» (по одной в строке). */
if ( ! function_exists( 'besedka_get_search_hints' ) ) {
	function besedka_get_search_hints() {
		$raw   = (string) get_theme_mod( 'besedka_search_hints', "Bragoff\nHigh Spirits\nтурбо-дрожжи 48\nдля зерна\nдля фруктов\nдля новичка" );
		$lines = preg_split( '/\R/u', $raw, -1, PREG_SPLIT_NO_EMPTY );
		$hints = array_values( array_unique( array_filter( array_map( 'trim', (array) $lines ) ) ) );
		return array_slice( $hints, 0, 12 );
	}
}

/** Популярные товары для окна поиска: по total_sales, в наличии, кэш на 12 часов. */
if ( ! function_exists( 'besedka_get_search_popular_ids' ) ) {
	function besedka_get_search_popular_ids() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$ids = get_transient( 'besedka_search_popular' );
		if ( false === $ids ) {
			$exclude = array();
			foreach ( besedka_get_sets() as $set ) {
				$exclude[] = $set->get_id();
			}
			$ids = wc_get_products(
				array(
					'status'       => 'publish',
					'limit'        => 3,
					'orderby'      => 'popularity',
					'order'        => 'DESC',
					'stock_status' => 'instock',
					'exclude'      => $exclude,
					'return'       => 'ids',
				)
			);
			set_transient( 'besedka_search_popular', $ids, 12 * HOUR_IN_SECONDS );
		}
		return array_map( 'intval', (array) $ids );
	}
}

/** Окно подсказок при пустом поле: «Часто ищут» + «Популярные товары». Пусто — ничего не выводит. */
if ( ! function_exists( 'besedka_render_search_suggest' ) ) {
	function besedka_render_search_suggest() {
		$hints    = besedka_get_search_hints();
		$products = array();
		foreach ( besedka_get_search_popular_ids() as $pid ) {
			$product = wc_get_product( $pid );
			if ( $product && $product->is_in_stock() ) {
				$products[] = $product;
			}
		}
		if ( empty( $hints ) && empty( $products ) ) {
			return;
		}
		?>
		<div class="header-search__panel header-search__suggest" data-search-suggest>
			<?php if ( $hints ) : ?>
				<div class="header-search__group">
					<p class="eyebrow"><?php esc_html_e( 'Часто ищут', 'besedka' ); ?></p>
					<div class="header-search__chips">
						<?php foreach ( $hints as $hint ) : ?>
							<a class="chip" href="<?php echo esc_url( add_query_arg( 'search', rawurlencode( $hint ), home_url( '/' ) ) ); ?>"><?php echo esc_html( $hint ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $products ) : ?>
				<div class="header-search__group">
					<p class="eyebrow"><?php esc_html_e( 'Популярные товары', 'besedka' ); ?></p>
					<div class="header-search__products">
						<?php
						foreach ( $products as $i => $product ) {
							besedka_render_search_item( $product, $i >= 2 ? 'header-search__item--extra' : '' );
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * Индекс для мгновенного поиска в шапке: товары в наличии одним JSON, кэш transient.
 * Сбрасывается при сохранении/изменении/удалении товара.
 */
if ( ! function_exists( 'besedka_get_search_index' ) ) {
	function besedka_get_search_index() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$items = get_transient( 'besedka_search_index' );
		if ( false === $items ) {
			$items    = array();
			$products = wc_get_products(
				array(
					'status'       => 'publish',
					'limit'        => -1,
					'stock_status' => 'instock',
					'orderby'      => 'title',
					'order'        => 'ASC',
				)
			);
			foreach ( $products as $product ) {
				$image_id = $product->get_image_id();
				$items[]  = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'brand' => (string) get_post_meta( $product->get_id(), '_besedka_brand', true ),
					'price' => wc_price( $product->get_price() ),
					'img'   => $image_id ? wp_get_attachment_image_url( $image_id, 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' ),
					'url'   => get_permalink( $product->get_id() ),
				);
			}
			set_transient( 'besedka_search_index', $items, DAY_IN_SECONDS );
			update_option( 'besedka_search_index_overflow', count( $items ) > 500 ? 1 : 0, false );
		}
		return $items;
	}
}

if ( ! function_exists( 'besedka_ajax_search_index' ) ) {
	function besedka_ajax_search_index() {
		wp_send_json_success( besedka_get_search_index() );
	}
	add_action( 'wp_ajax_besedka_search_index', 'besedka_ajax_search_index' );
	add_action( 'wp_ajax_nopriv_besedka_search_index', 'besedka_ajax_search_index' );
}

if ( ! function_exists( 'besedka_flush_search_index' ) ) {
	function besedka_flush_search_index() {
		delete_transient( 'besedka_search_index' );
		delete_transient( 'besedka_search_popular' );
	}
	foreach ( array( 'save_post_product', 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_product_set_stock_status', 'deleted_post', 'trashed_post', 'untrashed_post' ) as $besedka_hook ) {
		add_action( $besedka_hook, 'besedka_flush_search_index' );
	}
}

/** Предупреждение в админке: мгновенный поиск рассчитан на каталог до 500 товаров. */
add_action(
	'admin_notices',
	function () {
		if ( get_option( 'besedka_search_index_overflow' ) && current_user_can( 'manage_woocommerce' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Беседка: в каталоге больше 500 товаров — мгновенный поиск в шапке стоит переделать на серверный.', 'besedka' ) . '</p></div>';
		}
	}
);

/* ==========================================================================
   6.2 БАННЕРЫ НА ГЛАВНОЙ И ТАЙМЕР АКЦИИ
   ========================================================================== */

/**
 * Момент окончания акции (Unix-время). Берётся из Customizer («Баннеры» → «Окончание акции»,
 * формат ГГГГ-ММ-ДД ЧЧ:ММ). Если не задано — акция длится 7 дней с первого показа баннера.
 */
if ( ! function_exists( 'besedka_get_promo_end' ) ) {
	function besedka_get_promo_end() {
		$value = trim( (string) get_theme_mod( 'besedka_promo_end', '' ) );
		if ( '' !== $value ) {
			try {
				$date = new DateTime( $value, wp_timezone() );
				return $date->getTimestamp();
			} catch ( Exception $e ) {
				// Некорректная дата — используем запасной вариант ниже.
			}
		}

		$fallback = (int) get_option( 'besedka_promo_end_default', 0 );
		if ( ! $fallback ) {
			$fallback = time() + 7 * DAY_IN_SECONDS;
			update_option( 'besedka_promo_end_default', $fallback );
		}
		return $fallback;
	}
}

if ( ! function_exists( 'besedka_customize_register' ) ) {
	function besedka_customize_register( $wp_customize ) {
		$wp_customize->add_section(
			'besedka_banners',
			array(
				'title'    => __( 'Баннеры', 'besedka' ),
				'priority' => 40,
			)
		);
		$wp_customize->add_section(
			'besedka_search',
			array(
				'title'    => __( 'Поиск', 'besedka' ),
				'priority' => 41,
			)
		);
		$wp_customize->add_setting(
			'besedka_search_hints',
			array(
				'default'           => "Bragoff\nHigh Spirits\nтурбо-дрожжи 48\nдля зерна\nдля фруктов\nдля новичка",
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);
		$wp_customize->add_control(
			'besedka_search_hints',
			array(
				'section'     => 'besedka_search',
				'label'       => __( 'Подсказки «Часто ищут»', 'besedka' ),
				'description' => __( 'По одной подсказке в строке (до 12). Пусто — блок не показывается.', 'besedka' ),
				'type'        => 'textarea',
			)
		);
		$wp_customize->add_setting(
			'besedka_promo_end',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'besedka_promo_end',
			array(
				'section'     => 'besedka_banners',
				'label'       => __( 'Окончание акции (таймер на баннере)', 'besedka' ),
				'description' => __( 'Формат: ГГГГ-ММ-ДД ЧЧ:ММ, например 2026-11-01 23:59. Пусто — 7 дней с первого показа.', 'besedka' ),
				'type'        => 'text',
			)
		);
	}
	add_action( 'customize_register', 'besedka_customize_register' );
}

/* ==========================================================================
   6.3 ГОТОВЫЕ НАБОРЫ (БЛОК НА ГЛАВНОЙ)
   ========================================================================== */

/**
 * Наборы: товары с метой _besedka_set_slot (1 — главный, 2–4 — обычные).
 *
 * @return WC_Product[] Массив slot => товар.
 */
if ( ! function_exists( 'besedka_get_sets' ) ) {
	function besedka_get_sets() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => 8,
				'meta_key'    => '_besedka_set_slot', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'     => 'meta_value_num',
				'order'       => 'ASC',
				'fields'      => 'ids',
			)
		);
		$sets = array();
		foreach ( $ids as $set_id ) {
			$product = wc_get_product( $set_id );
			if ( $product ) {
				$sets[ (int) get_post_meta( $set_id, '_besedka_set_slot', true ) ] = $product;
			}
		}
		return $sets;
	}
}

/**
 * Состав набора: массив [ product, qty, label, line ].
 */
if ( ! function_exists( 'besedka_get_set_items' ) ) {
	function besedka_get_set_items( $set_id ) {
		$items = array();
		foreach ( (array) get_post_meta( $set_id, '_besedka_set_items', true ) as $row ) {
			$item = isset( $row['id'] ) ? wc_get_product( (int) $row['id'] ) : null;
			if ( ! $item ) {
				continue;
			}
			$qty     = isset( $row['qty'] ) ? max( 1, (int) $row['qty'] ) : 1;
			$items[] = array(
				'product' => $item,
				'qty'     => $qty,
				'label'   => ! empty( $row['label'] ) ? $row['label'] : $item->get_name(),
				'line'    => (float) $item->get_price() * $qty,
			);
		}
		return $items;
	}
}

/**
 * Изображение набора: готовая картинка набора, а если её нет — CSS-коллаж из фото состава.
 */
if ( ! function_exists( 'besedka_render_set_image' ) ) {
	function besedka_render_set_image( $set, $items ) {
		if ( $set->get_image_id() ) {
			echo wp_get_attachment_image( $set->get_image_id(), 'besedka-gallery', false, array( 'class' => 'product-card__image', 'loading' => 'lazy' ) );
			return;
		}
		$roles = array( 'center', 'left', 'right' );
		$n     = 0;
		echo '<span class="set-collage">';
		foreach ( $items as $item ) {
			$image_id = $item['product']->get_image_id();
			if ( ! $image_id || $n >= 3 ) {
				continue;
			}
			printf(
				'<img class="set-collage__img set-collage__img--%1$s" src="%2$s" alt="%3$s" loading="lazy">',
				esc_attr( $roles[ $n ] ),
				esc_url( wp_get_attachment_image_url( $image_id, 'besedka-card' ) ),
				esc_attr( $item['product']->get_name() )
			);
			$n++;
		}
		echo '</span>';
	}
}

if ( ! function_exists( 'besedka_render_set_favorite' ) ) {
	function besedka_render_set_favorite( $id ) {
		?>
		<button type="button" class="product-card__favorite" data-favorite-btn data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Добавить в избранное', 'besedka' ); ?>">
			<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
		</button>
		<?php
	}
}

/**
 * Обычная карточка набора (.product-card.set-card).
 */
if ( ! function_exists( 'besedka_render_set_card' ) ) {
	function besedka_render_set_card( $set, $slot ) {
		$id      = $set->get_id();
		$items   = besedka_get_set_items( $id );
		$regular = (float) $set->get_regular_price();
		$price   = (float) $set->get_price();
		$saving  = max( 0, $regular - $price );
		$percent = $regular > 0 ? round( $saving / $regular * 100 ) : 0;
		$labels  = array();
		foreach ( $items as $item ) {
			$labels[] = $item['label'];
		}
		?>
		<article class="product-card set-card set-card--slot-<?php echo esc_attr( $slot ); ?><?php echo in_array( (int) $slot, array( 3, 4 ), true ) ? ' set-card--wide' : ''; ?>" data-product-card data-product-id="<?php echo esc_attr( $id ); ?>">
			<a class="product-card__image-wrap" href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>">
				<?php besedka_render_set_image( $set, $items ); ?>
				<div class="product-card__badges">
					<span class="product-card__badge"><?php esc_html_e( 'Набор', 'besedka' ); ?></span>
					<?php if ( get_post_meta( $id, '_besedka_is_hit', true ) ) : ?>
						<span class="product-card__badge product-card__badge--hit"><?php esc_html_e( 'Хит', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $percent ) : ?>
						<span class="product-card__badge product-card__badge--sale"><?php echo esc_html( '−' . $percent . '%' ); ?></span>
					<?php endif; ?>
				</div>
			</a>

			<?php besedka_render_set_favorite( $id ); ?>

			<div class="product-card__body">
				<h3 class="product-card__title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $set->get_name() ); ?></a></h3>
				<p class="set-card__items"><?php echo esc_html( implode( ' · ', $labels ) ); ?></p>
				<div class="product-card__meta">
					<div class="product-card__prices">
						<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
						<?php if ( $saving ) : ?>
							<span class="product-card__price-old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $saving ) : ?>
						<span class="set-card__saving"><?php echo esc_html( sprintf( __( 'Выгода %s ₽', 'besedka' ), number_format_i18n( $saving, 0 ) ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="product-card__footer">
				<?php besedka_add_to_cart_button( $set, __( 'Набор в корзину', 'besedka' ), 'btn btn--primary btn--full product-card__btn-order' ); ?>
			</div>
		</article>
		<?php
	}
}

/**
 * Главная карточка набора (.set-featured): 2 колонки сетки, фото слева, состав справа.
 */
if ( ! function_exists( 'besedka_render_set_featured' ) ) {
	function besedka_render_set_featured( $set ) {
		$id      = $set->get_id();
		$items   = besedka_get_set_items( $id );
		$regular = (float) $set->get_regular_price();
		$price   = (float) $set->get_price();
		$saving  = max( 0, $regular - $price );
		$percent = $regular > 0 ? round( $saving / $regular * 100 ) : 0;
		?>
		<article class="set-featured" data-product-card data-product-id="<?php echo esc_attr( $id ); ?>">
			<a class="set-featured__image-wrap" href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>">
				<?php besedka_render_set_image( $set, $items ); ?>
				<div class="product-card__badges">
					<span class="product-card__badge"><?php esc_html_e( 'Набор', 'besedka' ); ?></span>
					<?php if ( get_post_meta( $id, '_besedka_is_hit', true ) ) : ?>
						<span class="product-card__badge product-card__badge--hit"><?php esc_html_e( 'Хит', 'besedka' ); ?></span>
					<?php endif; ?>
					<?php if ( $percent ) : ?>
						<span class="product-card__badge product-card__badge--sale"><?php echo esc_html( '−' . $percent . '%' ); ?></span>
					<?php endif; ?>
				</div>
			</a>

			<?php besedka_render_set_favorite( $id ); ?>

			<div class="set-featured__body">
				<h3 class="set-featured__title"><?php echo esc_html( $set->get_name() ); ?></h3>

				<ul class="set-featured__items">
					<?php foreach ( $items as $item ) :
						$thumb = $item['product']->get_image_id() ? wp_get_attachment_image_url( $item['product']->get_image_id(), 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' );
						?>
						<li>
							<a class="set-featured__item" href="<?php echo esc_url( get_permalink( $item['product']->get_id() ) ); ?>" data-quickview-trigger data-product-id="<?php echo esc_attr( $item['product']->get_id() ); ?>">
								<span class="set-featured__thumb"><img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy"></span>
								<span class="set-featured__name"><?php echo esc_html( $item['product']->get_name() ); ?><?php if ( $item['qty'] > 1 ) : ?> <span class="set-featured__qty">× <?php echo esc_html( $item['qty'] ); ?></span><?php endif; ?></span>
								<span class="set-featured__line"><?php echo wp_kses_post( wc_price( $item['line'] ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="set-featured__price-row">
					<div class="product-card__prices set-featured__prices">
						<span class="product-card__price-current"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
						<?php if ( $saving ) : ?>
							<span class="product-card__price-old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $saving ) : ?>
						<span class="set-card__saving"><?php echo esc_html( sprintf( __( 'Выгода %s ₽', 'besedka' ), number_format_i18n( $saving, 0 ) ) ); ?></span>
					<?php endif; ?>
				</div>

				<div class="set-featured__actions">
					<?php besedka_add_to_cart_button( $set, __( 'Набор в корзину', 'besedka' ), 'btn btn--primary' ); ?>
					<button type="button" class="btn btn--outline" data-quickview-trigger data-product-id="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Подробнее', 'besedka' ); ?></button>
				</div>
			</div>
		</article>
		<?php
	}
}

/**
 * Секция «Готовые наборы»: слева intro, справа карусель из наборов (слайд — карточка набора).
 */
if ( ! function_exists( 'besedka_render_sets_section' ) ) {
	function besedka_render_sets_section() {
		$sets = besedka_get_sets();
		if ( empty( $sets ) ) {
			return;
		}
		$check = '<span class="sets__check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>';
		?>
		<section class="section section--soft sets">
			<div class="container">
				<div class="sets__grid">

					<div class="section__intro sets__intro">
						<h2 class="section__title"><?php esc_html_e( 'Готовые наборы', 'besedka' ); ?></h2>
						<p class="section__subtitle"><?php esc_html_e( 'Всё для одного рецепта — дешевле, чем по отдельности', 'besedka' ); ?></p>
						<a class="section__link-all" href="<?php echo esc_url( home_url( '/product-category/nabory/' ) ); ?>"><?php esc_html_e( 'Все наборы', 'besedka' ); ?> &rarr;</a>
						<ul class="sets__perks">
							<li><?php echo $check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Подобрано под рецепт', 'besedka' ); ?></li>
							<li><?php echo $check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Выгоднее до 15%', 'besedka' ); ?></li>
							<li><?php echo $check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Инструкция в комплекте', 'besedka' ); ?></li>
						</ul>
					</div>

					<div class="sets__carousel" data-sets-carousel>
						<div class="sets__track" data-sets-track>
							<?php foreach ( $sets as $set ) : ?>
								<div class="sets__slide">
									<?php besedka_render_set_featured( $set ); ?>
								</div>
							<?php endforeach; ?>
						</div>

						<?php if ( count( $sets ) > 1 ) : ?>
							<div class="sets__nav">
								<button type="button" class="sets__arrow" data-sets-prev aria-label="<?php esc_attr_e( 'Предыдущий набор', 'besedka' ); ?>">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
								</button>
								<div class="sets__tabs" role="tablist">
									<?php foreach ( array_values( $sets ) as $i => $set ) :
										$tab = get_post_meta( $set->get_id(), '_besedka_set_tab', true );
										?>
										<button type="button" class="chip<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab" data-sets-tab="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $tab ? $tab : $set->get_name() ); ?></button>
									<?php endforeach; ?>
								</div>
								<button type="button" class="sets__arrow" data-sets-next aria-label="<?php esc_attr_e( 'Следующий набор', 'besedka' ); ?>">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
								</button>
							</div>
						<?php endif; ?>
					</div>

				</div>
			</div>
		</section>
		<?php
	}
}

/* ==========================================================================
   7. ПОДРОБНАЯ КАРТОЧКА ТОВАРА (ИСПОЛЬЗУЕТСЯ В single-product.php И В AJAX-МОДАЛКЕ)
   ========================================================================== */

/**
 * Блоки описания товара: описание, способ применения, состав, таблица брожения.
 * Каждый блок выводится только если для него есть данные.
 *
 * @param int    $id          ID товара.
 * @param string $description Описание.
 * @param string $composition Состав.
 * @param string $size        Фасовка.
 */
if ( ! function_exists( 'besedka_render_product_sections' ) ) {
	function besedka_render_product_sections( $id, $description, $composition, $size ) {
		$usage   = get_post_meta( $id, '_besedka_usage', true );
		$ferment = get_post_meta( $id, '_besedka_ferment', true );
		$usage   = is_array( $usage ) ? $usage : array();
		$ferment = is_array( $ferment ) ? $ferment : array();
		?>
		<div class="product-info">

			<?php if ( $description ) : ?>
				<section class="product-info__block">
					<h3 class="product-info__title"><?php esc_html_e( 'Описание:', 'besedka' ); ?></h3>
					<div class="product-info__text"><?php echo wp_kses_post( wpautop( $description ) ); ?></div>
				</section>
			<?php endif; ?>

			<?php if ( $usage ) : ?>
				<section class="product-info__block">
					<h3 class="product-info__title"><?php esc_html_e( 'Способ применения:', 'besedka' ); ?></h3>
					<?php
					$in_list = false;
					foreach ( $usage as $item ) {
						$type = isset( $item[0] ) ? $item[0] : 'text';
						$text = isset( $item[1] ) ? $item[1] : '';
						if ( 'step' === $type && ! $in_list ) {
							echo '<ul class="product-info__list">';
							$in_list = true;
						} elseif ( 'step' !== $type && $in_list ) {
							echo '</ul>';
							$in_list = false;
						}
						if ( 'step' === $type ) {
							echo '<li class="product-info__item">' . esc_html( $text ) . '</li>';
						} elseif ( 'note' === $type ) {
							echo '<p class="product-info__note">' . esc_html( $text ) . '</p>';
						} else {
							echo '<p class="product-info__text">' . esc_html( $text ) . '</p>';
						}
					}
					if ( $in_list ) {
						echo '</ul>';
					}
					?>
				</section>
			<?php endif; ?>

			<?php if ( $composition || $size ) : ?>
				<section class="product-info__block">
					<h3 class="product-info__title"><?php esc_html_e( 'Состав:', 'besedka' ); ?></h3>
					<?php if ( $composition ) : ?>
						<p class="product-info__text"><?php echo esc_html( $composition ); ?></p>
					<?php endif; ?>
					<?php if ( $size ) : ?>
						<p class="product-info__text"><?php esc_html_e( 'Фасовка:', 'besedka' ); ?> <?php echo esc_html( $size ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( $ferment ) : ?>
				<section class="product-info__block">
					<h3 class="product-info__title"><?php esc_html_e( 'Таблица брожения', 'besedka' ); ?></h3>
					<div class="product-info__table-wrap">
						<table class="product-info__table">
							<tbody>
								<?php foreach ( $ferment as $row ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
										<?php foreach ( (array) $row[1] as $cell ) : ?>
											<td><?php echo esc_html( $cell ); ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>
			<?php endif; ?>

		</div>
		<?php
	}
}

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
				<?php besedka_add_to_cart_button( $product, __( 'В корзину', 'besedka' ), 'btn btn--outline' ); ?>
				<a class="btn btn--primary" href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => $id, 'quantity' => 1 ), wc_get_checkout_url() ) ); ?>" data-buy-now rel="nofollow">
					<?php esc_html_e( 'Купить сейчас', 'besedka' ); ?>
				</a>
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

			<?php besedka_render_product_sections( $id, $description, $composition, $size ); ?>

			<p class="modal__disclaimer"><?php esc_html_e( 'Внешний вид упаковки может немного отличаться от изображения.', 'besedka' ); ?></p>

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

// Рецепты (тип записи, поля ACF, функции блока на главной).
require_once BESEDKA_DIR . '/inc/recipes.php';
