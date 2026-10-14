<?php
/**
 * Рецепты: тип записи recipe, поля ACF и функции вывода блока «Как сделать — пошагово».
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ТИП ЗАПИСИ И ПОЛЯ
   ========================================================================== */
if ( ! function_exists( 'besedka_register_recipe_post_type' ) ) {
	function besedka_register_recipe_post_type() {
		register_post_type(
			'recipe',
			array(
				'labels'        => array(
					'name'          => __( 'Рецепты', 'besedka' ),
					'singular_name' => __( 'Рецепт', 'besedka' ),
					'menu_name'     => __( 'Рецепты', 'besedka' ),
					'add_new'       => __( 'Добавить рецепт', 'besedka' ),
					'add_new_item'  => __( 'Добавить рецепт', 'besedka' ),
					'edit_item'     => __( 'Редактировать рецепт', 'besedka' ),
					'new_item'      => __( 'Новый рецепт', 'besedka' ),
					'view_item'     => __( 'Смотреть рецепт', 'besedka' ),
					'all_items'     => __( 'Все рецепты', 'besedka' ),
					'search_items'  => __( 'Искать рецепты', 'besedka' ),
					'not_found'     => __( 'Рецептов пока нет', 'besedka' ),
				),
				'public'        => true,
				'has_archive'   => 'recipes',
				'rewrite'       => array( 'slug' => 'recipes' ),
				'menu_icon'     => 'dashicons-book-alt',
				'menu_position' => 6,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			)
		);
	}
	add_action( 'init', 'besedka_register_recipe_post_type' );
}

add_action(
	'after_switch_theme',
	function () {
		besedka_register_recipe_post_type();
		flush_rewrite_rules();
	}
);

if ( ! function_exists( 'besedka_register_recipe_fields' ) ) {
	function besedka_register_recipe_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_besedka_recipe',
				'title'    => __( 'Параметры рецепта', 'besedka' ),
				'fields'   => array(
					array(
						'key'         => 'field_besedka_recipe_duration',
						'label'       => __( 'Длительность', 'besedka' ),
						'name'        => 'duration',
						'type'        => 'text',
						'placeholder' => __( 'Брожение 2–3 дня', 'besedka' ),
					),
					array(
						'key'        => 'field_besedka_recipe_difficulty',
						'label'      => __( 'Сложность', 'besedka' ),
						'name'       => 'difficulty',
						'type'       => 'select',
						'choices'    => array(
							'Легко'  => 'Легко',
							'Средне' => 'Средне',
							'Сложно' => 'Сложно',
						),
						'allow_null' => 1,
					),
					array(
						'key'   => 'field_besedka_recipe_steps_count',
						'label' => __( 'Количество шагов', 'besedka' ),
						'name'  => 'steps_count',
						'type'  => 'number',
						'min'   => 1,
					),
					array(
						'key'     => 'field_besedka_recipe_is_beginner',
						'label'   => __( 'Для новичка (бейдж)', 'besedka' ),
						'name'    => 'is_beginner',
						'type'    => 'true_false',
						'ui'      => 1,
						'message' => '',
					),
					array(
						'key'     => 'field_besedka_recipe_is_featured',
						'label'   => __( 'Главный рецепт блока', 'besedka' ),
						'name'    => 'is_featured',
						'type'    => 'true_false',
						'ui'      => 1,
						'message' => '',
					),
					array(
						'key'           => 'field_besedka_recipe_related_set',
						'label'         => __( 'Набор (товар WooCommerce)', 'besedka' ),
						'name'          => 'related_set',
						'type'          => 'post_object',
						'post_type'     => array( 'product' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'ui'            => 1,
					),
					array(
						'key'           => 'field_besedka_recipe_related_products',
						'label'         => __( 'Что понадобится (товары)', 'besedka' ),
						'name'          => 'related_products',
						'type'          => 'relationship',
						'post_type'     => array( 'product' ),
						'return_format' => 'id',
						'filters'       => array( 'search' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'recipe',
						),
					),
				),
			)
		);
	}
	add_action( 'acf/init', 'besedka_register_recipe_fields' );
}

/* ==========================================================================
   ХЕЛПЕРЫ
   ========================================================================== */

/** Значение поля рецепта: через ACF, а без него — из мета (ACF хранит поля как обычную мету). */
if ( ! function_exists( 'besedka_recipe_field' ) ) {
	function besedka_recipe_field( $name, $post_id ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $name, $post_id );
		}
		return get_post_meta( $post_id, $name, true );
	}
}

/** Склонение: 1 товар, 2 товара, 5 товаров. */
if ( ! function_exists( 'besedka_plural' ) ) {
	function besedka_plural( $n, $forms ) {
		$n = abs( (int) $n ) % 100;
		$d = $n % 10;
		if ( $n > 10 && $n < 20 ) {
			return $forms[2];
		}
		if ( 1 === $d ) {
			return $forms[0];
		}
		if ( $d >= 2 && $d <= 4 ) {
			return $forms[1];
		}
		return $forms[2];
	}
}

if ( ! function_exists( 'besedka_recipe_set_id' ) ) {
	function besedka_recipe_set_id( $recipe_id ) {
		$value = besedka_recipe_field( 'related_set', $recipe_id );
		if ( $value instanceof WP_Post ) {
			return $value->ID;
		}
		return absint( $value );
	}
}

if ( ! function_exists( 'besedka_recipe_kit_ids' ) ) {
	function besedka_recipe_kit_ids( $recipe_id ) {
		$value = besedka_recipe_field( 'related_products', $recipe_id );
		$ids   = array();
		foreach ( (array) $value as $item ) {
			$ids[] = $item instanceof WP_Post ? $item->ID : absint( $item );
		}
		return array_values( array_filter( $ids ) );
	}
}

/** Рецепты для главной: [ 'featured' => id, 'others' => [ id, id, id ] ] или пустой массив, если рецептов меньше 4. */
if ( ! function_exists( 'besedka_get_home_recipes' ) ) {
	function besedka_get_home_recipes() {
		$all = get_posts(
			array(
				'post_type'   => 'recipe',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'fields'      => 'ids',
			)
		);
		if ( count( $all ) < 4 ) {
			return array();
		}
		$featured = 0;
		foreach ( $all as $recipe_id ) {
			if ( besedka_recipe_field( 'is_featured', $recipe_id ) ) {
				$featured = $recipe_id;
				break;
			}
		}
		if ( ! $featured ) {
			$featured = $all[0];
		}
		$others = array_values( array_diff( $all, array( $featured ) ) );

		return array(
			'featured' => $featured,
			'others'   => array_slice( $others, 0, 3 ),
		);
	}
}

if ( ! function_exists( 'besedka_recipe_icon' ) ) {
	function besedka_recipe_icon( $type ) {
		$icons = array(
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'bars'  => '<path d="M6 20v-5M12 20V9M18 20v-9"/>',
			'flask' => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"/><path d="M7.5 15h9"/>',
		);
		$path = isset( $icons[ $type ] ) ? $icons[ $type ] : '';
		return '<svg class="recipes__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Стопка миниатюр товаров. $size — '' (36×46) или 'sm' (22×28). */
if ( ! function_exists( 'besedka_render_recipe_stack' ) ) {
	function besedka_render_recipe_stack( $product_ids, $max, $size = '' ) {
		$out = '';
		foreach ( array_slice( $product_ids, 0, $max ) as $product_id ) {
			$image_id = get_post_thumbnail_id( $product_id );
			if ( ! $image_id ) {
				continue;
			}
			$out .= '<span class="recipes-stack__item">' . wp_get_attachment_image( $image_id, 'besedka-thumb', false, array( 'alt' => '', 'loading' => 'lazy' ) ) . '</span>';
		}
		if ( '' === $out ) {
			return;
		}
		echo '<span class="recipes-stack' . ( $size ? ' recipes-stack--' . esc_attr( $size ) : '' ) . '" aria-hidden="true">' . $out . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Строка «Легко · 5 шагов». */
if ( ! function_exists( 'besedka_recipe_row_meta' ) ) {
	function besedka_recipe_row_meta( $recipe_id ) {
		$parts      = array();
		$difficulty = besedka_recipe_field( 'difficulty', $recipe_id );
		$steps      = (int) besedka_recipe_field( 'steps_count', $recipe_id );
		if ( $difficulty ) {
			$parts[] = $difficulty;
		}
		if ( $steps ) {
			$parts[] = $steps . ' ' . besedka_plural( $steps, array( 'шаг', 'шага', 'шагов' ) );
		}
		return implode( ' · ', $parts );
	}
}

/** Строка рецепта в списке — одна ссылка целиком. */
if ( ! function_exists( 'besedka_render_recipe_row' ) ) {
	function besedka_render_recipe_row( $recipe_id ) {
		$set_id   = besedka_recipe_set_id( $recipe_id );
		$set      = $set_id && function_exists( 'wc_get_product' ) ? wc_get_product( $set_id ) : null;
		$meta     = besedka_recipe_row_meta( $recipe_id );
		$kit_ids  = besedka_recipe_kit_ids( $recipe_id );
		?>
		<a class="recipe-row" href="<?php echo esc_url( get_permalink( $recipe_id ) ); ?>">
			<span class="recipe-row__thumb">
				<?php
				if ( has_post_thumbnail( $recipe_id ) ) {
					echo get_the_post_thumbnail( $recipe_id, 'besedka-card', array( 'loading' => 'lazy', 'alt' => get_the_title( $recipe_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</span>
			<span class="recipe-row__body">
				<?php if ( $meta ) : ?>
					<span class="recipe-row__meta"><?php echo esc_html( $meta ); ?></span>
				<?php endif; ?>
				<span class="recipe-row__title"><?php echo esc_html( get_the_title( $recipe_id ) ); ?></span>
				<?php if ( $set ) : ?>
					<span class="recipe-row__kit">
						<?php besedka_render_recipe_stack( $kit_ids, 4, 'sm' ); ?>
						<span class="recipe-row__price"><?php esc_html_e( 'Набор ·', 'besedka' ); ?> <?php echo wp_kses_post( wc_price( $set->get_price() ) ); ?></span>
					</span>
				<?php endif; ?>
			</span>
		</a>
		<?php
	}
}

/** Плашка «Понадобится: … · Читать рецепт · Набор · цена». */
if ( ! function_exists( 'besedka_render_recipe_kit' ) ) {
	function besedka_render_recipe_kit( $recipe_id, $show_read_link = true ) {
		$set_id  = besedka_recipe_set_id( $recipe_id );
		$set     = $set_id && function_exists( 'wc_get_product' ) ? wc_get_product( $set_id ) : null;
		$kit_ids = besedka_recipe_kit_ids( $recipe_id );
		if ( ! $set && empty( $kit_ids ) ) {
			return;
		}
		?>
		<div class="recipe-featured__kit">
			<?php if ( $kit_ids ) : ?>
				<div class="recipe-featured__needs">
					<span class="recipe-featured__needs-label"><?php esc_html_e( 'Понадобится:', 'besedka' ); ?></span>
					<?php besedka_render_recipe_stack( $kit_ids, 4 ); ?>
					<span class="recipe-featured__needs-count"><?php echo esc_html( count( $kit_ids ) . ' ' . besedka_plural( count( $kit_ids ), array( 'товар', 'товара', 'товаров' ) ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="recipe-featured__actions">
				<?php if ( $show_read_link ) : ?>
					<a class="recipe-featured__read" href="<?php echo esc_url( get_permalink( $recipe_id ) ); ?>"><?php esc_html_e( 'Читать рецепт', 'besedka' ); ?></a>
				<?php endif; ?>
				<?php if ( $set ) : ?>
					<button type="button" class="btn btn--primary recipe-featured__buy" data-add-to-cart data-product-id="<?php echo esc_attr( $set->get_id() ); ?>">
						<?php esc_html_e( 'Набор ·', 'besedka' ); ?> <?php echo wp_kses_post( wc_price( $set->get_price() ) ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
