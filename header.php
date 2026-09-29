<?php
/**
 * Шапка сайта: верхний уровень (контакты, избранное, телефон)
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

				<!-- Ссылки "О нас" / "Контакты" -->
				<nav class="header-top__links" aria-label="<?php esc_attr_e( 'Информационное меню', 'besedka' ); ?>">
					<?php
					if ( has_nav_menu( 'top-links' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'top-links',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'link_before'    => '<span class="header-top__link">',
								'link_after'     => '</span>',
							)
						);
					} else {
						?>
						<a class="header-top__link" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'О нас', 'besedka' ); ?></a>
						<a class="header-top__link" href="<?php echo esc_url( home_url( '/contacts/' ) ); ?>"><?php esc_html_e( 'Контакты', 'besedka' ); ?></a>
						<?php
					}
					?>
				</nav>

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
