<?php
/**
 * Данные для страницы товара: атрибуты (Тип, Бренд, Короткое название, Состав), шаги и таблица брожения
 * (из прежних полей), «С этим покупают». Идемпотентно.
 * Запуск: wp eval-file .devcontainer/seed-product-page.php --allow-root
 */

global $wpdb;

$find = function ( $title ) use ( $wpdb ) {
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status != 'trash' AND post_title = %s LIMIT 1",
			$title
		)
	);
};

/* ---- 1. Глобальные атрибуты «Тип» и «Бренд» ---- */
foreach ( array( 'tip' => 'Тип', 'brend' => 'Бренд' ) as $slug => $label ) {
	if ( ! taxonomy_exists( 'pa_' . $slug ) ) {
		$created = wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $created ) ) {
			WP_CLI::warning( "Атрибут «$label»: " . $created->get_error_message() );
			continue;
		}
		register_taxonomy( 'pa_' . $slug, 'product', array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
		WP_CLI::log( "Создан атрибут «$label» (pa_$slug)" );
	}
}

$ensure_term = function ( $name, $taxonomy ) {
	$term = get_term_by( 'name', $name, $taxonomy );
	if ( ! $term ) {
		$created = wp_insert_term( $name, $taxonomy );
		$term    = is_wp_error( $created ) ? null : get_term( $created['term_id'], $taxonomy );
	}
	return $term;
};

/* ---- 2. Товары-дрожжи: бренд, тип, короткое название, состав ---- */
$yeast = include get_theme_root() . '/besedka/inc/products-yeast.php';

$short_names = array(
	'Турбо-дрожжи High Spirits 100 SOTKA'    => '100 SOTKA',
	'Турбо-дрожжи High Spirits 200 2x SOTKA' => '200 2x SOTKA',
	'Турбо-дрожжи High Spirits 24 Extreme'   => '24 Extreme',
	'Турбо-дрожжи High Spirits 48 Classic'   => '48 Classic',
	'Турбо-дрожжи High Spirits F Fruit'      => 'Fruit',
	'Турбо-дрожжи High Spirits H3 Universal' => 'H3 Universal',
	'Турбо-дрожжи High Spirits W Whiskey'    => 'Whiskey',
	'Турбо-дрожжи High Spirits Мск Exclusive' => 'Мск Exclusive',
	'Дрожжи спиртовые Лёгкие Зерновые'       => 'Зерновые',
	'Дрожжи спиртовые Лёгкие Сахарные'       => 'Сахарные',
	'Дрожжи спиртовые Лёгкие Универсальные'  => 'Универсальные',
	'Дрожжи спиртовые Лёгкие Фруктовые'      => 'Фруктовые',
	'Турбо-дрожжи BRAGOFF 24 Quick'          => '24 Quick',
	'Турбо-дрожжи BRAGOFF 48 Universal'      => '48 Universal',
	'Турбо-дрожжи BRAGOFF 72 Gold Standart'  => '72 Gold Standard',
	'Турбо-дрожжи BRAGOFF Москва Original'   => 'Москва Original',
);

foreach ( $yeast as $row ) {
	$id = $find( $row['name'] );
	if ( ! $id ) {
		continue;
	}
	$product = wc_get_product( $id );
	$attrs   = array();

	foreach ( $product->get_attributes() as $key => $attribute ) {
		// Оставляем чужие атрибуты, наши пересоздаём ниже.
		if ( is_object( $attribute ) && ! in_array( $attribute->get_name(), array( 'pa_tip', 'pa_brend', 'Короткое название', 'Состав' ), true ) ) {
			$attrs[ $key ] = $attribute;
		}
	}

	$brand_name = 'Лёгкие' === $row['brand'] ? 'Лёгкие' : ( 'BRAGOFF' === $row['brand'] ? 'Bragoff' : 'High Spirits' );
	$type_name  = 0 === strpos( $row['name'], 'Турбо-дрожжи' ) ? 'Турбо-дрожжи' : 'Спиртовые дрожжи';
	$position   = 0;

	foreach ( array( 'pa_brend' => $brand_name, 'pa_tip' => $type_name ) as $taxonomy => $term_name ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$term = $ensure_term( $term_name, $taxonomy );
		if ( ! $term ) {
			continue;
		}
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term->term_id ) );
		$attribute->set_position( $position++ );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attrs[ $taxonomy ] = $attribute;
	}

	// Короткое название — служебный атрибут (на странице не выводится).
	$short = new WC_Product_Attribute();
	$short->set_id( 0 );
	$short->set_name( 'Короткое название' );
	$short->set_options( array( $short_names[ $row['name'] ] ) );
	$short->set_position( $position++ );
	$short->set_visible( false );
	$short->set_variation( false );
	$attrs[ sanitize_title( 'Короткое название' ) ] = $short;

	// Состав — видимая характеристика.
	if ( ! empty( $row['composition'] ) ) {
		$composition = new WC_Product_Attribute();
		$composition->set_id( 0 );
		$composition->set_name( 'Состав' );
		$composition->set_options( array( $row['composition'] ) );
		$composition->set_position( $position++ );
		$composition->set_visible( true );
		$composition->set_variation( false );
		$attrs[ sanitize_title( 'Состав' ) ] = $composition;
	}

	$product->set_attributes( $attrs );
	$product->save();

	/* ---- 3. Шаги и таблица брожения из прежних полей ---- */
	if ( ! get_post_meta( $id, '_besedka_steps', true ) ) {
		$steps = array();
		foreach ( (array) get_post_meta( $id, '_besedka_usage', true ) as $item ) {
			$type = isset( $item[0] ) ? $item[0] : '';
			$text = isset( $item[1] ) ? $item[1] : '';
			if ( 'step' === $type || 'text' === $type ) {
				$steps[] = array( 'text' => $text, 'warning' => '' );
			} elseif ( 'note' === $type && $steps ) {
				$last                       = count( $steps ) - 1;
				$steps[ $last ]['warning'] = $steps[ $last ]['warning'] ? $steps[ $last ]['warning'] . ' ' . $text : $text;
			}
		}
		update_post_meta( $id, '_besedka_steps', $steps );
	}
	if ( ! get_post_meta( $id, '_besedka_ferment_cols', true ) ) {
		$cols = array();
		foreach ( (array) get_post_meta( $id, '_besedka_ferment', true ) as $line ) {
			$label  = isset( $line[0] ) ? mb_strtolower( $line[0] ) : '';
			$values = isset( $line[1] ) ? (array) $line[1] : array();
			$key    = false !== strpos( $label, 'сахар' ) ? 'sugar' : ( ( false !== strpos( $label, 'вод' ) || false !== strpos( $label, 'жидк' ) ) ? 'liquid' : ( false !== strpos( $label, 'дн' ) ? 'days' : '' ) );
			if ( ! $key ) {
				continue;
			}
			foreach ( $values as $i => $value ) {
				$cols[ $i ][ $key ] = $value;
			}
		}
		update_post_meta( $id, '_besedka_ferment_cols', array_values( $cols ) );
	}
}

/* ---- 4. «С этим покупают» — демо-аксессуары для дрожжей ---- */
$cross = array();
foreach ( array( 'Уголь кокосовый активированный', 'Кубики Cognac Mix, славонский дуб', 'Бонификатор для сахарных дистиллятов', 'Эссенция High Spirits' ) as $title ) {
	$cross_id = $find( $title );
	if ( $cross_id ) {
		$cross[] = $cross_id;
	}
}
if ( $cross ) {
	foreach ( $yeast as $row ) {
		$id = $find( $row['name'] );
		if ( $id ) {
			$product = wc_get_product( $id );
			if ( ! $product->get_cross_sell_ids() ) {
				$product->set_cross_sell_ids( $cross );
				$product->save();
			}
		}
	}
}

WP_CLI::log( 'Данные страницы товара обновлены.' );
