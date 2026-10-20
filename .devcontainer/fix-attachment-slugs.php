<?php
/**
 * Переименовывает вложения, у которых ярлык совпадает с ярлыком категории товара или страницы.
 * Запуск: wp eval-file .devcontainer/fix-attachment-slugs.php --allow-root
 */

global $wpdb;

$taken = array();
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	$taken[] = $term->slug;
}
foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1 ) ) as $page ) {
	$taken[] = $page->post_name;
}

foreach ( array_unique( $taken ) as $slug ) {
	$ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_name = %s", $slug )
	);
	foreach ( $ids as $id ) {
		wp_update_post(
			array(
				'ID'        => (int) $id,
				'post_name' => $slug . '-photo',
			)
		);
		WP_CLI::log( "Вложение #$id: ярлык $slug → " . get_post_field( 'post_name', $id ) );
	}
}
