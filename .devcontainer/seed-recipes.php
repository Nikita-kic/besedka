<?php
/**
 * Создаёт/обновляет 4 демо-рецепта для блока «Как сделать — пошагово».
 * Запуск: wp eval-file .devcontainer/seed-recipes.php --allow-root
 */

global $wpdb;

$find = function ( $title, $type ) use ( $wpdb ) {
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'trash' AND post_title = %s LIMIT 1",
			$type,
			$title
		)
	);
};

$set_field = function ( $post_id, $name, $value ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( $name, $value, $post_id );
	} else {
		update_post_meta( $post_id, $name, $value );
	}
};

// Чем выше в списке, тем свежее дата — главный рецепт и порядок остальных.
$recipes = array(
	array(
		'title'      => 'Сахарная брага на турбо-дрожжах',
		'excerpt'    => 'Пошагово: подготовка воды и сахара, запуск брожения, очистка углём и выдержка на дубовых кубиках.',
		'duration'   => 'Брожение 2–3 дня',
		'difficulty' => 'Легко',
		'steps'      => 8,
		'beginner'   => 1,
		'featured'   => 1,
		'set'        => 'Набор для сахарной браги',
	),
	array(
		'title'      => 'Зерновой дистиллят на солоде',
		'excerpt'    => 'Зерновой дистиллят: затирание, брожение на зерновых и турбо-дрожжах, очистка.',
		'duration'   => 'Брожение 5–7 дней',
		'difficulty' => 'Средне',
		'steps'      => 8,
		'beginner'   => 0,
		'featured'   => 0,
		'set'        => 'Зерновой дистиллят',
	),
	array(
		'title'      => 'Фруктовая брага из яблок',
		'excerpt'    => 'Фруктовая брага на яблочном пюре и фруктовых дрожжах.',
		'duration'   => 'Брожение 4–6 дней',
		'difficulty' => 'Легко',
		'steps'      => 6,
		'beginner'   => 0,
		'featured'   => 0,
		'set'        => 'Фруктовый набор',
	),
	array(
		'title'      => 'Выдержка на дубовых кубиках: цвет и вкус коньяка',
		'excerpt'    => 'Как облагородить дистиллят дубовыми кубиками, эссенцией и бонификатором.',
		'duration'   => 'Выдержка от 2 недель',
		'difficulty' => 'Легко',
		'steps'      => 5,
		'beginner'   => 0,
		'featured'   => 0,
		'set'        => 'Облагораживание',
	),
);

foreach ( $recipes as $i => $recipe ) {
	$id      = $find( $recipe['title'], 'recipe' );
	$is_new  = ! $id;
	$post    = array(
		'post_type'    => 'recipe',
		'post_status'  => 'publish',
		'post_title'   => $recipe['title'],
		'post_excerpt' => $recipe['excerpt'],
	);
	if ( $is_new ) {
		$post['post_content'] = 'Подробная пошаговая инструкция скоро появится на этой странице. Список необходимых товаров указан ниже.';
		$post['post_date']    = gmdate( 'Y-m-d H:i:s', time() - $i * DAY_IN_SECONDS );
		$id                   = wp_insert_post( $post );
	} else {
		$post['ID'] = $id;
		wp_update_post( $post );
	}

	$set_id  = $find( $recipe['set'], 'product' );
	$kit_ids = array();
	foreach ( (array) get_post_meta( $set_id, '_besedka_set_items', true ) as $row ) {
		if ( ! empty( $row['id'] ) ) {
			$kit_ids[] = (int) $row['id'];
		}
	}

	$set_field( $id, 'duration', $recipe['duration'] );
	$set_field( $id, 'difficulty', $recipe['difficulty'] );
	$set_field( $id, 'steps_count', $recipe['steps'] );
	$set_field( $id, 'is_beginner', $recipe['beginner'] );
	$set_field( $id, 'is_featured', $recipe['featured'] );
	$set_field( $id, 'related_set', $set_id ? $set_id : '' );
	$set_field( $id, 'related_products', array_values( array_unique( $kit_ids ) ) );

	WP_CLI::log( ( $is_new ? 'Создан' : 'Обновлён' ) . " рецепт #$id: {$recipe['title']}" );
}
