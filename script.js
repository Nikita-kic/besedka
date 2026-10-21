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
		initCustomOrderForm();
		initCityModal();
		initSearch();
		initCountdown();
		initSetsCarousel();
		initAddedFeedback();
		initCatalogPage();
		initProductPage();
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
			return '<a class="header-search__item" href="' + escapeHtml( item.url ) + '" data-product-id="' + item.id + '">' +
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

	/* ==========================================================================
	   17. СТРАНИЦА КАТЕГОРИИ: ФИЛЬТРЫ, ШТОРКА, СОРТИРОВКА
	   ========================================================================== */
	function initCatalogPage() {
		var aside = qs( '[data-catalog-aside]' );
		if ( ! aside ) {
			return;
		}
		var overlay = qs( '[data-catalog-overlay]' );
		var sheet = qs( '[data-catalog-sort-sheet]' );
		var form = qs( '[data-catalog-filters]' );
		var apply = qs( '[data-catalog-apply]' );
		var sortSelect = qs( '[data-catalog-sort]' );
		var narrow = window.matchMedia( '(max-width: 1024px)' );
		var countTimer = null;

		function closePanels() {
			[ aside, sheet, overlay ].forEach( function ( el ) {
				if ( el ) {
					el.classList.remove( 'is-open' );
				}
			} );
			lockScroll( false );
		}

		function openPanel( panel ) {
			closePanels();
			panel.classList.add( 'is-open' );
			overlay.classList.add( 'is-open' );
			lockScroll( true );
		}

		qsa( '[data-catalog-open]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				openPanel( 'sort' === btn.getAttribute( 'data-catalog-open' ) ? sheet : aside );
			} );
		} );

		qsa( '[data-catalog-close]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', closePanels );
		} );

		if ( overlay ) {
			overlay.addEventListener( 'click', closePanels );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && aside.classList.contains( 'is-open' ) ) {
				closePanels();
			}
		} );

		narrow.addEventListener( 'change', function () {
			if ( ! narrow.matches ) {
				closePanels();
			}
		} );

		// Сортировка: меняем orderby в адресе, фильтры сохраняются.
		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				var params = new URLSearchParams( window.location.search );
				params.set( 'orderby', sortSelect.value );
				params.delete( 'paged' );
				var path = window.location.pathname.replace( /page\/\d+\/?$/, '' );
				window.location.href = path + '?' + params.toString();
			} );
		}

		if ( ! form ) {
			return;
		}

		function stripEmpty() {
			qsa( 'input[type="number"]', form ).forEach( function ( input ) {
				input.disabled = '' === input.value;
			} );
		}

		function updateCount() {
			if ( ! apply ) {
				return;
			}
			clearTimeout( countTimer );
			countTimer = setTimeout( function () {
				var params = new URLSearchParams( new FormData( form ) );
				params.set( 'action', 'besedka_catalog_count' );
				params.set( 'category', form.getAttribute( 'data-category' ) || '' );
				fetch( cfg.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' } )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( json ) {
						if ( json && json.success ) {
							apply.textContent = json.data.label;
						}
					} )
					.catch( function () {} );
			}, 200 );
		}

		form.addEventListener( 'submit', stripEmpty );

		form.addEventListener( 'change', function ( event ) {
			var chip = event.target.closest( '.chip' );
			if ( chip ) {
				chip.classList.toggle( 'is-active', event.target.checked );
			}
			if ( narrow.matches ) {
				updateCount();
			} else {
				stripEmpty();
				form.submit();
			}
		} );
	}

	/* ==========================================================================
	   18. СТРАНИЦА ТОВАРА: ГАЛЕРЕЯ, КОЛИЧЕСТВО, ВКЛАДКИ, ОТЗЫВЫ, ОКНО «НАПИСАТЬ ОТЗЫВ»
	   ========================================================================== */
	function initProductPage() {
		var root = qs( '[data-pp]' );
		if ( ! root ) {
			return;
		}

		/* --- Галерея: клик по миниатюре подменяет главное фото --- */
		var mainImage = qs( '[data-pp-main]', root );
		var thumbs = qsa( '[data-pp-thumb]', root );
		thumbs.forEach( function ( thumb ) {
			thumb.addEventListener( 'click', function () {
				if ( mainImage ) {
					mainImage.src = thumb.getAttribute( 'data-src' );
				}
				thumbs.forEach( function ( t ) {
					t.classList.toggle( 'is-active', t === thumb );
				} );
			} );
		} );

		/* --- Количество: все счётчики на странице синхронны, значение уходит в data-quantity кнопок --- */
		var qty = 1;
		function applyQty() {
			qsa( '[data-pp-qty]', root ).forEach( function ( input ) {
				input.value = qty;
			} );
			qsa( '.add_to_cart_button', root ).forEach( function ( btn ) {
				btn.setAttribute( 'data-quantity', qty );
			} );
		}
		root.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-pp-minus]' ) ) {
				qty = Math.max( 1, qty - 1 );
				applyQty();
			} else if ( event.target.closest( '[data-pp-plus]' ) ) {
				qty += 1;
				applyQty();
			}
		} );

		/* --- Вкладки с якорями #description / #specs / #reviews --- */
		var tabs = qsa( '[data-pp-tab]', root );
		var panels = qsa( '[data-pp-panel]', root );
		function showTab( key, updateHash ) {
			var found = panels.some( function ( p ) {
				return p.getAttribute( 'data-pp-panel' ) === key;
			} );
			if ( ! found ) {
				key = 'description';
			}
			panels.forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-pp-panel' ) !== key;
			} );
			tabs.forEach( function ( tab ) {
				tab.classList.toggle( 'is-active', tab.getAttribute( 'data-pp-tab' ) === key );
			} );
			if ( updateHash && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + key );
			}
		}
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				showTab( tab.getAttribute( 'data-pp-tab' ), true );
			} );
		} );
		function fromHash() {
			var key = window.location.hash.replace( '#', '' );
			showTab( key, false );
			if ( key && qs( '#' + key ) && panels.length ) {
				qs( '[data-pp-tabs]', root ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		}
		window.addEventListener( 'hashchange', fromHash );
		showTab( window.location.hash.replace( '#', '' ), false );
		if ( window.location.hash ) {
			setTimeout( fromHash, 0 );
		}

		/* --- Отзывы: показываем 3, «Показать все»; фильтр «Новые» / «Негативные» --- */
		var list = qs( '[data-pp-review-list]', root );
		var reviews = list ? qsa( '.pp-review', list ) : [];
		var expanded = false;
		var filter = 'new';
		function renderReviews() {
			var shown = 0;
			reviews.forEach( function ( review ) {
				var rating = parseInt( review.getAttribute( 'data-rating' ), 10 ) || 0;
				var match = 'negative' === filter ? rating <= 3 : true;
				var visible = match && ( expanded || shown < 3 );
				review.hidden = ! visible;
				if ( match ) {
					shown++;
				}
			} );
			var more = qs( '[data-pp-review-more]', root );
			if ( more ) {
				more.hidden = expanded || shown <= 3;
			}
		}
		if ( reviews.length ) {
			renderReviews();
			var more = qs( '[data-pp-review-more]', root );
			if ( more ) {
				more.addEventListener( 'click', function () {
					expanded = true;
					renderReviews();
				} );
			}
			qsa( '[data-pp-filter]', root ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					filter = btn.getAttribute( 'data-pp-filter' );
					qsa( '[data-pp-filter]', root ).forEach( function ( b ) {
						b.classList.toggle( 'is-active', b === btn );
					} );
					renderReviews();
				} );
			} );
		}

		/* --- «С этим покупают»: стрелки листают ленту --- */
		var track = qs( '[data-pp-cross-track]', root );
		if ( track ) {
			var step = function ( dir ) {
				var card = qs( '.cross-card', track );
				var width = card ? card.getBoundingClientRect().width : track.clientWidth;
				track.scrollBy( { left: dir * ( width + 16 ), behavior: 'smooth' } );
			};
			var prev = qs( '[data-pp-cross-prev]', root );
			var next = qs( '[data-pp-cross-next]', root );
			if ( prev ) {
				prev.addEventListener( 'click', function () {
					step( -1 );
				} );
			}
			if ( next ) {
				next.addEventListener( 'click', function () {
					step( 1 );
				} );
			}
		}

		/* --- Окно «Написать отзыв» --- */
		var modal = qs( '[data-review-modal]', root );
		var form = qs( '[data-review-form]', root );
		var thanks = qs( '[data-review-thanks]', root );
		if ( ! modal || ! form ) {
			return;
		}
		var captions = [ '', 'Плохо', 'Так себе', 'Нормально', 'Хорошо', 'Отлично' ];
		var starButtons = qsa( '[data-star]', form );
		var ratingInput = qs( '[data-review-rating]', form );
		var caption = qs( '[data-review-caption]', form );

		function setRating( value ) {
			ratingInput.value = value;
			starButtons.forEach( function ( star ) {
				var n = parseInt( star.getAttribute( 'data-star' ), 10 );
				star.classList.toggle( 'is-on', n <= value );
				star.setAttribute( 'aria-checked', n === value ? 'true' : 'false' );
			} );
			caption.textContent = captions[ value ] || '';
		}

		function clearErrors() {
			qsa( '[data-error-for]', form ).forEach( function ( el ) {
				el.hidden = true;
				el.textContent = '';
			} );
		}

		function showErrors( errors ) {
			Object.keys( errors ).forEach( function ( field ) {
				var el = qs( '[data-error-for="' + field + '"]', form );
				if ( el ) {
					el.textContent = errors[ field ];
					el.hidden = false;
				}
			} );
		}

		function openReview() {
			form.hidden = false;
			thanks.hidden = true;
			clearErrors();
			modal.classList.add( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'false' );
			lockScroll( true );
		}

		function closeReview() {
			modal.classList.remove( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'true' );
			lockScroll( false );
		}

		qsa( '[data-review-open]', root ).forEach( function ( btn ) {
			btn.addEventListener( 'click', openReview );
		} );
		qsa( '[data-review-close]', modal ).forEach( function ( btn ) {
			btn.addEventListener( 'click', closeReview );
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && modal.classList.contains( 'is-open' ) ) {
				closeReview();
			}
		} );
		starButtons.forEach( function ( star ) {
			star.addEventListener( 'click', function () {
				setRating( parseInt( star.getAttribute( 'data-star' ), 10 ) );
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			clearErrors();

			var errors = {};
			if ( ! ratingInput.value ) {
				errors.rating = 'Поставьте оценку';
			}
			if ( ! form.elements.content.value.trim() ) {
				errors.content = 'Расскажите, как всё прошло';
			}
			if ( ! form.elements.name.value.trim() ) {
				errors.name = 'Укажите имя';
			}
			if ( ! form.elements.consent.checked ) {
				errors.consent = 'Нужно ваше согласие';
			}
			if ( Object.keys( errors ).length ) {
				showErrors( errors );
				return;
			}

			var data = new FormData( form );
			data.append( 'action', 'besedka_submit_review' );
			data.append( 'nonce', form.getAttribute( 'data-nonce' ) );
			data.append( 'product_id', form.getAttribute( 'data-product-id' ) );

			var submit = qs( '[type="submit"]', form );
			submit.disabled = true;
			fetch( cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					submit.disabled = false;
					if ( json && json.success ) {
						form.hidden = true;
						thanks.hidden = false;
						form.reset();
						setRating( 0 );
					} else if ( json && json.data && json.data.errors ) {
						showErrors( json.data.errors );
					} else {
						showErrors( { form: ( json && json.data && json.data.message ) || 'Не удалось отправить отзыв, попробуйте ещё раз' } );
					}
				} )
				.catch( function () {
					submit.disabled = false;
					showErrors( { form: 'Не удалось отправить отзыв, попробуйте ещё раз' } );
				} );
		} );
	}
})();
