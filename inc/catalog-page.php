<?php
/**
 * Страница категории / каталога: боковая панель, фильтры по атрибутам и цене, сортировка,
 * активные фильтры, плашка набора. Макет «Каталог, вариант 4 — Боковая панель».
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ПОЛЕ КАТЕГОРИИ «СВЯЗАННЫЙ НАБОР» (ACF → term meta related_set)
   ========================================================================== */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_besedka_category_set',
				'title'    => __( 'Набор для категории', 'besedka' ),
				'fields'   => array(
					array(
						'key'           => 'field_besedka_category_related_set',
						'label'         => __( 'Связанный набор', 'besedka' ),
						'name'          => 'related_set',
						'type'          => 'post_object',
						'instructions'  => __( 'Плашка «Готовый набор» под сеткой товаров категории. Не выбран — плашки нет.', 'besedka' ),
						'post_type'     => array( 'product' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'ui'            => 1,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'product_cat',
						),
					),
				),
			)
		);
	}
);

if ( ! function_exists( 'besedka_category_set_id' ) ) {
	function besedka_category_set_id( $term_id ) {
		$value = function_exists( 'get_field' ) ? get_field( 'related_set', 'product_cat_' . $term_id ) : get_term_meta( $term_id, 'related_set', true );
		return $value instanceof WP_Post ? $value->ID : absint( $value );
	}
}

/* ==========================================================================
   ПАРАМЕТРЫ: сортировка, атрибуты, цена
   ========================================================================== */
if ( ! function_exists( 'besedka_catalog_sort_options' ) ) {
	function besedka_catalog_sort_options() {
		return array(
			'popularity' => __( 'Популярные', 'besedka' ),
			'price'      => __( 'Сначала дешевле', 'besedka' ),
			'price-desc' => __( 'Сначала дороже', 'besedka' ),
			'date'       => __( 'Новинки', 'besedka' ),
		);
	}
}

/** Атрибуты для фильтров: таксономия => [ подпись, тип отображения ]. */
if ( ! function_exists( 'besedka_catalog_attributes' ) ) {
	function besedka_catalog_attributes() {
		return array(
			'pa_tip'   => array( __( 'Тип', 'besedka' ), 'checkbox' ),
			'pa_brend' => array( __( 'Бренд', 'besedka' ), 'checkbox' ),
			'pa_syre'  => array( __( 'Сырьё', 'besedka' ), 'chip' ),
		);
	}
}

if ( ! function_exists( 'besedka_catalog_param_name' ) ) {
	function besedka_catalog_param_name( $taxonomy ) {
		return 'filter_' . substr( $taxonomy, 3 );
	}
}

if ( ! function_exists( 'besedka_catalog_selected' ) ) {
	/** Выбранные значения атрибута из запроса (массив ярлыков). */
	function besedka_catalog_selected( $taxonomy, $src = null ) {
		$src = null === $src ? $_GET : $src; // phpcs:ignore WordPress.Security.NonceVerification
		$key = besedka_catalog_param_name( $taxonomy );
		if ( empty( $src[ $key ] ) ) {
			return array();
		}
		$raw = wp_unslash( $src[ $key ] );
		$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		return array_values( array_filter( array_map( 'sanitize_title', $raw ) ) );
	}
}

if ( ! function_exists( 'besedka_catalog_price_range' ) ) {
	/** [ min|null, max|null ] из запроса. */
	function besedka_catalog_price_range( $src = null ) {
		$src = null === $src ? $_GET : $src; // phpcs:ignore WordPress.Security.NonceVerification
		$min = isset( $src['min_price'] ) && '' !== $src['min_price'] ? max( 0, (float) wp_unslash( $src['min_price'] ) ) : null;
		$max = isset( $src['max_price'] ) && '' !== $src['max_price'] ? max( 0, (float) wp_unslash( $src['max_price'] ) ) : null;
		return array( $min, $max );
	}
}

if ( ! function_exists( 'besedka_catalog_orderby' ) ) {
	function besedka_catalog_orderby( $src = null ) {
		$src   = null === $src ? $_GET : $src; // phpcs:ignore WordPress.Security.NonceVerification
		$value = isset( $src['orderby'] ) ? sanitize_key( wp_unslash( $src['orderby'] ) ) : 'popularity';
		return array_key_exists( $value, besedka_catalog_sort_options() ) ? $value : 'popularity';
	}
}

/* ==========================================================================
   ЗАПРОС ТОВАРОВ
   ========================================================================== */
if ( ! function_exists( 'besedka_catalog_args' ) ) {
	function besedka_catalog_args( $term, $src = null, $paged = 1, $ids_only = false ) {
		$tax_query = array( 'relation' => 'AND' );
		if ( $term instanceof WP_Term ) {
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => $term->term_id,
				'include_children' => true,
			);
		}
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);
		foreach ( array_keys( besedka_catalog_attributes() ) as $taxonomy ) {
			$selected = besedka_catalog_selected( $taxonomy, $src );
			if ( $selected && taxonomy_exists( $taxonomy ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $selected,
					'operator' => 'IN',
				);
			}
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $ids_only ? 1 : 12,
			'paged'          => max( 1, (int) $paged ),
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( $ids_only ) {
			$args['fields'] = 'ids';
		}

		list( $min, $max ) = besedka_catalog_price_range( $src );
		if ( null !== $min || null !== $max ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_price',
					'value'   => array( null === $min ? 0 : $min, null === $max ? 99999999 : $max ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			);
		}

		switch ( besedka_catalog_orderby( $src ) ) {
			case 'price':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = array( 'meta_value_num' => 'ASC', 'date' => 'DESC' );
				break;
			case 'price-desc':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
				break;
			case 'date':
				$args['orderby'] = array( 'date' => 'DESC' );
				break;
			default:
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
		}
		return $args;
	}
}

/** AJAX: сколько товаров даст текущее состояние фильтров («Показать N товаров»). */
if ( ! function_exists( 'besedka_ajax_catalog_count' ) ) {
	function besedka_ajax_catalog_count() {
		$slug  = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$term  = $slug ? get_term_by( 'slug', $slug, 'product_cat' ) : null;
		$query = new WP_Query( besedka_catalog_args( $term ? $term : null, $_GET, 1, true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		wp_send_json_success(
			array(
				'count' => (int) $query->found_posts,
				'label' => besedka_catalog_show_label( (int) $query->found_posts ),
			)
		);
	}
	add_action( 'wp_ajax_besedka_catalog_count', 'besedka_ajax_catalog_count' );
	add_action( 'wp_ajax_nopriv_besedka_catalog_count', 'besedka_ajax_catalog_count' );
}

/* ==========================================================================
   ТЕКСТЫ И ССЫЛКИ
   ========================================================================== */
if ( ! function_exists( 'besedka_catalog_show_label' ) ) {
	function besedka_catalog_show_label( $count ) {
		return sprintf( __( 'Показать %1$d %2$s', 'besedka' ), $count, besedka_plural( $count, array( 'товар', 'товара', 'товаров' ) ) );
	}
}

if ( ! function_exists( 'besedka_catalog_found_text' ) ) {
	function besedka_catalog_found_text( $count ) {
		$tens = $count % 100;
		$one  = 1 === $count % 10 && 11 !== $tens;
		return sprintf( '%1$s %2$d %3$s', $one ? __( 'Найден', 'besedka' ) : __( 'Найдено', 'besedka' ), $count, besedka_plural( $count, array( 'товар', 'товара', 'товаров' ) ) );
	}
}

/** Текущие параметры каталога (атрибуты, цена, сортировка) как массив для add_query_arg(). */
if ( ! function_exists( 'besedka_catalog_current_params' ) ) {
	function besedka_catalog_current_params() {
		$params = array();
		foreach ( array_keys( besedka_catalog_attributes() ) as $taxonomy ) {
			$selected = besedka_catalog_selected( $taxonomy );
			if ( $selected ) {
				$params[ besedka_catalog_param_name( $taxonomy ) ] = $selected;
			}
		}
		list( $min, $max ) = besedka_catalog_price_range();
		if ( null !== $min ) {
			$params['min_price'] = $min;
		}
		if ( null !== $max ) {
			$params['max_price'] = $max;
		}
		if ( 'popularity' !== besedka_catalog_orderby() ) {
			$params['orderby'] = besedka_catalog_orderby();
		}
		return $params;
	}
}

/** Активные фильтры: [ подпись, адрес «убрать этот фильтр» ]. */
if ( ! function_exists( 'besedka_catalog_active_filters' ) ) {
	function besedka_catalog_active_filters( $base_url ) {
		$tags   = array();
		$params = besedka_catalog_current_params();

		foreach ( besedka_catalog_attributes() as $taxonomy => $config ) {
			$key = besedka_catalog_param_name( $taxonomy );
			foreach ( besedka_catalog_selected( $taxonomy ) as $slug ) {
				$term = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $slug, $taxonomy ) : null;
				$rest = $params;
				$rest[ $key ] = array_values( array_diff( $params[ $key ], array( $slug ) ) );
				if ( empty( $rest[ $key ] ) ) {
					unset( $rest[ $key ] );
				}
				$tags[] = array( $term ? $term->name : $slug, add_query_arg( $rest, $base_url ) );
			}
		}

		list( $min, $max ) = besedka_catalog_price_range();
		if ( null !== $min || null !== $max ) {
			$rest = $params;
			unset( $rest['min_price'], $rest['max_price'] );
			if ( null !== $min && null !== $max ) {
				$label = sprintf( __( '%1$s–%2$s ₽', 'besedka' ), number_format_i18n( $min, 0 ), number_format_i18n( $max, 0 ) );
			} elseif ( null !== $min ) {
				$label = sprintf( __( 'от %s ₽', 'besedka' ), number_format_i18n( $min, 0 ) );
			} else {
				$label = sprintf( __( 'до %s ₽', 'besedka' ), number_format_i18n( $max, 0 ) );
			}
			$tags[] = array( $label, add_query_arg( $rest, $base_url ) );
		}
		return $tags;
	}
}

/** Значения атрибута для боковой панели: только те, что есть в текущей категории (с количеством). */
if ( ! function_exists( 'besedka_catalog_attribute_values' ) ) {
	function besedka_catalog_attribute_values( $taxonomy, $term ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		$values = array();
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) ) as $attr_term ) {
			$tax_query = array(
				'relation' => 'AND',
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $attr_term->term_id,
				),
			);
			if ( $term instanceof WP_Term ) {
				$tax_query[] = array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => true,
				);
			}
			$query = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => 1,
					'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			if ( $query->found_posts > 0 ) {
				$values[] = array( $attr_term, (int) $query->found_posts );
			}
		}
		return $values;
	}
}

/* ==========================================================================
   БОКОВАЯ ПАНЕЛЬ
   ========================================================================== */
if ( ! function_exists( 'besedka_render_catalog_aside' ) ) {
	function besedka_render_catalog_aside( $term, $base_url, $found ) {
		$default_cat = (int) get_option( 'default_product_cat' );
		$sections    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'exclude'    => $default_cat ? array( $default_cat ) : array(),
				'orderby'    => 'name',
			)
		);
		list( $min, $max ) = besedka_catalog_price_range();
		$orderby            = besedka_catalog_orderby();
		?>
		<aside class="catalog-aside" data-catalog-aside aria-label="<?php esc_attr_e( 'Разделы и фильтры', 'besedka' ); ?>">
			<button type="button" class="catalog-aside__close" data-catalog-close aria-label="<?php esc_attr_e( 'Закрыть', 'besedka' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
			</button>

			<nav class="catalog-sections" aria-label="<?php esc_attr_e( 'Разделы каталога', 'besedka' ); ?>">
				<p class="eyebrow"><?php esc_html_e( 'Разделы', 'besedka' ); ?></p>
				<?php
				foreach ( $sections as $section ) :
					if ( is_wp_error( $section ) ) {
						continue;
					}
					$is_active = $term instanceof WP_Term && $term->term_id === $section->term_id;
					?>
					<a class="catalog-sections__link<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $section ) ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
						<span><?php echo esc_html( $section->name ); ?></span>
						<span class="catalog-sections__count"><?php echo esc_html( $section->count ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<form class="catalog-filters" id="catalog-filters" method="get" action="<?php echo esc_url( $base_url ); ?>" data-catalog-filters data-category="<?php echo esc_attr( $term instanceof WP_Term ? $term->slug : '' ); ?>">
				<?php if ( 'popularity' !== $orderby ) : ?>
					<input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
				<?php endif; ?>

				<?php
				foreach ( besedka_catalog_attributes() as $taxonomy => $config ) :
					$values = besedka_catalog_attribute_values( $taxonomy, $term );
					if ( empty( $values ) ) {
						continue; // Группа без значений в этой категории не выводится.
					}
					$selected = besedka_catalog_selected( $taxonomy );
					$name     = besedka_catalog_param_name( $taxonomy ) . '[]';
					?>
					<fieldset class="catalog-filters__group">
						<legend class="eyebrow"><?php echo esc_html( $config[0] ); ?></legend>
						<?php if ( 'chip' === $config[1] ) : ?>
							<div class="catalog-filters__chips">
								<?php foreach ( $values as $value ) : ?>
									<label class="chip catalog-filters__chip<?php echo in_array( $value[0]->slug, $selected, true ) ? ' is-active' : ''; ?>">
										<input class="visually-hidden" type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value[0]->slug ); ?>" <?php checked( in_array( $value[0]->slug, $selected, true ) ); ?>>
										<?php echo esc_html( $value[0]->name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<?php foreach ( $values as $value ) : ?>
								<label class="catalog-filters__check">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value[0]->slug ); ?>" <?php checked( in_array( $value[0]->slug, $selected, true ) ); ?>>
									<span><?php echo esc_html( $value[0]->name ); ?></span>
									<span class="catalog-filters__count"><?php echo esc_html( $value[1] ); ?></span>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</fieldset>
				<?php endforeach; ?>

				<fieldset class="catalog-filters__group">
					<legend class="eyebrow"><?php esc_html_e( 'Цена, ₽', 'besedka' ); ?></legend>
					<div class="catalog-filters__price">
						<input class="catalog-filters__input" type="number" name="min_price" min="0" inputmode="numeric" placeholder="<?php esc_attr_e( 'от', 'besedka' ); ?>" value="<?php echo null === $min ? '' : esc_attr( $min ); ?>" aria-label="<?php esc_attr_e( 'Цена от', 'besedka' ); ?>">
						<input class="catalog-filters__input" type="number" name="max_price" min="0" inputmode="numeric" placeholder="<?php esc_attr_e( 'до', 'besedka' ); ?>" value="<?php echo null === $max ? '' : esc_attr( $max ); ?>" aria-label="<?php esc_attr_e( 'Цена до', 'besedka' ); ?>">
					</div>
				</fieldset>

				<a class="btn btn--outline btn--small" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'Сбросить фильтры', 'besedka' ); ?></a>
			</form>

			<button type="submit" form="catalog-filters" class="btn btn--primary btn--full catalog-aside__apply" data-catalog-apply><?php echo esc_html( besedka_catalog_show_label( $found ) ); ?></button>
		</aside>
		<?php
	}
}

/* ==========================================================================
   ПЛАШКА НАБОРА ПОД СЕТКОЙ
   ========================================================================== */
if ( ! function_exists( 'besedka_render_category_set_banner' ) ) {
	function besedka_render_category_set_banner( $term ) {
		if ( ! ( $term instanceof WP_Term ) || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$set_id = besedka_category_set_id( $term->term_id );
		$set    = $set_id ? wc_get_product( $set_id ) : null;
		if ( ! $set || 'publish' !== get_post_status( $set_id ) ) {
			return;
		}
		$items   = besedka_get_set_items( $set_id );
		$regular = (float) $set->get_regular_price();
		$price   = (float) $set->get_price();
		$percent = ( $regular > 0 && $price < $regular ) ? round( ( $regular - $price ) / $regular * 100 ) : 0;
		$labels  = array();
		foreach ( $items as $item ) {
			$labels[] = $item['label'];
		}
		?>
		<a class="set-banner" href="<?php echo esc_url( get_permalink( $set_id ) ); ?>">
			<span class="set-banner__packs" aria-hidden="true">
				<?php
				$shown = 0;
				foreach ( $items as $item ) {
					$image_id = $item['product']->get_image_id();
					if ( ! $image_id || $shown >= 3 ) {
						continue;
					}
					echo wp_get_attachment_image( $image_id, 'besedka-thumb', false, array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					$shown++;
				}
				?>
			</span>
			<span class="set-banner__body">
				<span class="eyebrow"><?php echo esc_html( $percent ? sprintf( __( 'Готовый набор · −%d%%', 'besedka' ), $percent ) : __( 'Готовый набор', 'besedka' ) ); ?></span>
				<span class="set-banner__title"><?php echo esc_html( $set->get_name() ); ?></span>
				<span class="set-banner__items"><?php echo esc_html( implode( ' · ', $labels ) ); ?></span>
			</span>
			<span class="set-banner__buy">
				<span class="set-banner__prices">
					<span class="set-banner__price"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
					<?php if ( $percent ) : ?>
						<del class="set-banner__old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></del>
					<?php endif; ?>
				</span>
				<span class="set-banner__button"><?php esc_html_e( 'Смотреть набор', 'besedka' ); ?></span>
			</span>
		</a>
		<?php
	}
}
