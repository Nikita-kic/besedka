<?php
/**
 * Страница категории и общего каталога: /catalog/{категория}/ и /catalog/.
 * Макет «Каталог, вариант 4 — Боковая панель» (см. inc/catalog-page.php).
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$queried = get_queried_object();
$term    = ( $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy ) ? $queried : null;
$title   = $term ? $term->name : __( 'Каталог', 'besedka' );
$base    = $term ? get_term_link( $term ) : besedka_catalog_url();
$base    = is_wp_error( $base ) ? besedka_catalog_url() : $base;
$paged   = max( 1, (int) get_query_var( 'paged' ) );

$query    = new WP_Query( besedka_catalog_args( $term, null, $paged ) );
$products = array_filter( array_map( 'wc_get_product', wp_list_pluck( $query->posts, 'ID' ) ) );
$found    = (int) $query->found_posts;
$total    = $term ? (int) $term->count : (int) wp_count_posts( 'product' )->publish;
wp_reset_postdata();

$active  = besedka_catalog_active_filters( $base );
$orderby = besedka_catalog_orderby();
$sorts   = besedka_catalog_sort_options();
$params  = besedka_catalog_current_params();
?>

<section class="catalog catalog-page">
	<div class="container">

		<?php besedka_render_breadcrumbs(); ?>

		<div class="catalog-layout">

			<?php besedka_render_catalog_aside( $term, $base, $found ); ?>

			<div class="catalog-content">

				<div class="catalog-head">
					<h1 class="catalog-head__title"><?php echo esc_html( $title ); ?></h1>
					<span class="catalog-head__count"><?php echo esc_html( sprintf( __( '%1$d из %2$d', 'besedka' ), $found, $total ) ); ?></span>
					<label class="catalog-sort">
						<span><?php esc_html_e( 'Сортировка:', 'besedka' ); ?></span>
						<select class="catalog-sort__select" data-catalog-sort>
							<?php foreach ( $sorts as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $orderby, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<div class="catalog-tools">
					<button type="button" class="catalog-tools__btn" data-catalog-open="filters">
						<?php esc_html_e( 'Фильтры', 'besedka' ); ?>
						<?php if ( $active ) : ?>
							<span class="catalog-tools__count"><?php echo esc_html( count( $active ) ); ?></span>
						<?php endif; ?>
					</button>
					<button type="button" class="catalog-tools__btn" data-catalog-open="sort"><?php esc_html_e( 'Сортировка', 'besedka' ); ?></button>
				</div>

				<div class="catalog-found<?php echo $active ? '' : ' catalog-found--empty'; ?>">
					<span class="catalog-found__text"><?php echo esc_html( besedka_catalog_found_text( $found ) . ( $active ? ':' : '' ) ); ?></span>
					<?php if ( $active ) : ?>
						<div class="catalog-found__tags">
							<?php foreach ( $active as $tag ) : ?>
								<a class="filter-tag" href="<?php echo esc_url( $tag[1] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Убрать фильтр: %s', 'besedka' ), $tag[0] ) ); ?>">
									<?php echo esc_html( $tag[0] ); ?>
									<span class="filter-tag__x" aria-hidden="true">&times;</span>
								</a>
							<?php endforeach; ?>
						</div>
						<a class="catalog-found__reset section__link-all" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Сбросить всё', 'besedka' ); ?></a>
					<?php endif; ?>
				</div>

				<?php if ( $products ) : ?>
					<div class="catalog-grid">
						<?php foreach ( $products as $product ) : ?>
							<?php besedka_render_product_card( $product ); ?>
						<?php endforeach; ?>
						<?php besedka_render_category_set_banner( $term ); ?>
					</div>
				<?php else : ?>
					<div class="catalog-empty">
						<p class="catalog-empty__text">
							<?php echo esc_html( $active ? __( 'Ничего не нашли — попробуйте убрать часть фильтров', 'besedka' ) : __( 'В этой категории пока нет товаров. Загляните позже или посмотрите другие разделы каталога.', 'besedka' ) ); ?>
						</p>
						<?php if ( $active ) : ?>
							<a class="btn btn--outline" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Сбросить фильтры', 'besedka' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $query->max_num_pages > 1 ) : ?>
					<nav class="products__pagination" aria-label="<?php esc_attr_e( 'Страницы каталога', 'besedka' ); ?>">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'     => trailingslashit( $base ) . 'page/%#%/',
									'format'   => '',
									'total'    => $query->max_num_pages,
									'current'  => $paged,
									'type'      => 'list',
									'prev_text' => '&larr;',
									'next_text' => '&rarr;',
									'add_args'  => $params,
								)
							)
						);
						?>
					</nav>
				<?php endif; ?>

				<?php if ( $term && term_description( $term ) ) : ?>
					<div class="seo-text"><?php echo wp_kses_post( term_description( $term ) ); ?></div>
				<?php endif; ?>

			</div>
		</div>

	</div>
</section>

<div class="catalog-overlay" data-catalog-overlay></div>

<div class="catalog-sheet" data-catalog-sort-sheet role="dialog" aria-label="<?php esc_attr_e( 'Сортировка', 'besedka' ); ?>">
	<p class="eyebrow"><?php esc_html_e( 'Сортировка', 'besedka' ); ?></p>
	<?php foreach ( $sorts as $value => $label ) : ?>
		<a class="catalog-sheet__link<?php echo $orderby === $value ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_merge( $params, array( 'orderby' => $value ) ), $base ) ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
</div>


<?php
get_footer();
