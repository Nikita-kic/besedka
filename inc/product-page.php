<?php
/**
 * Страница товара «Витрина»: данные товара, отзывы, приём отзыва, микроразметка.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ДАННЫЕ ТОВАРА: способ применения и таблица брожения (админка: свои поля-повторители)
   ========================================================================== */

/** Шаги «Способа применения»: [ [ text, warning ], … ]. */
if ( ! function_exists( 'besedka_get_usage_steps' ) ) {
	function besedka_get_usage_steps( $product_id ) {
		$out = array();
		foreach ( (array) get_post_meta( $product_id, '_besedka_steps', true ) as $step ) {
			$text = isset( $step['text'] ) ? trim( (string) $step['text'] ) : '';
			if ( '' === $text ) {
				continue;
			}
			$out[] = array(
				'text'    => $text,
				'warning' => isset( $step['warning'] ) ? trim( (string) $step['warning'] ) : '',
			);
		}
		return $out;
	}
}

/** Таблица брожения: [ 'cols' => [ [ sugar, liquid, days ], … ], 'note' => '…' ]. Пусто — таблицы нет. */
if ( ! function_exists( 'besedka_get_ferment_table' ) ) {
	function besedka_get_ferment_table( $product_id ) {
		$cols = array();
		foreach ( (array) get_post_meta( $product_id, '_besedka_ferment_cols', true ) as $col ) {
			$item = array(
				'sugar'  => isset( $col['sugar'] ) ? trim( (string) $col['sugar'] ) : '',
				'liquid' => isset( $col['liquid'] ) ? trim( (string) $col['liquid'] ) : '',
				'days'   => isset( $col['days'] ) ? trim( (string) $col['days'] ) : '',
			);
			if ( '' !== $item['sugar'] || '' !== $item['liquid'] || '' !== $item['days'] ) {
				$cols[] = $item;
			}
		}
		return array(
			'cols' => $cols,
			'note' => trim( (string) get_post_meta( $product_id, '_besedka_ferment_note', true ) ),
		);
	}
}

if ( ! function_exists( 'besedka_metabox_repeater' ) ) {
	/** Повторитель строк в админке: $name — ключ поля, $columns — [ ключ => подпись ]. */
	function besedka_metabox_repeater( $name, $columns, $rows ) {
		$rows = $rows ? array_values( (array) $rows ) : array( array() );
		echo '<div class="besedka-repeater" data-name="' . esc_attr( $name ) . '">';
		echo '<div class="besedka-repeater__rows">';
		foreach ( $rows as $i => $row ) {
			besedka_metabox_repeater_row( $name, $columns, $row, $i );
		}
		echo '</div>';
		echo '<template class="besedka-repeater__tpl">';
		besedka_metabox_repeater_row( $name, $columns, array(), '__i__' );
		echo '</template>';
		echo '<p><button type="button" class="button besedka-repeater__add">' . esc_html__( 'Добавить строку', 'besedka' ) . '</button></p>';
		echo '</div>';
	}
}

if ( ! function_exists( 'besedka_metabox_repeater_row' ) ) {
	function besedka_metabox_repeater_row( $name, $columns, $row, $index ) {
		echo '<div class="besedka-repeater__row" style="display:flex;gap:8px;margin-bottom:8px;align-items:flex-start">';
		foreach ( $columns as $key => $label ) {
			printf(
				'<label style="flex:1">%1$s<br><input type="text" class="widefat" name="%2$s[%3$s][%4$s]" value="%5$s"></label>',
				esc_html( $label ),
				esc_attr( $name ),
				esc_attr( $index ),
				esc_attr( $key ),
				esc_attr( isset( $row[ $key ] ) ? $row[ $key ] : '' )
			);
		}
		echo '<button type="button" class="button besedka-repeater__remove" style="margin-top:18px" aria-label="' . esc_attr__( 'Удалить строку', 'besedka' ) . '">&times;</button>';
		echo '</div>';
	}
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'besedka_steps',
			__( 'Способ применения (шаги)', 'besedka' ),
			function ( $post ) {
				wp_nonce_field( 'besedka_product_fields', 'besedka_product_nonce' );
				echo '<p class="description">' . esc_html__( 'Каждый шаг — отдельная строка. «Предупреждение» выводится плашкой под шагом. Нет шагов — блок не показывается.', 'besedka' ) . '</p>';
				besedka_metabox_repeater(
					'besedka_steps',
					array(
						'text'    => __( 'Шаг', 'besedka' ),
						'warning' => __( 'Предупреждение (необязательно)', 'besedka' ),
					),
					get_post_meta( $post->ID, '_besedka_steps', true )
				);
			},
			'product',
			'normal',
			'default'
		);
		add_meta_box(
			'besedka_ferment',
			__( 'Таблица брожения', 'besedka' ),
			function ( $post ) {
				echo '<p class="description">' . esc_html__( 'Каждая строка — один столбец таблицы на сайте. Не заполнено — блок и таблица не показываются.', 'besedka' ) . '</p>';
				besedka_metabox_repeater(
					'besedka_ferment_cols',
					array(
						'sugar'  => __( 'Сахар (например, 5 кг)', 'besedka' ),
						'liquid' => __( 'Жидкость (например, 26 л)', 'besedka' ),
						'days'   => __( 'Дни (например, 2–4)', 'besedka' ),
					),
					get_post_meta( $post->ID, '_besedka_ferment_cols', true )
				);
				printf(
					'<p><label>%1$s<br><input type="text" class="widefat" name="besedka_ferment_note" value="%2$s"></label></p>',
					esc_html__( 'Подпись под таблицей (например, температура браги 24–30 °C)', 'besedka' ),
					esc_attr( get_post_meta( $post->ID, '_besedka_ferment_note', true ) )
				);
			},
			'product',
			'normal',
			'default'
		);
	}
);

add_action(
	'admin_footer',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		?>
		<script>
		document.addEventListener('click', function (e) {
			var box = e.target.closest('.besedka-repeater');
			if (!box) { return; }
			if (e.target.classList.contains('besedka-repeater__add')) {
				var rows = box.querySelector('.besedka-repeater__rows');
				var tpl = box.querySelector('.besedka-repeater__tpl').innerHTML.replace(/__i__/g, rows.children.length + Date.now());
				rows.insertAdjacentHTML('beforeend', tpl);
			}
			if (e.target.classList.contains('besedka-repeater__remove')) {
				var row = e.target.closest('.besedka-repeater__row');
				if (row.parentNode.children.length > 1) { row.remove(); } else { row.querySelectorAll('input').forEach(function (i) { i.value = ''; }); }
			}
		});
		</script>
		<?php
	}
);

add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['besedka_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['besedka_product_nonce'] ) ), 'besedka_product_fields' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$clean = function ( $rows, $keys ) {
			$out = array();
			foreach ( (array) $rows as $row ) {
				$item = array();
				foreach ( $keys as $key ) {
					$item[ $key ] = isset( $row[ $key ] ) ? sanitize_text_field( wp_unslash( $row[ $key ] ) ) : '';
				}
				if ( array_filter( $item ) ) {
					$out[] = $item;
				}
			}
			return $out;
		};
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		update_post_meta( $post_id, '_besedka_steps', $clean( isset( $_POST['besedka_steps'] ) ? $_POST['besedka_steps'] : array(), array( 'text', 'warning' ) ) );
		update_post_meta( $post_id, '_besedka_ferment_cols', $clean( isset( $_POST['besedka_ferment_cols'] ) ? $_POST['besedka_ferment_cols'] : array(), array( 'sugar', 'liquid', 'days' ) ) );
		update_post_meta( $post_id, '_besedka_ferment_note', isset( $_POST['besedka_ferment_note'] ) ? sanitize_text_field( wp_unslash( $_POST['besedka_ferment_note'] ) ) : '' );
		// phpcs:enable
	}
);

/* ==========================================================================
   АТРИБУТЫ: бренд, короткое название
   ========================================================================== */
if ( ! function_exists( 'besedka_product_brand' ) ) {
	/** Бренд товара (термин атрибута pa_brend) или null. */
	function besedka_product_brand( $product ) {
		if ( ! taxonomy_exists( 'pa_brend' ) ) {
			return null;
		}
		$terms = wc_get_product_terms( $product->get_id(), 'pa_brend' );
		return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	}
}

if ( ! function_exists( 'besedka_product_short_name' ) ) {
	/** Короткое название из атрибута «Короткое название»; нет атрибута — полное название (без обрезки). */
	function besedka_product_short_name( $product ) {
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_object( $attribute ) ) {
				continue;
			}
			$name = $attribute->get_name();
			if ( 'Короткое название' === wc_attribute_label( $name, $product ) ) {
				$value = trim( (string) $product->get_attribute( $name ) );
				if ( '' !== $value ) {
					return $value;
				}
			}
		}
		return $product->get_name();
	}
}

/** Товары той же категории и того же бренда: [ [ product, label, is_current ], … ]; меньше двух — пусто. */
if ( ! function_exists( 'besedka_brand_siblings' ) ) {
	function besedka_brand_siblings( $product ) {
		$brand = besedka_product_brand( $product );
		$cats  = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! $brand || ! $cats || is_wp_error( $cats ) ) {
			return array();
		}
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'orderby'        => 'menu_order title', // Порядок задаётся полем «Порядок» товара, при равенстве — по названию.
				'order'          => 'ASC',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => reset( $cats )->term_id,
					),
					array(
						'taxonomy' => 'pa_brend',
						'field'    => 'term_id',
						'terms'    => $brand->term_id,
					),
				),
			)
		);
		if ( count( $ids ) < 2 ) {
			return array();
		}
		$list = array();
		foreach ( $ids as $sibling_id ) {
			$sibling = wc_get_product( $sibling_id );
			if ( $sibling ) {
				$list[] = array( $sibling, besedka_product_short_name( $sibling ), $sibling_id === $product->get_id() );
			}
		}
		return $list;
	}
}

/** Бейджи над названием: [ [ класс-модификатор, подпись ], … ]. */
if ( ! function_exists( 'besedka_product_badges' ) ) {
	function besedka_product_badges( $product ) {
		$badges = array();
		$id     = $product->get_id();
		if ( get_post_meta( $id, '_besedka_is_hit', true ) ) {
			$badges[] = array( 'hit', __( 'Хит продаж', 'besedka' ) );
		}
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $product->is_on_sale() && $regular > 0 && $sale > 0 && $sale < $regular ) {
			$badges[] = array( 'sale', '−' . round( ( $regular - $sale ) / $regular * 100 ) . '%' );
		}
		if ( get_post_meta( $id, '_besedka_is_new', true ) ) {
			$badges[] = array( 'new', __( 'Новинка', 'besedka' ) );
		}
		return $badges;
	}
}

/** Характеристики: [ [ название, значение ], … ] из видимых атрибутов, кроме Типа, Бренда и Короткого названия. */
if ( ! function_exists( 'besedka_product_specs' ) ) {
	function besedka_product_specs( $product ) {
		$rows = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_object( $attribute ) || ! $attribute->get_visible() ) {
				continue;
			}
			$name  = $attribute->get_name();
			$label = wc_attribute_label( $name, $product );
			if ( in_array( $name, array( 'pa_tip', 'pa_brend' ), true ) || 'Короткое название' === $label ) {
				continue;
			}
			$value = trim( wp_strip_all_tags( (string) $product->get_attribute( $name ) ) );
			if ( '' !== $value ) {
				$rows[] = array( $label, $value );
			}
		}
		return $rows;
	}
}

/* ==========================================================================
   ОТЗЫВЫ (нативные комментарии WooCommerce)
   ========================================================================== */
if ( ! function_exists( 'besedka_ru_date' ) ) {
	function besedka_ru_date( $mysql_date ) {
		$months = array( '', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
		$time   = strtotime( $mysql_date );
		return gmdate( 'j', $time ) . ' ' . $months[ (int) gmdate( 'n', $time ) ] . ' ' . gmdate( 'Y', $time );
	}
}

if ( ! function_exists( 'besedka_get_product_reviews' ) ) {
	/** Одобренные отзывы (без ответов), новые первыми. */
	function besedka_get_product_reviews( $product_id ) {
		return get_comments(
			array(
				'post_id'  => $product_id,
				'status'   => 'approve',
				'type__in' => array( 'review', 'comment' ),
				'parent'   => 0,
				'orderby'  => 'comment_date_gmt',
				'order'    => 'DESC',
			)
		);
	}
}

if ( ! function_exists( 'besedka_review_shop_reply' ) ) {
	/** Ответ магазина: одобренный дочерний комментарий администратора. */
	function besedka_review_shop_reply( $comment ) {
		$replies = get_comments(
			array(
				'parent' => $comment->comment_ID,
				'status' => 'approve',
				'number' => 5,
			)
		);
		foreach ( $replies as $reply ) {
			if ( $reply->user_id && ( user_can( $reply->user_id, 'manage_woocommerce' ) || user_can( $reply->user_id, 'manage_options' ) ) ) {
				return $reply;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'besedka_rating_label' ) ) {
	function besedka_rating_label( $rating ) {
		$labels = array( 1 => 'Плохо', 2 => 'Так себе', 3 => 'Нормально', 4 => 'Хорошо', 5 => 'Отлично' );
		return isset( $labels[ $rating ] ) ? $labels[ $rating ] : '';
	}
}

/** Приём отзыва из модалки. Отзыв уходит на модерацию (настройка WordPress «комментарий должен быть одобрен»). */
if ( ! function_exists( 'besedka_ajax_submit_review' ) ) {
	function besedka_ajax_submit_review() {
		check_ajax_referer( 'besedka_review', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product || ! comments_open( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Отзывы к этому товару закрыты', 'besedka' ) ) );
		}

		$rating  = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$city    = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$pros    = isset( $_POST['pros'] ) ? sanitize_text_field( wp_unslash( $_POST['pros'] ) ) : '';
		$cons    = isset( $_POST['cons'] ) ? sanitize_text_field( wp_unslash( $_POST['cons'] ) ) : '';
		$consent = ! empty( $_POST['consent'] );

		$errors = array();
		if ( $rating < 1 || $rating > 5 ) {
			$errors['rating'] = __( 'Поставьте оценку', 'besedka' );
		}
		if ( '' === trim( $content ) ) {
			$errors['content'] = __( 'Расскажите, как всё прошло', 'besedka' );
		}
		if ( '' === trim( $name ) ) {
			$errors['name'] = __( 'Укажите имя', 'besedka' );
		}
		if ( ! $consent ) {
			$errors['consent'] = __( 'Нужно ваше согласие', 'besedka' );
		}
		if ( $errors ) {
			wp_send_json_error( array( 'errors' => $errors ) );
		}

		$user = wp_get_current_user();
		$data = array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $name,
			'comment_author_email' => $user->exists() ? $user->user_email : '',
			'comment_content'      => $content,
			'comment_type'         => 'review',
			'comment_parent'       => 0,
			'user_id'              => $user->exists() ? $user->ID : 0,
		);

		$_POST['rating'] = $rating; // WooCommerce сохраняет оценку из $_POST['rating'] на хуке comment_post.
		$comment_id      = wp_new_comment( wp_slash( $data ), true );
		if ( is_wp_error( $comment_id ) ) {
			wp_send_json_error( array( 'message' => $comment_id->get_error_message() ) );
		}

		if ( ! get_comment_meta( $comment_id, 'rating', true ) ) {
			add_comment_meta( $comment_id, 'rating', $rating, true );
		}
		foreach ( array( 'besedka_pros' => $pros, 'besedka_cons' => $cons, 'besedka_city' => $city ) as $key => $value ) {
			if ( '' !== $value ) {
				add_comment_meta( $comment_id, $key, $value, true );
			}
		}

		wp_send_json_success();
	}
	add_action( 'wp_ajax_besedka_submit_review', 'besedka_ajax_submit_review' );
	add_action( 'wp_ajax_nopriv_besedka_submit_review', 'besedka_ajax_submit_review' );
}

/* ==========================================================================
   МИКРОРАЗМЕТКА Product + AggregateRating + Review
   ========================================================================== */
if ( ! function_exists( 'besedka_render_product_schema' ) ) {
	function besedka_render_product_schema( $product, $reviews ) {
		$images = array();
		foreach ( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) as $image_id ) {
			$images[] = wp_get_attachment_image_url( $image_id, 'full' );
		}
		$brand  = besedka_product_brand( $product );
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
			'sku'         => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
			'url'         => get_permalink( $product->get_id() ),
			'offers'      => array(
				'@type'         => 'Offer',
				'url'           => get_permalink( $product->get_id() ),
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => wc_format_decimal( $product->get_price(), 2 ),
				'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			),
		);
		if ( $images ) {
			$schema['image'] = $images;
		}
		if ( $brand ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand->name,
			);
		}
		$count = count( $reviews );
		if ( $count > 0 ) {
			$sum = 0;
			$out = array();
			foreach ( $reviews as $review ) {
				$rating = (int) get_comment_meta( $review->comment_ID, 'rating', true );
				$sum   += $rating;
				if ( count( $out ) < 10 ) {
					$out[] = array(
						'@type'         => 'Review',
						'author'        => array(
							'@type' => 'Person',
							'name'  => $review->comment_author,
						),
						'datePublished' => gmdate( 'c', strtotime( $review->comment_date_gmt . ' UTC' ) ),
						'reviewBody'    => $review->comment_content,
						'reviewRating'  => array(
							'@type'       => 'Rating',
							'ratingValue' => $rating,
							'bestRating'  => 5,
						),
					);
				}
			}
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round( $sum / $count, 1 ),
				'reviewCount' => $count,
			);
			$schema['review']          = $out;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ==========================================================================
   «С ЭТИМ ПОКУПАЮТ»: компактная карточка
   ========================================================================== */
if ( ! function_exists( 'besedka_render_cross_card' ) ) {
	function besedka_render_cross_card( $product ) {
		$cats  = get_the_terms( $product->get_id(), 'product_cat' );
		$cat   = ( $cats && ! is_wp_error( $cats ) ) ? reset( $cats )->name : '';
		$image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' );
		?>
		<article class="cross-card">
			<a class="cross-card__media" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" tabindex="-1" aria-hidden="true">
				<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
			</a>
			<div class="cross-card__body">
				<?php if ( $cat ) : ?>
					<span class="cross-card__cat"><?php echo esc_html( $cat ); ?></span>
				<?php endif; ?>
				<a class="cross-card__title" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				<div class="cross-card__foot">
					<span class="cross-card__price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
					<?php besedka_add_to_cart_button( $product, __( 'В корзину', 'besedka' ), 'btn btn--primary btn--small cross-card__buy' ); ?>
				</div>
			</div>
		</article>
		<?php
	}
}

/* ==========================================================================
   ВСПОМОГАТЕЛЬНОЕ ДЛЯ ШАБЛОНА
   ========================================================================== */

/** Набор для плашки: набор, в который входит товар; иначе набор категории/по умолчанию; для самих наборов — нет. */
if ( ! function_exists( 'besedka_product_set_id' ) ) {
	function besedka_product_set_id( $product ) {
		$id = $product->get_id();
		if ( get_post_meta( $id, '_besedka_set_slot', true ) ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => '_besedka_set_slot', // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'     => 'meta_value_num',
				'order'       => 'ASC',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_besedka_set_items',
						'value'   => '"id";i:' . $id . ';',
						'compare' => 'LIKE',
					),
				),
			)
		);
		if ( $ids ) {
			return (int) $ids[0];
		}
		$cats = get_the_terms( $id, 'product_cat' );
		$term = ( $cats && ! is_wp_error( $cats ) ) ? reset( $cats ) : null;
		return (int) besedka_resolve_catalog_set_id( $term );
	}
}

/** Счётчик «− 1 +». Все счётчики на странице синхронизируются скриптом (data-pp-*). */
if ( ! function_exists( 'besedka_render_qty' ) ) {
	function besedka_render_qty() {
		?>
		<div class="pp-qty">
			<button type="button" class="pp-qty__btn" data-pp-minus aria-label="<?php esc_attr_e( 'Уменьшить количество', 'besedka' ); ?>">−</button>
			<input class="pp-qty__value" type="number" value="1" min="1" readonly data-pp-qty aria-label="<?php esc_attr_e( 'Количество', 'besedka' ); ?>">
			<button type="button" class="pp-qty__btn" data-pp-plus aria-label="<?php esc_attr_e( 'Увеличить количество', 'besedka' ); ?>">+</button>
		</div>
		<?php
	}
}

/** Цена текстом, без HTML: «450 ₽». */
if ( ! function_exists( 'besedka_price_text' ) ) {
	function besedka_price_text( $price ) {
		return trim( html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' ) );
	}
}
