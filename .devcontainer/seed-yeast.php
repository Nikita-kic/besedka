<?php
/**
 * Создаёт/обновляет товары категории «Спиртовые дрожжи» из inc/products-yeast.php.
 * Запуск: wp eval-file .devcontainer/seed-yeast.php --allow-root
 */

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$theme_dir = get_theme_root() . '/besedka';
$products  = include $theme_dir . '/inc/products-yeast.php';

// Порядок в блоке «Популярные товары» на главной (slug из inc/products-yeast.php => позиция).
$popular = array(
	'bragoff-moskva-original' => 1,
	'bragoff-48-universal'    => 2,
	'hs-msk-exclusive'        => 3,
	'hs-h3-universal'         => 4,
	'legkie-saharnye'         => 5,
	'hs-48-classic'           => 6,
);

foreach ( $products as $item ) {
	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status != 'trash' AND post_title = %s LIMIT 1",
			$item['name']
		)
	);

	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	$is_new  = ! $id;

	$product->set_name( $item['name'] );
	$product->set_status( 'publish' );
	$product->set_description( $item['description'] );
	if ( $is_new ) {
		$product->set_regular_price( (string) $item['price'] );
	}
	$term = get_term_by( 'slug', $item['category'], 'product_cat' );
	if ( $term ) {
		$product->set_category_ids( array( $term->term_id ) );
	}
	$id = $product->save();

	update_post_meta( $id, '_besedka_brand', $item['brand'] );
	if ( isset( $popular[ $item['slug'] ] ) ) {
		update_post_meta( $id, '_besedka_popular_order', $popular[ $item['slug'] ] );
	} else {
		delete_post_meta( $id, '_besedka_popular_order' );
	}
	update_post_meta( $id, '_besedka_composition', $item['composition'] );
	update_post_meta( $id, '_besedka_usage', $item['usage'] );
	update_post_meta( $id, '_besedka_ferment', $item['ferment'] );

	if ( get_post_meta( $id, '_besedka_seed_image', true ) !== $item['image'] ) {
		$tmp = wp_tempnam( $item['image'] );
		copy( $theme_dir . '/img/products/' . $item['image'], $tmp );
		$attachment_id = media_handle_sideload(
			array(
				'name'     => $item['image'],
				'tmp_name' => $tmp,
			),
			$id
		);
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			WP_CLI::warning( 'Фото не загружено: ' . $item['name'] . ' — ' . $attachment_id->get_error_message() );
		} else {
			set_post_thumbnail( $id, $attachment_id );
			update_post_meta( $id, '_besedka_seed_image', $item['image'] );
		}
	}

	WP_CLI::log( ( $is_new ? 'Создан' : 'Обновлён' ) . " товар #$id: " . $item['name'] );
}
