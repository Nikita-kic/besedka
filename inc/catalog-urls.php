<?php
/**
 * Адреса каталога: /catalog/, /catalog/{категория}/, /catalog/{категория}/{товар}/.
 * Ссылки через функции WP/WooCommerce, canonical, редиректы со старых адресов, хлебные крошки.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ССЫЛКИ
   ========================================================================== */

/** Адрес страницы магазина («Каталог»). */
if ( ! function_exists( 'besedka_catalog_url' ) ) {
	function besedka_catalog_url() {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/catalog/' );
	}
}

/** Адрес категории по ярлыку; если категории нет — /catalog/{ярлык}/. */
if ( ! function_exists( 'besedka_category_url' ) ) {
	function besedka_category_url( $slug ) {
		$link = taxonomy_exists( 'product_cat' ) ? get_term_link( $slug, 'product_cat' ) : null;
		if ( $link && ! is_wp_error( $link ) ) {
			return $link;
		}
		return trailingslashit( besedka_catalog_url() ) . $slug . '/';
	}
}

/* ==========================================================================
   CANONICAL: страницы с ?orderby=, ?filter_…, ?min_price= и т. п. — на чистый адрес
   ========================================================================== */
if ( ! function_exists( 'besedka_output_canonical' ) ) {
	function besedka_output_canonical() {
		if ( is_admin() ) {
			return;
		}
		$url = '';
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			$link = $term ? get_term_link( $term ) : '';
			$url  = is_wp_error( $link ) ? '' : $link;
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$url = besedka_catalog_url();
		}
		if ( ! $url ) {
			return;
		}
		$paged = (int) get_query_var( 'paged' );
		if ( $paged > 1 ) {
			$url = trailingslashit( $url ) . 'page/' . $paged . '/';
		}
		remove_action( 'wp_head', 'rel_canonical' );
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	add_action( 'wp_head', 'besedka_output_canonical', 1 );
}

/* ==========================================================================
   НЕ УГАДЫВАТЬ АДРЕС ПО ЯРЛЫКУ: на несуществующий адрес — 404, а не редирект на вложение
   (ярлык вложения «kubiki» совпадал с ярлыком категории и открывал файл картинки)
   ========================================================================== */
add_filter( 'pre_redirect_guess_404_permalink', '__return_false' );

/* ==========================================================================
   301-РЕДИРЕКТЫ
   ========================================================================== */
/** Товар по ярлыку: текущему или прежнему (_wp_old_slug после смены ярлыка). */
if ( ! function_exists( 'besedka_find_product_by_slug' ) ) {
	function besedka_find_product_by_slug( $slug ) {
		global $wpdb;
		$post = get_page_by_path( rawurldecode( $slug ), OBJECT, 'product' );
		if ( $post ) {
			return $post;
		}
		$old = strtolower( rawurlencode( rawurldecode( $slug ) ) );
		$id  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_old_slug' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_value = %s LIMIT 1",
				$old
			)
		);
		return $id ? get_post( $id ) : null;
	}
}

if ( ! function_exists( 'besedka_legacy_redirects' ) ) {
	function besedka_legacy_redirects() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		if ( 'GET' !== $method || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$uri   = wp_unslash( $_SERVER['REQUEST_URI'] );
		$path  = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
		$base  = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		$rel   = trim( substr( $path, strlen( $base ) ), '/' );
		$tail  = $query ? '?' . $query : '';
		$target = '';

		if ( 'shop' === $rel ) {
			// /shop/ → /catalog/
			$target = besedka_catalog_url();
		} elseif ( preg_match( '#^product-category/(.+)$#', $rel, $m ) ) {
			// /product-category/{slug}/ → /catalog/{slug}/
			$structure = function_exists( 'wc_get_permalink_structure' ) ? wc_get_permalink_structure() : array();
			$cat_base  = ! empty( $structure['category_rewrite_slug'] ) ? $structure['category_rewrite_slug'] : 'catalog';
			$target    = home_url( '/' . $cat_base . '/' . $m[1] . '/' );
		} elseif ( preg_match( '#^product/([^/]+)$#', $rel, $m ) ) {
			// /product/{slug}/ → /catalog/{категория}/{slug}/
			$post = besedka_find_product_by_slug( $m[1] );
			if ( $post ) {
				$target = get_permalink( $post );
			}
		} elseif ( is_404() && preg_match( '#^[^/]+/[^/]+/([^/]+)$#', $rel, $m ) ) {
			// /catalog/{категория}/{старый-ярлык}/ → актуальный адрес товара.
			$post = besedka_find_product_by_slug( $m[1] );
			if ( $post && trailingslashit( get_permalink( $post ) ) !== trailingslashit( home_url( '/' . $rel ) ) ) {
				$target = get_permalink( $post );
			}
		} elseif ( '' !== $path && '/' !== substr( $path, -1 ) && get_option( 'permalink_structure' ) ) {
			// Единый формат со слешем на конце: 301 с версии без слеша.
			$last = basename( $path );
			if ( false === strpos( $last, '.' ) && ! preg_match( '#^/?(wp-admin|wp-login|wp-json|wp-content|wp-includes)#', ltrim( substr( $path, strlen( $base ) ), '/' ) ) ) {
				$target = home_url( '/' . $rel . '/' );
			}
		}

		if ( $target ) {
			wp_safe_redirect( $target . $tail, 301 );
			exit;
		}
	}
	add_action( 'template_redirect', 'besedka_legacy_redirects', 1 );
}

/* ==========================================================================
   ХЛЕБНЫЕ КРОШКИ + BreadcrumbList
   ========================================================================== */
if ( ! function_exists( 'besedka_get_breadcrumbs' ) ) {
	/** @return array[] Элементы [ название, адрес ]. */
	function besedka_get_breadcrumbs() {
		$crumbs   = array( array( __( 'Главная', 'besedka' ), home_url( '/' ) ) );
		$catalog  = besedka_catalog_url();

		if ( function_exists( 'is_product' ) && is_product() ) {
			$crumbs[] = array( __( 'Каталог', 'besedka' ), $catalog );
			$terms    = get_the_terms( get_the_ID(), 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term     = reset( $terms );
				$link     = get_term_link( $term );
				$crumbs[] = array( $term->name, is_wp_error( $link ) ? '' : $link );
			}
			$crumbs[] = array( get_the_title(), get_permalink() );
		} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$crumbs[] = array( __( 'Каталог', 'besedka' ), $catalog );
			$term     = get_queried_object();
			if ( $term instanceof WP_Term ) {
				foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, 'product_cat' );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$crumbs[] = array( $ancestor->name, get_term_link( $ancestor ) );
					}
				}
				$link     = get_term_link( $term );
				$crumbs[] = array( $term->name, is_wp_error( $link ) ? '' : $link );
			}
		} else {
			$crumbs[] = array( __( 'Каталог', 'besedka' ), $catalog );
		}
		return $crumbs;
	}
}

if ( ! function_exists( 'besedka_render_breadcrumbs' ) ) {
	function besedka_render_breadcrumbs() {
		$crumbs = besedka_get_breadcrumbs();
		$last   = count( $crumbs ) - 1;
		$items  = array();
		?>
		<nav class="product-page__breadcrumbs" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'besedka' ); ?>">
			<?php foreach ( $crumbs as $i => $crumb ) : ?>
				<?php if ( $i === $last || empty( $crumb[1] ) ) : ?>
					<span aria-current="page"><?php echo esc_html( $crumb[0] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $crumb[1] ); ?>"><?php echo esc_html( $crumb[0] ); ?></a>
					<span>/</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<?php
		foreach ( $crumbs as $i => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb[0],
			);
			if ( ! empty( $crumb[1] ) ) {
				$item['item'] = $crumb[1];
			}
			$items[] = $item;
		}
		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
