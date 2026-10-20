<?php
/**
 * Создаёт/обновляет «Готовые наборы» (категория «Наборы» + 4 товара).
 * Цена набора = сумма текущих цен состава со скидкой, округление до 10 ₽.
 * Запуск: wp eval-file .devcontainer/seed-sets.php --allow-root
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

$term = get_term_by( 'slug', 'nabory', 'product_cat' );
if ( ! $term ) {
	$created = wp_insert_term( 'Наборы', 'product_cat', array( 'slug' => 'nabory' ) );
	$term    = is_wp_error( $created ) ? null : get_term( $created['term_id'], 'product_cat' );
}

// slot — порядок слайдов в карусели и вкладок; tab — подпись вкладки.
$sets = array(
	array(
		'name'     => 'Набор для сахарной браги',
		'tab'      => 'Сахарная брага',
		'slot'     => 1,
		'discount' => 15,
		'hit'      => 1,
		'desc'     => 'Всё для одного рецепта сахарной браги: быстрые турбо-дрожжи, спиртовые дрожжи, уголь для очистки и дубовые кубики для выдержки. К набору прилагается инструкция.',
		'items'    => array(
			array( 'Турбо-дрожжи BRAGOFF 48 Universal', 1, 'Турбо-дрожжи' ),
			array( 'Дрожжи спиртовые Лёгкие Сахарные', 2, 'Дрожжи × 2' ),
			array( 'Уголь кокосовый активированный', 1, 'Уголь' ),
			array( 'Кубики Cognac Mix, славонский дуб', 1, 'Кубики' ),
		),
	),
	array(
		'name'     => 'Облагораживание',
		'tab'      => 'Облагораживание',
		'slot'     => 4,
		'discount' => 15,
		'hit'      => 0,
		'desc'     => 'Набор для облагораживания готового дистиллята: дубовые кубики, эссенция и бонификатор. К набору прилагается инструкция.',
		'items'    => array(
			array( 'Кубики Cognac Mix, славонский дуб', 1, 'Кубики' ),
			array( 'Эссенция High Spirits', 1, 'Эссенция' ),
			array( 'Бонификатор для сахарных дистиллятов', 1, 'Бонификатор' ),
		),
	),
	array(
		'name'     => 'Фруктовый набор',
		'tab'      => 'Фруктовый',
		'slot'     => 3,
		'discount' => 13,
		'hit'      => 0,
		'desc'     => 'Набор для фруктового дистиллята: две пачки фруктовых дрожжей, эссенция и дубовые кубики. К набору прилагается инструкция.',
		'items'    => array(
			array( 'Дрожжи спиртовые Лёгкие Фруктовые', 2, 'Дрожжи × 2' ),
			array( 'Эссенция High Spirits', 1, 'Эссенция' ),
			array( 'Кубики Cognac Mix, славонский дуб', 1, 'Кубики' ),
		),
	),
	array(
		'name'     => 'Зерновой дистиллят',
		'tab'      => 'Зерновой',
		'slot'     => 2,
		'discount' => 12,
		'hit'      => 0,
		'desc'     => 'Набор для зернового дистиллята: зерновые дрожжи, турбо-дрожжи Whiskey и уголь для очистки. К набору прилагается инструкция.',
		'items'    => array(
			array( 'Дрожжи спиртовые Лёгкие Зерновые', 1, 'Дрожжи' ),
			array( 'Турбо-дрожжи High Spirits W Whiskey', 1, 'Турбо-дрожжи' ),
			array( 'Уголь кокосовый активированный', 1, 'Уголь' ),
		),
	),
);

foreach ( $sets as $set ) {
	$items       = array();
	$sum         = 0;
	$composition = array();
	foreach ( $set['items'] as $row ) {
		$pid = $find( $row[0] );
		if ( ! $pid ) {
			WP_CLI::warning( 'Нет товара для набора «' . $set['name'] . '»: ' . $row[0] );
			continue 2;
		}
		$p             = wc_get_product( $pid );
		$sum          += (float) $p->get_price() * $row[1];
		$items[]       = array(
			'id'    => $pid,
			'qty'   => $row[1],
			'label' => $row[2],
		);
		$composition[] = $p->get_name() . ( $row[1] > 1 ? ' × ' . $row[1] : '' );
	}

	$price = round( $sum * ( 100 - $set['discount'] ) / 100 / 10 ) * 10;

	$id      = $find( $set['name'] );
	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	$is_new  = ! $id;

	$product->set_name( $set['name'] );
	$product->set_status( 'publish' );
	$product->set_description( $set['desc'] );
	$product->set_regular_price( (string) $sum );
	$product->set_sale_price( (string) $price );
	if ( $term ) {
		$product->set_category_ids( array( $term->term_id ) );
	}
	$id = $product->save();

	update_post_meta( $id, '_besedka_set_items', $items );
	update_post_meta( $id, '_besedka_set_slot', $set['slot'] );
	update_post_meta( $id, '_besedka_set_tab', $set['tab'] );
	update_post_meta( $id, '_besedka_composition', implode( '; ', $composition ) . '.' );
	if ( $set['hit'] ) {
		update_post_meta( $id, '_besedka_is_hit', '1' );
	} else {
		delete_post_meta( $id, '_besedka_is_hit' );
	}

	WP_CLI::log( ( $is_new ? 'Создан' : 'Обновлён' ) . " набор #$id: {$set['name']} — $price ₽ вместо $sum ₽" );
}

// Категория «Спиртовые дрожжи»: связанный набор для плашки под сеткой.
$yeast_term = get_term_by( 'slug', 'drozhzhi', 'product_cat' );
$yeast_set  = $find( 'Набор для сахарной браги' );
if ( $yeast_term && $yeast_set ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( 'related_set', $yeast_set, 'product_cat_' . $yeast_term->term_id );
	} else {
		update_term_meta( $yeast_term->term_id, 'related_set', $yeast_set );
	}
	WP_CLI::log( 'Категория «Спиртовые дрожжи»: связанный набор — Набор для сахарной браги' );
}

// Порядок разделов в каталоге (term order): задаётся только там, где ещё не расставлен вручную.
foreach ( array( 'drozhzhi', 'nabory', 'ugol', 'kubiki', 'essencii', 'bonifikator', 'komplekt' ) as $position => $order_slug ) {
	$order_term = get_term_by( 'slug', $order_slug, 'product_cat' );
	if ( $order_term && 0 === (int) get_term_meta( $order_term->term_id, 'order', true ) ) {
		update_term_meta( $order_term->term_id, 'order', $position + 1 );
	}
}

// Набор по умолчанию для плашки на /catalog/ и в категориях без «Связанного набора».
if ( $yeast_set && ! get_theme_mod( 'besedka_default_set' ) ) {
	set_theme_mod( 'besedka_default_set', $yeast_set );
}
