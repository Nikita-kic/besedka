<?php
/**
 * Страница товара «Витрина»: галерея и карточка покупки, вкладки (описание, характеристики, отзывы),
 * «С этим покупают», «Почему Беседка», окно «Написать отзыв».
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	global $product;
	if ( ! is_a( $product, 'WC_Product' ) && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) {
		continue;
	}

	$id          = $product->get_id();
	$title       = $product->get_name();
	$image_ids   = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
	$main_src    = $image_ids ? wp_get_attachment_image_url( $image_ids[0], 'besedka-gallery' ) : wc_placeholder_img_src( 'besedka-gallery' );
	$thumb_src   = $image_ids ? wp_get_attachment_image_url( $image_ids[0], 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' );
	$badges      = besedka_product_badges( $product );
	$short       = trim( (string) $product->get_short_description() );
	$siblings    = besedka_brand_siblings( $product );
	$brand       = besedka_product_brand( $product );
	$cats        = get_the_terms( $id, 'product_cat' );
	$cat         = ( $cats && ! is_wp_error( $cats ) ) ? reset( $cats ) : null;
	$is_sale     = $product->is_on_sale() && $product->get_sale_price();
	$price       = $product->get_price();
	$set_id      = besedka_product_set_id( $product );

	$description = trim( (string) $product->get_description() );
	$steps       = besedka_get_usage_steps( $id );
	$ferment     = besedka_get_ferment_table( $id );
	$specs       = besedka_product_specs( $product );

	$reviews      = besedka_get_product_reviews( $id );
	$review_count = count( $reviews );
	$rating_sum   = 0;
	foreach ( $reviews as $review ) {
		$rating_sum += (int) get_comment_meta( $review->comment_ID, 'rating', true );
	}
	$rating_avg = $review_count ? $rating_sum / $review_count : 0;

	$cross_products = array();
	foreach ( $product->get_cross_sell_ids() as $cross_id ) {
		$cross = wc_get_product( $cross_id );
		if ( $cross && 'publish' === get_post_status( $cross_id ) && $cross->is_in_stock() ) {
			$cross_products[] = $cross;
		}
	}

	$user      = wp_get_current_user();
	$user_name = $user->exists() ? ( $user->first_name ? $user->first_name : $user->display_name ) : '';
	if ( function_exists( 'WC' ) && WC()->customer && WC()->customer->get_billing_first_name() ) {
		$user_name = WC()->customer->get_billing_first_name();
	}

	$tabs = array( 'description' => __( 'Описание', 'besedka' ) );
	if ( $specs ) {
		$tabs['specs'] = __( 'Характеристики', 'besedka' );
	}
	$tabs['reviews'] = __( 'Отзывы', 'besedka' );
	?>

	<article class="pp" data-pp data-product-id="<?php echo esc_attr( $id ); ?>">
		<div class="container">

			<?php besedka_render_breadcrumbs(); ?>
			<?php if ( $cat ) : ?>
				<a class="pp-back" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">&larr; <?php echo esc_html( $cat->name ); ?></a>
			<?php endif; ?>

			<div class="pp-hero">

				<?php
				$has_many    = count( $image_ids ) > 1; // Колонка миниатюр есть всегда, стрелки, точки и свайп — только при нескольких фото.
				$gallery_ids = $image_ids ? $image_ids : array( 0 ); // Без фото — один слайд с заглушкой.
				?>
				<div class="pp-gallery pp-gallery--thumbs<?php echo $has_many ? '' : ' pp-gallery--single'; ?>" data-pp-gallery>
					<div class="pp-gallery__rail">
						<div class="pp-gallery__rail-inner">
							<button type="button" class="pp-gallery__arrow" data-pp-arrow="-1" aria-label="<?php esc_attr_e( 'Предыдущие фото', 'besedka' ); ?>" hidden>&uarr;</button>
							<div class="pp-gallery__thumbs" data-pp-thumbs>
								<?php foreach ( $gallery_ids as $index => $image_id ) : ?>
									<button type="button" class="pp-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-pp-thumb aria-label="<?php echo esc_attr( sprintf( __( 'Фото %d', 'besedka' ), $index + 1 ) ); ?>">
										<img src="<?php echo esc_url( $image_id ? wp_get_attachment_image_url( $image_id, 'besedka-thumb' ) : wc_placeholder_img_src( 'besedka-thumb' ) ); ?>" alt="" loading="lazy">
									</button>
								<?php endforeach; ?>
							</div>
							<button type="button" class="pp-gallery__arrow" data-pp-arrow="1" aria-label="<?php esc_attr_e( 'Следующие фото', 'besedka' ); ?>" hidden>&darr;</button>
						</div>
					</div>
					<div class="pp-gallery__main">
						<div class="pp-gallery__track" data-pp-track>
							<?php foreach ( $gallery_ids as $index => $image_id ) : ?>
								<div class="pp-gallery__slide">
									<img class="pp-gallery__image" src="<?php echo esc_url( 0 === $index ? $main_src : wp_get_attachment_image_url( $image_id, 'besedka-gallery' ) ); ?>" alt="<?php echo esc_attr( $title ); ?>"<?php echo 0 === $index ? '' : ' loading="lazy"'; ?>>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="product-card__favorite" data-favorite-btn data-product-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Добавить в избранное', 'besedka' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
						</button>
					</div>
					<?php if ( $has_many ) : ?>
						<div class="pp-gallery__dots" data-pp-dots>
							<?php foreach ( $image_ids as $index => $image_id ) : ?>
								<button type="button" class="pp-gallery__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-pp-dot aria-label="<?php echo esc_attr( sprintf( __( 'Фото %d', 'besedka' ), $index + 1 ) ); ?>"></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="pp-side">
					<div class="pp-card">

						<?php if ( $badges ) : ?>
							<div class="pp-card__badges">
								<?php foreach ( $badges as $badge ) : ?>
									<span class="pp-badge pp-badge--<?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="pp-card__title"><?php echo esc_html( $title ); ?></h1>

						<?php if ( '' !== $short ) : ?>
							<div class="pp-card__lead"><?php echo wp_kses_post( wpautop( $short ) ); ?></div>
						<?php endif; ?>

						<?php if ( $siblings && $brand ) : ?>
							<div class="pp-brand">
								<p class="pp-brand__title">
									<?php
									$has_yeast = $cat && 'drozhzhi' === $cat->slug;
									echo esc_html( ( $has_yeast ? __( 'Другие дрожжи', 'besedka' ) : __( 'Другие товары', 'besedka' ) ) . ' ' . $brand->name );
									?>
								</p>
								<div class="pp-brand__list">
									<?php foreach ( $siblings as $sibling ) : ?>
										<?php if ( $sibling[2] ) : ?>
											<span class="chip chip--outline pp-brand__item is-current" aria-current="true"><?php echo esc_html( $sibling[1] ); ?></span>
										<?php else : ?>
											<a class="chip chip--outline pp-brand__item" href="<?php echo esc_url( get_permalink( $sibling[0]->get_id() ) ); ?>"><?php echo esc_html( $sibling[1] ); ?></a>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<div class="pp-price">
							<div class="pp-price__main">
								<span class="pp-price__current"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
								<?php if ( $is_sale ) : ?>
									<del class="pp-price__old"><?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></del>
								<?php endif; ?>
							</div>
							<span class="pp-stock<?php echo $product->is_in_stock() ? '' : ' pp-stock--out'; ?>">
								<span class="pp-stock__dot" aria-hidden="true"></span>
								<?php echo $product->is_in_stock() ? esc_html__( 'В наличии', 'besedka' ) : esc_html__( 'Нет в наличии', 'besedka' ); ?>
							</span>
						</div>

						<div class="pp-buy">
							<?php besedka_render_qty(); ?>
							<?php besedka_add_to_cart_button( $product, __( 'В корзину', 'besedka' ), 'btn btn--primary pp-buy__button' ); ?>
						</div>

						<ul class="pp-lines">
							<li class="pp-lines__item">
								<span class="pp-lines__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/></svg></span>
								<span><?php esc_html_e( 'Доставка Почтой России', 'besedka' ); ?> &middot; <button type="button" class="pp-lines__city" data-city-trigger><span data-city-current><?php esc_html_e( 'Москва', 'besedka' ); ?></span></button> &middot; <?php esc_html_e( 'с трек-номером', 'besedka' ); ?></span>
							</li>
							<li class="pp-lines__item">
								<span class="pp-lines__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10.5h18M7 15h4"/></svg></span>
								<span><?php esc_html_e( 'Оплата картой онлайн при оформлении', 'besedka' ); ?></span>
							</li>
						</ul>
					</div>

					<?php besedka_render_set_banner( $set_id, true ); ?>
				</div>
			</div>

			<!-- Вкладки -->
			<nav class="pp-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Разделы товара', 'besedka' ); ?>" data-pp-tabs>
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="chip pp-tabs__item<?php echo 'description' === $key ? ' is-active' : ''; ?>" href="#<?php echo esc_attr( $key ); ?>" role="tab" data-pp-tab="<?php echo esc_attr( $key ); ?>">
						<span class="pp-tabs__label" data-text="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></span>
						<?php if ( 'reviews' === $key ) : ?>
							<span class="pp-tabs__count"><?php echo esc_html( $review_count ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<!-- Описание -->
			<section class="pp-panel" id="description" data-pp-panel="description">
				<?php $has_table = ! empty( $ferment['cols'] ); ?>
				<div class="pp-desc<?php echo $has_table ? '' : ' pp-desc--full'; ?>">
					<div class="pp-desc__main">
						<?php if ( '' !== $description ) : ?>
							<h2 class="pp-title"><?php esc_html_e( 'Описание', 'besedka' ); ?></h2>
							<div class="pp-prose"><?php echo wp_kses_post( wpautop( $description ) ); ?></div>
						<?php endif; ?>

						<?php if ( $steps ) : ?>
							<h2 class="pp-title"><?php esc_html_e( 'Способ применения', 'besedka' ); ?></h2>
							<ol class="pp-steps">
								<?php foreach ( $steps as $n => $step ) : ?>
									<li class="pp-steps__item">
										<span class="pp-steps__num"><?php echo esc_html( $n + 1 ); ?></span>
										<div class="pp-steps__body">
											<p class="pp-steps__text"><?php echo esc_html( $step['text'] ); ?></p>
											<?php if ( '' !== $step['warning'] ) : ?>
												<p class="pp-steps__warn"><?php echo esc_html( $step['warning'] ); ?></p>
											<?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
					</div>

					<?php if ( $has_table ) : ?>
						<aside class="pp-ferment">
							<h2 class="pp-title"><?php esc_html_e( 'Таблица брожения', 'besedka' ); ?></h2>
							<div class="pp-ferment__table-wrap">
								<table class="pp-ferment__table">
									<tbody>
										<?php
										$rows = array(
											'sugar'  => __( 'Сахар', 'besedka' ),
											'liquid' => __( 'Жидкость', 'besedka' ),
											'days'   => __( 'Дни', 'besedka' ),
										);
										foreach ( $rows as $key => $label ) :
											?>
											<tr>
												<th scope="row"><?php echo esc_html( $label ); ?></th>
												<?php foreach ( $ferment['cols'] as $col ) : ?>
													<td><?php echo esc_html( $col[ $key ] ); ?></td>
												<?php endforeach; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php if ( '' !== $ferment['note'] ) : ?>
								<p class="pp-ferment__note"><?php echo esc_html( $ferment['note'] ); ?></p>
							<?php endif; ?>
						</aside>
					<?php endif; ?>
				</div>
				<p class="pp-pack-note"><?php esc_html_e( 'Внешний вид упаковки может немного отличаться от изображения.', 'besedka' ); ?></p>
			</section>

			<!-- Характеристики -->
			<?php if ( $specs ) : ?>
				<section class="pp-panel" id="specs" data-pp-panel="specs">
					<h2 class="pp-title"><?php esc_html_e( 'Технические характеристики', 'besedka' ); ?></h2>
					<dl class="pp-specs">
						<?php foreach ( $specs as $spec ) : ?>
							<div class="pp-specs__row">
								<dt><?php echo esc_html( $spec[0] ); ?></dt>
								<dd><?php echo esc_html( $spec[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
					<p class="pp-pack-note"><?php esc_html_e( 'Внешний вид упаковки может немного отличаться от изображения.', 'besedka' ); ?></p>
				</section>
			<?php endif; ?>

			<!-- Отзывы -->
			<section class="pp-panel" id="reviews" data-pp-panel="reviews">
				<div class="pp-reviews">
					<?php if ( $review_count > 0 ) : ?>
						<div class="pp-reviews__head">
							<div class="pp-reviews__summary">
								<span class="pp-reviews__avg"><?php echo esc_html( str_replace( '.', ',', number_format( $rating_avg, 1, '.', '' ) ) ); ?></span>
								<span class="pp-stars pp-stars--lg"><?php echo besedka_render_stars( $rating_avg ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span class="pp-reviews__count"><?php echo esc_html( $review_count . ' ' . besedka_plural( $review_count, array( 'отзыв', 'отзыва', 'отзывов' ) ) ); ?></span>
							</div>
							<button type="button" class="btn btn--outline btn--small" data-review-open><?php esc_html_e( 'Написать отзыв', 'besedka' ); ?></button>
						</div>

						<div class="pp-reviews__filters" data-pp-review-filters>
							<button type="button" class="chip is-active" data-pp-filter="new"><?php esc_html_e( 'Новые', 'besedka' ); ?></button>
							<button type="button" class="chip" data-pp-filter="negative"><?php esc_html_e( 'Негативные', 'besedka' ); ?></button>
						</div>

						<ol class="pp-reviews__list" data-pp-review-list>
							<?php
							foreach ( $reviews as $review ) :
								$rating = (int) get_comment_meta( $review->comment_ID, 'rating', true );
								$pros   = get_comment_meta( $review->comment_ID, 'besedka_pros', true );
								$cons   = get_comment_meta( $review->comment_ID, 'besedka_cons', true );
								$reply  = besedka_review_shop_reply( $review );
								?>
								<li class="pp-review" data-rating="<?php echo esc_attr( $rating ); ?>">
									<div class="pp-review__head">
										<span class="pp-review__name"><?php echo esc_html( $review->comment_author ); ?></span>
										<span class="pp-stars"><?php echo besedka_render_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
										<time class="pp-review__date" datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $review->comment_date_gmt . ' UTC' ) ) ); ?>"><?php echo esc_html( besedka_ru_date( $review->comment_date ) ); ?></time>
									</div>
									<p class="pp-review__text"><?php echo esc_html( $review->comment_content ); ?></p>
									<?php if ( $pros || $cons ) : ?>
										<p class="pp-review__pc">
											<?php if ( $pros ) : ?>
												<span class="pp-review__pros"><?php esc_html_e( 'Плюсы:', 'besedka' ); ?></span> <?php echo esc_html( $pros ); ?>
											<?php endif; ?>
											<?php if ( $cons ) : ?>
												<span class="pp-review__cons"><?php esc_html_e( 'Минусы:', 'besedka' ); ?></span> <?php echo esc_html( $cons ); ?>
											<?php endif; ?>
										</p>
									<?php endif; ?>
									<?php if ( $reply ) : ?>
										<div class="pp-review__reply">
											<p class="pp-review__reply-title"><?php esc_html_e( 'Ответ магазина «Беседка»', 'besedka' ); ?></p>
											<p><?php echo esc_html( $reply->comment_content ); ?></p>
										</div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>

						<?php if ( $review_count > 3 ) : ?>
							<button type="button" class="pp-reviews__more section__link-all" data-pp-review-more>
								<?php echo esc_html( sprintf( __( 'Показать все %d %s', 'besedka' ), $review_count, besedka_plural( $review_count, array( 'отзыв', 'отзыва', 'отзывов' ) ) ) ); ?> &rarr;
							</button>
						<?php endif; ?>
					<?php else : ?>
						<div class="pp-reviews__empty">
							<p class="pp-reviews__empty-text"><?php esc_html_e( 'Отзывов пока нет', 'besedka' ); ?></p>
							<button type="button" class="btn btn--outline btn--small" data-review-open><?php esc_html_e( 'Написать отзыв', 'besedka' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- С этим покупают -->
			<?php if ( $cross_products ) : ?>
				<section class="pp-cross" data-pp-cross>
					<div class="pp-cross__head">
						<h2 class="pp-title"><?php esc_html_e( 'С этим покупают', 'besedka' ); ?></h2>
						<div class="pp-cross__nav">
							<button type="button" class="pp-cross__arrow" data-pp-cross-prev aria-label="<?php esc_attr_e( 'Назад', 'besedka' ); ?>">&larr;</button>
							<button type="button" class="pp-cross__arrow" data-pp-cross-next aria-label="<?php esc_attr_e( 'Вперёд', 'besedka' ); ?>">&rarr;</button>
						</div>
					</div>
					<div class="pp-cross__track" data-pp-cross-track>
						<?php foreach ( $cross_products as $cross ) : ?>
							<?php besedka_render_cross_card( $cross ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<?php
		besedka_render_why_section(
			array(
				'pay_title' => __( 'Оплата онлайн', 'besedka' ),
				'pay_text'  => __( 'Банковской картой на сайте при оформлении заказа. Сразу после оплаты собираем и отправляем посылку.', 'besedka' ),
			)
		);
		?>

		<!-- Закреплённая панель покупки (мобильный) -->
		<div class="pp-buybar">
			<?php besedka_render_qty(); ?>
			<?php besedka_add_to_cart_button( $product, __( 'В корзину', 'besedka' ) . ' · ' . besedka_price_text( $price ), 'btn btn--primary pp-buybar__button' ); ?>
		</div>

		<!-- Окно «Написать отзыв» -->
		<div class="review-modal" data-review-modal aria-hidden="true">
			<div class="review-modal__overlay" data-review-close></div>
			<div class="review-modal__window" role="dialog" aria-modal="true" aria-labelledby="review-modal-title">

				<form class="review-form" data-review-form novalidate data-nonce="<?php echo esc_attr( wp_create_nonce( 'besedka_review' ) ); ?>" data-product-id="<?php echo esc_attr( $id ); ?>">
					<div class="review-form__head">
						<span class="review-form__thumb"><img src="<?php echo esc_url( $thumb_src ); ?>" alt=""></span>
						<div class="review-form__heading">
							<h2 class="review-form__title" id="review-modal-title"><?php esc_html_e( 'Отзыв о товаре', 'besedka' ); ?></h2>
							<p class="review-form__product"><?php echo esc_html( $title ); ?></p>
						</div>
						<button type="button" class="review-modal__close" data-review-close aria-label="<?php esc_attr_e( 'Закрыть', 'besedka' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
						</button>
					</div>

					<div class="review-field">
						<span class="review-field__label"><?php esc_html_e( 'Ваша оценка', 'besedka' ); ?></span>
						<div class="review-stars" data-review-stars role="radiogroup" aria-label="<?php esc_attr_e( 'Оценка', 'besedka' ); ?>">
							<?php for ( $star = 1; $star <= 5; $star++ ) : ?>
								<button type="button" class="review-stars__star" data-star="<?php echo esc_attr( $star ); ?>" role="radio" aria-checked="false" aria-label="<?php echo esc_attr( $star . ' — ' . besedka_rating_label( $star ) ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15 9 22 9 16.5 13.5 18.5 21 12 16.8 5.5 21 7.5 13.5 2 9 9 9"/></svg>
								</button>
							<?php endfor; ?>
							<span class="review-stars__caption" data-review-caption></span>
						</div>
						<input type="hidden" name="rating" value="" data-review-rating>
						<p class="review-field__error" data-error-for="rating" hidden></p>
					</div>

					<div class="review-field">
						<label class="review-field__label" for="review-content"><?php esc_html_e( 'Отзыв', 'besedka' ); ?></label>
						<textarea class="review-field__input review-field__input--area" id="review-content" name="content" rows="5" placeholder="<?php esc_attr_e( 'Как прошло брожение? Сколько ставили сахара, за сколько дней отбродило, какой получился результат', 'besedka' ); ?>"></textarea>
						<p class="review-field__error" data-error-for="content" hidden></p>
					</div>

					<div class="review-form__row">
						<div class="review-field">
							<label class="review-field__label" for="review-pros"><?php esc_html_e( 'Плюсы', 'besedka' ); ?> <span class="review-field__hint">· <?php esc_html_e( 'необязательно', 'besedka' ); ?></span></label>
							<input class="review-field__input" type="text" id="review-pros" name="pros" placeholder="<?php esc_attr_e( 'Что понравилось', 'besedka' ); ?>">
						</div>
						<div class="review-field">
							<label class="review-field__label" for="review-cons"><?php esc_html_e( 'Минусы', 'besedka' ); ?> <span class="review-field__hint">· <?php esc_html_e( 'необязательно', 'besedka' ); ?></span></label>
							<input class="review-field__input" type="text" id="review-cons" name="cons" placeholder="<?php esc_attr_e( 'Что не понравилось', 'besedka' ); ?>">
						</div>
					</div>

					<div class="review-form__row">
						<div class="review-field">
							<label class="review-field__label" for="review-name"><?php esc_html_e( 'Имя', 'besedka' ); ?></label>
							<input class="review-field__input" type="text" id="review-name" name="name" value="<?php echo esc_attr( $user_name ); ?>" autocomplete="given-name">
							<p class="review-field__error" data-error-for="name" hidden></p>
						</div>
						<div class="review-field">
							<label class="review-field__label" for="review-city"><?php esc_html_e( 'Город', 'besedka' ); ?> <span class="review-field__hint">· <?php esc_html_e( 'необязательно', 'besedka' ); ?></span></label>
							<input class="review-field__input" type="text" id="review-city" name="city" placeholder="<?php esc_attr_e( 'Например, Белгород', 'besedka' ); ?>">
						</div>
					</div>

					<div class="review-field">
						<label class="review-consent">
							<input type="checkbox" name="consent" value="1">
							<span><?php esc_html_e( 'Согласен на обработку персональных данных и с', 'besedka' ); ?> <a href="<?php echo esc_url( home_url( '/review-rules/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'правилами публикации отзывов', 'besedka' ); ?></a></span>
						</label>
						<p class="review-field__error" data-error-for="consent" hidden></p>
					</div>

					<p class="review-field__error review-form__error" data-error-for="form" hidden></p>

					<button type="submit" class="btn btn--primary btn--full review-form__submit"><?php esc_html_e( 'Отправить отзыв', 'besedka' ); ?></button>
					<p class="review-form__note"><?php esc_html_e( 'Отзыв появится на сайте после проверки', 'besedka' ); ?></p>
				</form>

				<div class="review-thanks" data-review-thanks hidden>
					<span class="review-thanks__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
					<h2 class="review-thanks__title"><?php esc_html_e( 'Спасибо за отзыв!', 'besedka' ); ?></h2>
					<p class="review-thanks__text"><?php esc_html_e( 'Мы проверим его и опубликуем в течение дня. Ваш опыт поможет другим покупателям с выбором.', 'besedka' ); ?></p>
					<button type="button" class="review-thanks__button" data-review-close><?php esc_html_e( 'Вернуться к товару', 'besedka' ); ?></button>
				</div>
			</div>
		</div>

		<?php besedka_render_product_schema( $product, $reviews ); ?>
	</article>

	<?php
endwhile;

get_footer();
