<?php
/**
 * Отчёт по каталогу (ничего не меняет):
 *  1) товары, у которых категорий не одна;
 *  2) ярлыки (slug) товаров и категорий не в формате «латиница, строчные, дефисы».
 * Запуск: wp eval-file .devcontainer/check-catalog.php --allow-root
 */

$translit = array(
	'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
	'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
	'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
	'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
);
$stop_words = array( 'product', 'tovar', 'copy', 'kopiya', 'draft', 'test' );

// Утверждённые ярлыки: inc/slugs.php (название => slug).
$preferred = include get_theme_root() . '/besedka/inc/slugs.php';

$propose = function ( $title ) use ( $translit, $stop_words, $preferred ) {
	if ( isset( $preferred[ $title ] ) ) {
		return $preferred[ $title ];
	}
	$text  = strtr( mb_strtolower( $title ), $translit );
	$text  = trim( preg_replace( '/[^a-z0-9]+/', '-', $text ), '-' );
	$parts = array_filter( explode( '-', $text ), function ( $p ) use ( $stop_words ) {
		return ! in_array( $p, $stop_words, true );
	} );
	return implode( '-', $parts );
};

$is_bad = function ( $slug ) use ( $stop_words ) {
	if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) {
		return true;
	}
	if ( preg_match( '/^(product|tovar)-\d+$/', $slug ) ) {
		return true;
	}
	foreach ( explode( '-', $slug ) as $part ) {
		if ( in_array( $part, $stop_words, true ) ) {
			return true;
		}
	}
	return false;
};

WP_CLI::log( '== 1. Товары: категорий не одна ==' );
$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1 ) );
$bad_cats = 0;
foreach ( $products as $post ) {
	$terms = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) );
	if ( 1 !== count( $terms ) ) {
		$bad_cats++;
		WP_CLI::log( sprintf( '#%d «%s»: %s', $post->ID, $post->post_title, $terms ? implode( ', ', $terms ) : 'нет категорий' ) );
	}
}
WP_CLI::log( $bad_cats ? "Найдено: $bad_cats" : 'Все товары с ровно одной категорией.' );

WP_CLI::log( '== 2. Ярлыки товаров ==' );
$used     = array();
$bad_slug = 0;
foreach ( $products as $post ) {
	$slug = rawurldecode( $post->post_name );
	if ( $is_bad( $slug ) ) {
		$bad_slug++;
		$new = $propose( $post->post_title );
		while ( isset( $used[ $new ] ) ) {
			$new .= '-2';
		}
		$used[ $new ] = true;
		WP_CLI::log( sprintf( '#%d «%s»: %s → %s', $post->ID, $post->post_title, $slug, $new ) );
	} else {
		$used[ $slug ] = true;
	}
}
WP_CLI::log( $bad_slug ? "Предлагается заменить: $bad_slug (сам скрипт ничего не меняет)" : 'Все ярлыки товаров в порядке.' );

WP_CLI::log( '== 3. Ярлыки категорий ==' );
$bad_term = 0;
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	$slug = rawurldecode( $term->slug );
	if ( $is_bad( $slug ) ) {
		$bad_term++;
		WP_CLI::log( sprintf( '#%d «%s»: %s → %s', $term->term_id, $term->name, $slug, $propose( $term->name ) ) );
	}
}
WP_CLI::log( $bad_term ? "Предлагается заменить: $bad_term" : 'Все ярлыки категорий в порядке.' );
