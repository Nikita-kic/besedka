<?php
/**
 * Отчёт (ничего не меняет): сколько товаров в разделах каталога и какие товары вне этих разделов.
 * Запуск: wp eval-file wp-content/themes/besedka/.devcontainer/report-categories.php --allow-root
 */

$slugs = array( 'drozhzhi', 'nabory', 'ugol', 'kubiki', 'essencii', 'bonifikator', 'komplekt' );

$all = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1 ) );
WP_CLI::log( 'Опубликовано товаров: ' . count( $all ) );

WP_CLI::log( '== Разделы: счётчик WooCommerce / фактически ==' );
$in_sections = array();
foreach ( $slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term ) {
		WP_CLI::log( "$slug: категории нет" );
		continue;
	}
	$ids = get_posts(
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$in_sections = array_merge( $in_sections, $ids );
	WP_CLI::log( sprintf( '%s («%s»): счётчик %d / фактически %d', $slug, $term->name, $term->count, count( $ids ) ) );
}

WP_CLI::log( '== Товары вне этих разделов ==' );
$outside = 0;
foreach ( $all as $post ) {
	if ( ! in_array( $post->ID, $in_sections, true ) ) {
		$outside++;
		$cats = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) );
		WP_CLI::log( sprintf( '#%d «%s»: %s', $post->ID, $post->post_title, $cats ? implode( ', ', $cats ) : 'без категории' ) );
	}
}
WP_CLI::log( $outside ? "Вне разделов: $outside" : 'Все товары входят в разделы.' );
