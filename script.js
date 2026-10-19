/**
 * Тема «БЕСЕДКА» — вся интерактивность каталога.
 * Чистый JavaScript (ES6+), без зависимостей. Методология БЭМ в data-атрибутах.
 */
(function () {
	'use strict';

	// Данные из functions.php (wp_localize_script), с безопасным фолбэком.
	var cfg = window.besedkaData || {
		ajaxUrl: '/wp-admin/admin-ajax.php',
		wcAjaxAddToCartUrl: '',
		nonce: '',
		cartUrl: '/cart/',
		checkoutUrl: '/checkout/',
		favoritesUrl: '/favorites/',
		currency: '₽',
		i18n: { added: 'Добавлено', error: 'Ошибка, попробуйте ещё раз', outOfStock: 'Нет в наличии', sending: 'Отправляем…' },
	};

	var FAVORITES_KEY = 'besedka_favorites';
	var CITY_KEY = 'besedka_city';

	document.addEventListener( 'DOMContentLoaded', function () {
		initStickyFilters();
		initFiltersDrawer();
		initFiltersReset();
		initFavorites();
		initQuickView();
		initModalClose();
		initCustomOrderForm();
		initCityModal();
		initSearch();
		initCountdown();
		initSetsCarousel();
		initAddedFeedback();
	} );

	/* ==========================================================================
	   УТИЛИТЫ
	   ========================================================================== */
	function qs( selector, ctx ) {
		return ( ctx || document ).querySelector( selector );
	}

	function qsa( selector, ctx ) {
		return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( selector ) );
	}

	function lockScroll( locked ) {
		document.body.classList.toggle( 'no-scroll', locked );
	}

	/* ==========================================================================
	   2. ЗАКРЕПЛЕНИЕ ПАНЕЛИ ФИЛЬТРОВ ПРИ СКРОЛЛЕ (К ВЕРХУ БРАУЗЕРА)
	   ========================================================================== */
	function initStickyFilters() {
		var panel = qs( '[data-filters-panel]' );
		var header = qs( '.site-header' );
		if ( ! panel || ! header ) {
			return;
		}

		var placeholderHeight = 0;
		var initialOffsetTop = 0;

		function measure() {
			if ( ! panel.classList.contains( 'is-sticky' ) ) {
				initialOffsetTop = panel.getBoundingClientRect().top + window.scrollY;
				placeholderHeight = panel.offsetHeight;
			}
		}

		measure();
		window.addEventListener( 'resize', measure );

		window.addEventListener( 'scroll', function () {
			// На мобильных вместо sticky используется выдвижная панель — пропускаем.
			if ( window.innerWidth <= 860 ) {
				return;
			}
			var shouldStick = window.scrollY > initialOffsetTop;
			panel.classList.toggle( 'is-sticky', shouldStick );
			panel.parentElement.style.paddingTop = shouldStick ? placeholderHeight + 'px' : '';
		}, { passive: true } );
	}

	/* ==========================================================================
	   3. МОБИЛЬНАЯ ВЫДВИЖНАЯ ПАНЕЛЬ ФИЛЬТРОВ
	   ========================================================================== */
	function initFiltersDrawer() {
		var panel = qs( '[data-filters-panel]' );
		var toggle = qs( '[data-filters-toggle]' );
		var closeBtn = qs( '[data-filters-close]' );
		var overlay = qs( '[data-filters-overlay]' );
		if ( ! panel || ! toggle ) {
			return;
		}

		function open() {
			panel.classList.add( 'is-drawer-open' );
			if ( overlay ) {
				overlay.classList.add( 'is-active' );
			}
			lockScroll( true );
		}

		function close() {
			panel.classList.remove( 'is-drawer-open' );
			if ( overlay ) {
				overlay.classList.remove( 'is-active' );
			}
			lockScroll( false );
		}

		toggle.addEventListener( 'click', open );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', close );
		}
		if ( overlay ) {
			overlay.addEventListener( 'click', close );
		}
	}

	/* ==========================================================================
	   4. СБРОС ФИЛЬТРОВ
	   ========================================================================== */
	function initFiltersReset() {
		qsa( '[data-filters-reset]' ).forEach( function ( link ) {
			link.addEventListener( 'click', function () {
				var form = qs( '[data-filters-form]', link.closest( '.filters' ) );
				if ( form ) {
					form.reset();
				}
				// Переход по чистой ссылке (без query-параметров) выполняется по умолчанию.
			} );
		} );
	}

	/* ==========================================================================
	   5. ИЗБРАННОЕ (LOCALSTORAGE)
	   ========================================================================== */
	function getFavorites() {
		try {
			return JSON.parse( localStorage.getItem( FAVORITES_KEY ) ) || [];
		} catch ( e ) {
			return [];
		}
	}

	function saveFavorites( list ) {
		localStorage.setItem( FAVORITES_KEY, JSON.stringify( list ) );
	}

	function updateFavoritesCount() {
		var count = getFavorites().length;
		qsa( '[data-favorites-count]' ).forEach( function ( el ) {
			el.textContent = count;
			el.setAttribute( 'data-count', count );
		} );
	}

	function syncFavoriteButtons( productId ) {
		var isActive = getFavorites().indexOf( String( productId ) ) !== -1;
		qsa( '[data-favorite-btn][data-product-id="' + productId + '"]' ).forEach( function ( btn ) {
			btn.classList.toggle( 'is-active', isActive );
		} );
	}

	function initFavorites() {
		updateFavoritesCount();
		qsa( '[data-favorite-btn]' ).forEach( function ( btn ) {
			syncFavoriteButtons( btn.getAttribute( 'data-product-id' ) );
		} );

		document.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( '[data-favorite-btn]' );
			if ( ! btn ) {
				return;
			}
			event.preventDefault();

			var id = String( btn.getAttribute( 'data-product-id' ) );
			var favorites = getFavorites();
			var index = favorites.indexOf( id );

			if ( index === -1 ) {
				favorites.push( id );
			} else {
				favorites.splice( index, 1 );
			}

			saveFavorites( favorites );
			updateFavoritesCount();
			syncFavoriteButtons( id );
		} );
	}

	/* ==========================================================================
	   6. БЫСТРЫЙ ПРОСМОТР ТОВАРА (МОДАЛЬНОЕ ОКНО)
	   ========================================================================== */
	function initQuickView() {
		document.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest( '[data-quickview-trigger]' );
			if ( ! trigger ) {
				return;
			}
			event.preventDefault();
			openQuickView( trigger.getAttribute( 'data-product-id' ) );
		} );
	}

	function openQuickView( productId ) {
		var modal = qs( '[data-product-modal]' );
		var content = qs( '[data-modal-content]' );
		if ( ! modal || ! content ) {
			return;
		}

		content.innerHTML = '<div class="modal__loading">' + ( cfg.i18n.sending || 'Загрузка…' ) + '</div>';
		openModal();

		var formData = new FormData();
		formData.append( 'action', 'besedka_quick_view' );
		formData.append( 'nonce', cfg.nonce );
		formData.append( 'product_id', productId );

		fetch( cfg.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( json.success ) {
					content.innerHTML = json.data.html;
					initGallery( content );
					initCounter( content );
					initTabs( content );
					syncFavoriteButtons( productId );
				} else {
					content.innerHTML = '<p class="modal__loading">' + ( cfg.i18n.error || 'Ошибка' ) + '</p>';
				}
			} )
			.catch( function () {
				content.innerHTML = '<p class="modal__loading">' + ( cfg.i18n.error || 'Ошибка' ) + '</p>';
			} );
	}

	function openModal() {
		var modal = qs( '[data-product-modal]' );
		if ( ! modal ) {
			return;
		}
		modal.classList.add( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'false' );
		lockScroll( true );
	}

	function closeModal() {
		var modal = qs( '[data-product-modal]' );
		if ( ! modal ) {
			return;
		}
		modal.classList.remove( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'true' );
		lockScroll( false );
	}

	function initModalClose() {
		document.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-modal-close]' ) ) {
				closeModal();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeModal();
			}
		} );
	}

	/* ==========================================================================
	   7. ГАЛЕРЕЯ ИЗОБРАЖЕНИЙ ТОВАРА (МОДАЛЬНОЕ ОКНО / СТРАНИЦА ТОВАРА)
	   ========================================================================== */
	function initGallery( scope ) {
		var images = qsa( '[data-gallery-image]', scope );
		var thumbs = qsa( '[data-gallery-thumb]', scope );
		var prevBtn = qs( '[data-gallery-prev]', scope );
		var nextBtn = qs( '[data-gallery-next]', scope );
		if ( images.length < 2 ) {
			return;
		}

		var current = 0;

		function show( index ) {
			current = ( index + images.length ) % images.length;
			images.forEach( function ( img, i ) {
				img.style.display = i === current ? '' : 'none';
			} );
			thumbs.forEach( function ( thumb, i ) {
				thumb.classList.toggle( 'is-active', i === current );
			} );
		}

		thumbs.forEach( function ( thumb, i ) {
			thumb.addEventListener( 'click', function () {
				show( i );
			} );
		} );

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				show( current - 1 );
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				show( current + 1 );
			} );
		}
	}

	// Инициализация галереи для страницы товара (single-product.php), если она есть при загрузке.
	initGallery( document );

	/* ==========================================================================
	   8. СЧЁТЧИК КОЛИЧЕСТВА (+ / −)
	   ========================================================================== */
	function initCounter( scope ) {
		var minusBtn = qs( '[data-counter-minus]', scope );
		var plusBtn = qs( '[data-counter-plus]', scope );
		var value = qs( '[data-counter-value]', scope );
		if ( ! value ) {
			return;
		}

		function set( n ) {
			var qty = Math.max( 1, n || 1 );
			value.value = qty;

			// Количество уходит в стандартную кнопку WooCommerce и в ссылку «Купить сейчас».
			var area = value.closest( '.modal__info' ) || scope;
			qsa( '.add_to_cart_button', area ).forEach( function ( btn ) {
				btn.setAttribute( 'data-quantity', qty );
			} );
			qsa( '[data-buy-now]', area ).forEach( function ( link ) {
				try {
					var url = new URL( link.href, window.location.href );
					url.searchParams.set( 'quantity', qty );
					link.href = url.toString();
				} catch ( e ) {}
			} );
		}

		if ( minusBtn ) {
			minusBtn.addEventListener( 'click', function () {
				set( parseInt( value.value, 10 ) - 1 );
			} );
		}
		if ( plusBtn ) {
			plusBtn.addEventListener( 'click', function () {
				set( parseInt( value.value, 10 ) + 1 );
			} );
		}
	}

	initCounter( document );

	/* ==========================================================================
	   9. ВКЛАДКИ ОПИСАНИЯ ТОВАРА (ОПИСАНИЕ / СОСТАВ / УХОД)
	   ========================================================================== */
	function initTabs( scope ) {
		var tabs = qsa( '[data-tab]', scope );
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var target = tab.getAttribute( 'data-tab' );

				qsa( '[data-tab]', scope ).forEach( function ( t ) {
					t.classList.remove( 'is-active' );
				} );
				tab.classList.add( 'is-active' );

				qsa( '[data-tab-panel]', scope ).forEach( function ( panel ) {
					panel.classList.toggle( 'is-active', panel.getAttribute( 'data-tab-panel' ) === target );
				} );
			} );
		} );
	}

	initTabs( document );

	/* 10. Корзина: добавление товара выполняет стандартный скрипт WooCommerce (см. раздел 16). */

	/* ==========================================================================
	   11. ФОРМА ИНДИВИДУАЛЬНОГО ЗАКАЗА «ПОДОБРАТЬ БУКЕТ»
	   ========================================================================== */
	function initCustomOrderForm() {
		var form = qs( '[data-custom-order-form]' );
		if ( ! form ) {
			return;
		}

		var fileInput = qs( 'input[type="file"]', form );
		var fileLabel = qs( '[data-file-label]', form );
		if ( fileInput && fileLabel ) {
			fileInput.addEventListener( 'change', function () {
				fileLabel.textContent = fileInput.files.length ? fileInput.files[0].name : 'Прикрепить файл';
			} );
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var messageEl = qs( '[data-custom-order-message]', form );
			var submitBtn = qs( '.custom-order__submit', form );
			var originalText = submitBtn ? submitBtn.textContent : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = cfg.i18n.sending || 'Отправляем…';
			}

			var formData = new FormData( form );
			formData.append( 'action', 'besedka_custom_order' );
			formData.append( 'nonce', cfg.nonce );

			fetch( cfg.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
					}

					if ( ! messageEl ) {
						return;
					}

					messageEl.classList.add( 'is-visible' );
					if ( json.success ) {
						messageEl.classList.remove( 'custom-order__message--error' );
						messageEl.classList.add( 'custom-order__message--success' );
						messageEl.textContent = json.data.message;
						form.reset();
						if ( fileLabel ) {
							fileLabel.textContent = 'Прикрепить файл';
						}
					} else {
						messageEl.classList.remove( 'custom-order__message--success' );
						messageEl.classList.add( 'custom-order__message--error' );
						messageEl.textContent = json.data && json.data.message ? json.data.message : ( cfg.i18n.error || 'Ошибка' );
					}
				} )
				.catch( function () {
					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
					}
					if ( messageEl ) {
						messageEl.classList.add( 'is-visible', 'custom-order__message--error' );
						messageEl.textContent = cfg.i18n.error || 'Ошибка';
					}
				} );
		} );
	}

	/* ==========================================================================
	   12. МОДАЛКА "ВЫБОР ГОРОДА"
	   ========================================================================== */
	function getSavedCity() {
		try {
			var raw = localStorage.getItem( CITY_KEY );
			return raw ? JSON.parse( raw ) : null;
		} catch ( e ) {
			return null;
		}
	}

	function setCurrentCityLabel( name ) {
		qsa( '[data-city-current]' ).forEach( function ( el ) {
			el.textContent = name;
		} );
	}

	function openCityModal() {
		var modal = qs( '[data-city-modal]' );
		if ( ! modal ) {
			return;
		}
		modal.classList.add( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'false' );
		lockScroll( true );
		var search = qs( '[data-city-search]', modal );
		if ( search ) {
			search.focus();
		}
	}

	function closeCityModal() {
		var modal = qs( '[data-city-modal]' );
		if ( ! modal ) {
			return;
		}
		modal.classList.remove( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'true' );
		lockScroll( false );
	}

	function initCityModal() {
		var modal = qs( '[data-city-modal]' );

		// Восстанавливаем ранее выбранный город.
		var saved = getSavedCity();
		if ( saved && saved.name ) {
			setCurrentCityLabel( saved.name );
		}

		if ( ! modal ) {
			return;
		}

		var search = qs( '[data-city-search]', modal );
		var groups = qsa( '[data-city-list] .city-modal__group', modal );
		var items = qsa( '[data-city-item]', modal );
		var emptyBox = qs( '[data-city-empty]', modal );
		var emptyName = qs( '[data-city-empty-name]', modal );

		function normCity( text ) {
			return String( text ).toLowerCase().replace( /ё/g, 'е' );
		}

		// Текущий город в списке выделяется цветом и жирным.
		function markCurrent() {
			var label = qs( '[data-city-current]' );
			var current = label ? normCity( label.textContent.trim() ) : '';
			items.forEach( function ( item ) {
				item.classList.toggle( 'is-current', normCity( item.getAttribute( 'data-city-name' ) ) === current );
			} );
		}

		// Фильтр на лету: без учёта регистра, ё = е, по началу названия; пустые буквы скрываются.
		function filter() {
			var typed = search ? search.value.trim() : '';
			var query = normCity( typed );
			var visibleGroups = 0;

			groups.forEach( function ( group ) {
				var visibleItems = 0;
				qsa( '.city-modal__item', group ).forEach( function ( item ) {
					var match = 0 === normCity( item.getAttribute( 'data-city-name' ) ).indexOf( query );
					item.parentElement.style.display = match ? '' : 'none';
					if ( match ) {
						visibleItems++;
					}
				} );
				group.style.display = visibleItems > 0 ? '' : 'none';
				if ( visibleItems > 0 ) {
					visibleGroups++;
				}
			} );

			if ( emptyBox ) {
				emptyBox.hidden = 0 !== visibleGroups || '' === typed;
				if ( emptyName ) {
					emptyName.textContent = typed;
				}
			}
		}

		function chooseCity( slug, name ) {
			try {
				localStorage.setItem( CITY_KEY, JSON.stringify( { slug: slug, name: name } ) );
			} catch ( e ) {}
			setCurrentCityLabel( name );
			markCurrent();
			closeCityModal();
		}

		qsa( '[data-city-trigger]' ).forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function () {
				openCityModal();
				if ( search ) {
					search.value = '';
				}
				filter();
				markCurrent();
			} );
		} );

		qsa( '[data-city-modal-close]', modal ).forEach( function ( btn ) {
			btn.addEventListener( 'click', closeCityModal );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && modal.classList.contains( 'is-open' ) ) {
				closeCityModal();
			}
		} );

		// Выбор города из списка.
		items.forEach( function ( item ) {
			item.addEventListener( 'click', function () {
				chooseCity( item.getAttribute( 'data-city-slug' ), item.getAttribute( 'data-city-name' ) );
			} );
		} );

		// Город не из списка: сохраняем то, что ввёл пользователь.
		var customBtn = qs( '[data-city-custom]', modal );
		if ( customBtn && search ) {
			customBtn.addEventListener( 'click', function () {
				var typed = search.value.trim();
				if ( typed ) {
					chooseCity( '', typed );
				}
			} );
		}

		if ( search ) {
			search.addEventListener( 'input', filter );
		}

		markCurrent();
	}

	/* ==========================================================================
	   13. ПОИСК В ШАПКЕ (ПОДСКАЗКИ ПО НАЗВАНИЮ И БРЕНДУ)
	   ========================================================================== */
	function initSearch() {
		var form = qs( '[data-search]' );
		if ( ! form ) {
			return;
		}
		var input = qs( '[data-search-input]', form );
		var results = qs( '[data-search-results]', form );
		var suggest = qs( '[data-search-suggest]', form );
		var live = qs( '[data-search-live]', form );
		var cancel = qs( '[data-search-cancel]', form );
		var mobile = window.matchMedia( '(max-width: 860px)' );
		var skipFocusOpen = false;
		var index = null;
		var loading = false;
		var searchUrl = ( window.besedkaData && cfg.searchUrl ) ? cfg.searchUrl : '/';
		var MAX_ROWS = 6;

		function norm( text ) {
			return String( text ).toLowerCase().replace( /ё/g, 'е' );
		}

		function escapeHtml( text ) {
			return String( text ).replace( /[&<>"']/g, function ( ch ) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ ch ];
			} );
		}

		// Название с выделением совпавших частей полужирным.
		function highlight( name, tokens ) {
			var lower = norm( name );
			var marks = [];
			tokens.forEach( function ( token ) {
				var from = 0;
				var pos;
				while ( ( pos = lower.indexOf( token, from ) ) !== -1 ) {
					marks.push( [ pos, pos + token.length ] );
					from = pos + token.length;
				}
			} );
			marks.sort( function ( a, b ) {
				return a[ 0 ] - b[ 0 ];
			} );
			var merged = [];
			marks.forEach( function ( m ) {
				var last = merged[ merged.length - 1 ];
				if ( last && m[ 0 ] <= last[ 1 ] ) {
					last[ 1 ] = Math.max( last[ 1 ], m[ 1 ] );
				} else {
					merged.push( [ m[ 0 ], m[ 1 ] ] );
				}
			} );
			var html = '';
			var cursor = 0;
			merged.forEach( function ( m ) {
				html += escapeHtml( name.slice( cursor, m[ 0 ] ) ) + '<mark class="header-search__mark">' + escapeHtml( name.slice( m[ 0 ], m[ 1 ] ) ) + '</mark>';
				cursor = m[ 1 ];
			} );
			return html + escapeHtml( name.slice( cursor ) );
		}

		// Та же разметка строки, что у «Популярных товаров» (besedka_render_search_item).
		function rowHtml( item, tokens ) {
			return '<a class="header-search__item" href="' + escapeHtml( item.url ) + '" data-quickview-trigger data-product-id="' + item.id + '">' +
				'<img class="header-search__thumb" src="' + escapeHtml( item.img ) + '" alt="">' +
				'<span class="header-search__name">' + highlight( item.name, tokens ) + '</span>' +
				'<span class="header-search__price">' + item.price + '</span>' +
				'</a>';
		}

		function loadIndex() {
			if ( index || loading ) {
				return;
			}
			loading = true;
			fetch( cfg.ajaxUrl + '?action=besedka_search_index', { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					index = ( json && json.success && json.data ) ? json.data : [];
					index.forEach( function ( item ) {
						item.hay = norm( item.name + ' ' + item.brand );
						item.flat = item.hay.replace( /\s+/g, '' );
					} );
					loading = false;
					if ( '' !== input.value.trim() && document.activeElement === input ) {
						renderResults();
					}
				} )
				.catch( function () {
					loading = false;
				} );
		}

		function setOpen( open ) {
			results.hidden = ! open;
			input.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		// Режимы взаимоисключающие: подсказки (пустое поле) или результаты (есть ввод).
		function showSuggest() {
			live.hidden = true;
			if ( ! suggest ) {
				setOpen( false );
				return;
			}
			suggest.hidden = false;
			setOpen( true );
		}

		function renderResults() {
			if ( ! index ) {
				loadIndex();
				setOpen( false );
				return;
			}
			var term = input.value.trim();
			var tokens = norm( term ).split( /\s+/ ).filter( Boolean );
			var found = index.filter( function ( item ) {
				return tokens.every( function ( token ) {
					return -1 !== item.hay.indexOf( token ) || -1 !== item.flat.indexOf( token );
				} );
			} );
			found.sort( function ( a, b ) {
				function rank( item ) {
					var name = norm( item.name );
					return name.indexOf( tokens[ 0 ] ) === 0 ? 0 : ( -1 !== name.indexOf( ' ' + tokens[ 0 ] ) ? 1 : 2 );
				}
				return rank( a ) - rank( b );
			} );

			var html;
			if ( ! found.length ) {
				html = '<p class="header-search__empty">' + escapeHtml( ( cfg.i18n && cfg.i18n.nothingFound ) || 'Ничего не найдено' ) + '</p>';
			} else {
				html = found.slice( 0, MAX_ROWS ).map( function ( item ) {
					return rowHtml( item, tokens );
				} ).join( '' );
				html += '<a class="header-search__all" href="' + escapeHtml( searchUrl + encodeURIComponent( term ) ) + '">' +
					escapeHtml( ( cfg.i18n && cfg.i18n.allResults ) || 'Все результаты' ) + ' (' + found.length + ') &rarr;</a>';
			}
			live.innerHTML = html;
			if ( suggest ) {
				suggest.hidden = true;
			}
			live.hidden = false;
			setOpen( true );
		}

		function update() {
			if ( '' === input.value.trim() ) {
				showSuggest();
			} else {
				renderResults();
			}
		}

		function enterFullscreen() {
			if ( mobile.matches && ! form.classList.contains( 'is-fullscreen' ) ) {
				form.classList.add( 'is-fullscreen' );
				lockScroll( true );
			}
		}

		function close() {
			setOpen( false );
			if ( form.classList.contains( 'is-fullscreen' ) ) {
				form.classList.remove( 'is-fullscreen' );
				lockScroll( false );
			}
		}

		function links() {
			return qsa( 'a', results ).filter( function ( link ) {
				return null !== link.offsetParent;
			} );
		}

		input.addEventListener( 'input', update );

		input.addEventListener( 'focus', function () {
			loadIndex();
			enterFullscreen();
			if ( skipFocusOpen ) {
				skipFocusOpen = false;
				return;
			}
			update();
		} );

		input.addEventListener( 'click', function () {
			if ( results.hidden ) {
				update();
			}
		} );

		// Клик внутри окна не должен уводить фокус из поля, иначе окно закроется.
		results.addEventListener( 'mousedown', function ( event ) {
			if ( ! event.target.closest( 'a' ) ) {
				event.preventDefault();
			}
		} );

		results.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-quickview-trigger]' ) ) {
				close();
			}
		} );

		form.addEventListener( 'focusout', function ( event ) {
			if ( ! event.relatedTarget || ! form.contains( event.relatedTarget ) ) {
				close();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! form.contains( event.target ) ) {
				close();
			}
		} );

		form.addEventListener( 'keydown', function ( event ) {
			var key = event.key;

			if ( 'Escape' === key ) {
				if ( ! results.hidden || form.classList.contains( 'is-fullscreen' ) ) {
					close();
					if ( document.activeElement !== input ) {
						skipFocusOpen = true;
						input.focus();
					}
				}
				return;
			}

			if ( 'ArrowDown' !== key && 'ArrowUp' !== key ) {
				return;
			}

			if ( results.hidden ) {
				if ( 'ArrowDown' === key ) {
					event.preventDefault();
					update();
				}
				return;
			}

			var list = links();
			var position = list.indexOf( document.activeElement );
			event.preventDefault();

			if ( 'ArrowDown' === key ) {
				if ( list.length ) {
					list[ Math.min( position + 1, list.length - 1 ) ].focus();
				}
			} else if ( position > 0 ) {
				list[ position - 1 ].focus();
			} else {
				skipFocusOpen = true;
				input.focus();
			}
		} );

		if ( cancel ) {
			cancel.addEventListener( 'click', function () {
				input.blur();
				close();
			} );
		}

		mobile.addEventListener( 'change', function () {
			if ( ! mobile.matches ) {
				close();
			}
		} );
	}

	/* ==========================================================================
	   14. ТАЙМЕР АКЦИИ НА БАННЕРЕ
	   ========================================================================== */
	function initCountdown() {
		qsa( '[data-countdown]' ).forEach( function ( box ) {
			var end = parseInt( box.getAttribute( 'data-countdown' ), 10 ) * 1000;
			var parts = {
				days: qs( '[data-countdown-days]', box ),
				hours: qs( '[data-countdown-hours]', box ),
				minutes: qs( '[data-countdown-minutes]', box ),
				seconds: qs( '[data-countdown-seconds]', box )
			};

			function pad( n ) {
				return n < 10 ? '0' + n : String( n );
			}

			function tick() {
				var left = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );
				if ( left <= 0 ) {
					box.classList.add( 'is-finished' );
					clearInterval( timer );
					return;
				}
				parts.days.textContent = pad( Math.floor( left / 86400 ) );
				parts.hours.textContent = pad( Math.floor( ( left % 86400 ) / 3600 ) );
				parts.minutes.textContent = pad( Math.floor( ( left % 3600 ) / 60 ) );
				parts.seconds.textContent = pad( left % 60 );
			}

			var timer = setInterval( tick, 1000 );
			tick();
		} );
	}

	/* ==========================================================================
	   15. КАРУСЕЛЬ «ГОТОВЫЕ НАБОРЫ»
	   ========================================================================== */
	function initSetsCarousel() {
		var root = qs( '[data-sets-carousel]' );
		if ( ! root ) {
			return;
		}
		var track = qs( '[data-sets-track]', root );
		var tabs = qsa( '[data-sets-tab]', root );
		var prev = qs( '[data-sets-prev]', root );
		var next = qs( '[data-sets-next]', root );
		var count = qsa( '.sets__slide', track ).length;

		function current() {
			return Math.round( track.scrollLeft / track.clientWidth );
		}

		function goTo( index ) {
			index = Math.max( 0, Math.min( count - 1, index ) );
			track.scrollTo( { left: index * track.clientWidth, behavior: 'smooth' } );
		}

		function sync() {
			var index = current();
			tabs.forEach( function ( tab, i ) {
				tab.classList.toggle( 'is-active', i === index );
			} );
		}

		track.addEventListener( 'scroll', sync, { passive: true } );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				goTo( current() - 1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				goTo( current() + 1 );
			} );
		}
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				goTo( parseInt( tab.getAttribute( 'data-sets-tab' ), 10 ) );
			} );
		} );

		sync();
	}

	/* ==========================================================================
	   16. ОБРАТНАЯ СВЯЗЬ ПОСЛЕ «В КОРЗИНУ» (событие стандартного скрипта WooCommerce)
	   ========================================================================== */
	function initAddedFeedback() {
		if ( ! window.jQuery ) {
			return;
		}
		window.jQuery( document.body ).on( 'added_to_cart', function ( event, fragments, hash, $button ) {
			if ( ! $button || ! $button.length ) {
				return;
			}
			var el = $button[ 0 ];
			var original = el.getAttribute( 'data-original-text' ) || el.textContent;
			el.setAttribute( 'data-original-text', original );
			el.textContent = cfg.i18n.added || 'Добавлено';
			setTimeout( function () {
				el.textContent = original;
			}, 1500 );
		} );
	}
})();
