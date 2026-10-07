<?php
/**
 * Шапка сайта: верхний уровень (логотип, поиск, избранное, телефон)
 * и нижний уровень (меню категорий каталога).
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="visually-hidden" href="#main-content"><?php esc_html_e( 'Перейти к содержимому', 'besedka' ); ?></a>

<?php besedka_render_topbar(); ?>
<?php besedka_render_city_modal(); ?>

<header class="site-header">

	<!-- ===== Верхний уровень хедера ===== -->
	<div class="site-header__top">
		<div class="container">
			<div class="header-top">

				<!-- Кнопка мобильного меню -->
				<button type="button" class="header-top__burger" data-menu-toggle aria-label="<?php esc_attr_e( 'Открыть меню', 'besedka' ); ?>" aria-expanded="false"></button>

				<!-- Логотип -->
				<div class="header-top__logo">
					<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php besedka_logo(); ?>
					</a>
				</div>

				<!-- Поиск по названию и бренду -->
				<form class="header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-search>
					<span class="header-search__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l5 5"/></svg>
					</span>
					<input class="header-search__input" type="search" name="search" value="<?php echo isset( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Искать товары, например Bragoff', 'besedka' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Поиск товаров', 'besedka' ); ?>" data-search-input>
					<button class="header-search__btn" type="submit"><?php esc_html_e( 'Поиск', 'besedka' ); ?></button>
					<div class="header-search__results" data-search-results hidden></div>
				</form>

				<!-- Адрес и время работы -->
				<address class="header-top__address">
					<span class="header-top__address-line"><?php echo esc_html( get_theme_mod( 'besedka_shop_address', 'г. Москва, Тарный проезд, д. 11с1' ) ); ?></span>
					<span class="header-top__hours"><?php esc_html_e( 'Время работы: с 9:00 до 19:00', 'besedka' ); ?></span>
				</address>

				<!-- Действия: избранное, телефон, звонок -->
				<div class="header-top__actions">
					<a class="favorites-btn" href="<?php echo esc_url( home_url( '/favorites/' ) ); ?>" data-favorites-link>
						<span class="favorites-btn__icon">
							<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
							<span class="favorites-btn__count" data-favorites-count>0</span>
						</span>
						<span class="favorites-btn__label"><?php esc_html_e( 'Избранное', 'besedka' ); ?></span>
					</a>

					<div class="header-top__contact">
						<a class="btn btn--primary header-top__call-btn" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', get_theme_mod( 'besedka_shop_phone', '+74951234567' ) ) ); ?>">
							<span><?php esc_html_e( 'Позвонить', 'besedka' ); ?></span>
						</a>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- ===== Нижний уровень хедера: категории каталога ===== -->
	<div class="site-header__bottom" data-mobile-menu>
		<div class="container">
			<nav class="nav-categories" aria-label="<?php esc_attr_e( 'Категории каталога', 'besedka' ); ?>">
				<ul class="nav-categories__list">
					<?php
					if ( has_nav_menu( 'categories' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'categories',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'walker'         => new Besedka_Categories_Walker(),
							)
						);
					} else {
						$fallback_items = array(
							'ugol'        => __( 'Уголь', 'besedka' ),
							'kubiki'      => __( 'Дубовые кубики', 'besedka' ),
							'essencii'    => __( 'Эссенции', 'besedka' ),
							'drozhzhi'    => __( 'Спиртовые дрожжи', 'besedka' ),
							'komplekt'    => __( 'Комплектующие', 'besedka' ),
							'bonifikator' => __( 'Бонификаторы', 'besedka' ),
						);
						foreach ( $fallback_items as $slug => $label ) :
							?>
							<li class="nav-categories__item nav-categories__item--<?php echo esc_attr( $slug ); ?>">
								<a class="nav-categories__link" href="<?php echo esc_url( home_url( '/product-category/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a>
							</li>
							<?php
						endforeach;
					}
					?>
				</ul>
			</nav>
		</div>
	</div>

</header>

<main id="main-content" class="site-main">
