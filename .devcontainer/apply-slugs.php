<?php
/**
 * Применяет ярлыки товаров из inc/slugs.php (название => slug). Идемпотентно.
 * Старые ярлыки сохраняются в _wp_old_slug — на них работают 301-редиректы.
 * Запуск: wp eval-file .devcontainer/apply-slugs.php --allow-root
 */

global $wpdb;

$map = include get_theme_root() . '/besedka/inc/slugs.php';

foreach ( $map as $title => $slug ) {
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status != 'trash' AND post_title = %s LIMIT 1",
			$title
		)
	);
	if ( ! $id ) {
		WP_CLI::log( "Нет товара: $title" );
		continue;
	}
	$current = rawurldecode( get_post_field( 'post_name', $id ) );
	if ( $current === $slug ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'        => $id,
			'post_name' => $slug,
		)
	);
	$result = get_post_field( 'post_name', $id );
	WP_CLI::log( sprintf( '#%d «%s»: %s → %s%s', $id, $title, $current, $result, $result === $slug ? '' : ' (ярлык занят, WordPress добавил суффикс)' ) );
}
