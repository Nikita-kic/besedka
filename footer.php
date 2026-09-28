<?php
/**
 * Футер сайта: 4 колонки, нижняя строка с копирайтом, политикой и способами оплаты.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

</main><!-- .site-main -->

<footer class="site-footer">
	<div class="container">
		<div class="site-footer__columns">

			<!-- Колонка 1: логотип, описание, время работы -->
			<div class="footer-col footer-col--about">
				<a class="footer-col__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php besedka_logo(); ?>
				</a>
				<p class="footer-col__desc">
					<?php echo esc_html( get_theme_mod( 'besedka_footer_desc', __( 'Товары для самогоноварения собственного производства с доставкой по России и СНГ.', 'besedka' ) ) ); ?>
				</p>
				<p class="footer-col__hours"><?php esc_html_e( 'Время работы: с 8:00 до 21:00', 'besedka' ); ?></p>
			</div>

			<!-- Колонка 2: информационное меню -->
			<div class="footer-col">
				<h3 class="footer-col__title"><?php esc_html_e( 'Информация', 'besedka' ); ?></h3>
				<ul class="footer-col__list">
					<?php
					if ( has_nav_menu( 'footer-info' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer-info',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'link_before'    => '<span class="footer-col__link">',
								'link_after'     => '</span>',
							)
						);
					} else {
						$info_links = array(
							'about'    => array( __( 'О нас', 'besedka' ), home_url( '/about/' ) ),
							'contacts' => array( __( 'Контакты', 'besedka' ), home_url( '/contacts/' ) ),
							'delivery' => array( __( 'Доставка и оплата', 'besedka' ), home_url( '/delivery/' ) ),
							'quality'  => array( __( 'Гарантия качества', 'besedka' ), home_url( '/quality/' ) ),
							'reviews'  => array( __( 'Отзывы', 'besedka' ), home_url( '/reviews/' ) ),
							'faq'      => array( __( 'Вопросы и ответы', 'besedka' ), home_url( '/faq/' ) ),
						);
						foreach ( $info_links as $link ) :
							?>
							<li><a class="footer-col__link" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a></li>
							<?php
						endforeach;
					}
					?>
				</ul>
			</div>

			<!-- Колонка 3: каталог -->
			<div class="footer-col">
				<h3 class="footer-col__title"><?php esc_html_e( 'Каталог', 'besedka' ); ?></h3>
				<ul class="footer-col__list">
					<?php
					if ( has_nav_menu( 'footer-catalog' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer-catalog',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'link_before'    => '<span class="footer-col__link">',
								'link_after'     => '</span>',
							)
						);
					} else {
						$catalog_links = array(
							'ugol'        => array( __( 'Уголь', 'besedka' ), home_url( '/product-category/ugol/' ) ),
							'kubiki'      => array( __( 'Дубовые кубики', 'besedka' ), home_url( '/product-category/kubiki/' ) ),
							'essencii'    => array( __( 'Эссенции', 'besedka' ), home_url( '/product-category/essencii/' ) ),
							'drozhzhi'    => array( __( 'Спиртовые дрожжи', 'besedka' ), home_url( '/product-category/drozhzhi/' ) ),
							'komplekt'    => array( __( 'Комплектующие', 'besedka' ), home_url( '/product-category/komplekt/' ) ),
							'bonifikator' => array( __( 'Бонификаторы', 'besedka' ), home_url( '/product-category/bonifikator/' ) ),
						);
						foreach ( $catalog_links as $link ) :
							?>
							<li><a class="footer-col__link" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a></li>
							<?php
						endforeach;
					}
					?>
				</ul>
			</div>

			<!-- Колонка 4: контакты и соцсети -->
			<div class="footer-col">
				<h3 class="footer-col__title"><?php esc_html_e( 'Контакты', 'besedka' ); ?></h3>
				<address>
					<span class="footer-col__contact"><?php echo esc_html( get_theme_mod( 'besedka_shop_address', 'г. Москва, Тарный проезд, д. 11с1' ) ); ?></span>
					<a class="footer-col__contact" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', get_theme_mod( 'besedka_shop_phone', '+74951234567' ) ) ); ?>">
						<?php echo esc_html( get_theme_mod( 'besedka_shop_phone', '+7 (495) 123-45-67' ) ); ?>
					</a>
					<a class="footer-col__contact" href="mailto:<?php echo esc_attr( get_theme_mod( 'besedka_shop_email', 'info@besedka.ru' ) ); ?>">
						<?php echo esc_html( get_theme_mod( 'besedka_shop_email', 'info@besedka.ru' ) ); ?>
					</a>
				</address>
				<div class="footer-col__social">
					<a class="footer-col__social-link" href="<?php echo esc_url( get_theme_mod( 'besedka_telegram_url', 'https://t.me/besedka' ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
						<svg viewBox="0 0 24 24"><path d="M9.4 15.5l-.4 5 1.8-1.8 3.6 2.6 4-19-19 7.4 5.4 2 12-7.4z"/></svg>
					</a>
					<a class="footer-col__social-link" href="<?php echo esc_url( get_theme_mod( 'besedka_whatsapp_url', 'https://wa.me/74951234567' ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
						<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2z"/></svg>
					</a>
					<a class="footer-col__social-link" href="<?php echo esc_url( get_theme_mod( 'besedka_vk_url', 'https://vk.com/besedka' ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="VK">
						<svg viewBox="0 0 24 24"><path d="M2 12c0-5 0-7.5 1.5-9S7 2 12 2s7.5 0 9 1.5S22 7 22 12s0 7.5-1.5 9S17 22 12 22s-7.5 0-9-1.5S2 17 2 12z"/></svg>
					</a>
				</div>
			</div>

		</div>

		<!-- Нижняя строка футера -->
		<div class="site-footer__bottom">
			<p class="site-footer__copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Все права защищены.', 'besedka' ); ?>
			</p>

			<nav class="site-footer__legal" aria-label="<?php esc_attr_e( 'Юридическая информация', 'besedka' ); ?>">
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Политика конфиденциальности', 'besedka' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Пользовательское соглашение', 'besedka' ); ?></a>
			</nav>

			<div class="site-footer__payments">
				<span class="visually-hidden"><?php esc_html_e( 'Способы оплаты:', 'besedka' ); ?></span>
				<svg width="40" height="22" viewBox="0 0 40 22" aria-label="Visa" role="img"><rect width="40" height="22" rx="4" fill="#fff" opacity="0.9"/><text x="20" y="15" font-size="9" font-weight="700" text-anchor="middle" fill="#1a1f71">VISA</text></svg>
				<svg width="40" height="22" viewBox="0 0 40 22" aria-label="Mastercard" role="img"><rect width="40" height="22" rx="4" fill="#fff" opacity="0.9"/><circle cx="17" cy="11" r="6" fill="#eb001b"/><circle cx="24" cy="11" r="6" fill="#f79e1b" opacity="0.85"/></svg>
				<svg width="40" height="22" viewBox="0 0 40 22" aria-label="МИР" role="img"><rect width="40" height="22" rx="4" fill="#fff" opacity="0.9"/><text x="20" y="15" font-size="8" font-weight="700" text-anchor="middle" fill="#0f754e">МИР</text></svg>
			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
