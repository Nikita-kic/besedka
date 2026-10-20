<?php
/**
 * Ярлыки (slug) товаров: «название товара» => ярлык.
 * Транслит: х→h, ц→ts, ч→ch, ш→sh, щ→sch, ж→zh, й→y, ы→y, я→ya, ю→yu, ё/е→e, ь/ъ убраны.
 * Схема: тип-бренд-модель; наборы начинаются с nabor-.
 * Применяется .devcontainer/apply-slugs.php, сверяется .devcontainer/check-catalog.php.
 *
 * @package Besedka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'Турбо-дрожжи High Spirits 100 SOTKA' => 'turbo-drozhzhi-high-spirits-100-sotka',
	'Турбо-дрожжи High Spirits 200 2x SOTKA' => 'turbo-drozhzhi-high-spirits-200-2x-sotka',
	'Турбо-дрожжи High Spirits 24 Extreme' => 'turbo-drozhzhi-high-spirits-24-extreme',
	'Турбо-дрожжи High Spirits 48 Classic' => 'turbo-drozhzhi-high-spirits-48-classic',
	'Турбо-дрожжи High Spirits F Fruit' => 'turbo-drozhzhi-high-spirits-f-fruit',
	'Турбо-дрожжи High Spirits H3 Universal' => 'turbo-drozhzhi-high-spirits-h3-universal',
	'Турбо-дрожжи High Spirits W Whiskey' => 'turbo-drozhzhi-high-spirits-w-whiskey',
	'Турбо-дрожжи High Spirits Мск Exclusive' => 'turbo-drozhzhi-high-spirits-msk-exclusive',
	'Дрожжи спиртовые Лёгкие Зерновые' => 'drozhzhi-legkie-zernovye',
	'Дрожжи спиртовые Лёгкие Сахарные' => 'drozhzhi-legkie-saharnye',
	'Дрожжи спиртовые Лёгкие Универсальные' => 'drozhzhi-legkie-universalnye',
	'Дрожжи спиртовые Лёгкие Фруктовые' => 'drozhzhi-legkie-fruktovye',
	'Турбо-дрожжи BRAGOFF 24 Quick' => 'turbo-drozhzhi-bragoff-24-quick',
	'Турбо-дрожжи BRAGOFF 48 Universal' => 'turbo-drozhzhi-bragoff-48-universal',
	'Турбо-дрожжи BRAGOFF 72 Gold Standart' => 'turbo-drozhzhi-bragoff-72-gold-standard',
	'Турбо-дрожжи BRAGOFF Москва Original' => 'turbo-drozhzhi-bragoff-moskva-original',
	'Уголь кокосовый активированный' => 'ugol-kokosovyy-aktivirovannyy',
	'Кубики Cognac Mix, славонский дуб' => 'kubiki-cognac-mix-slavonskiy-dub',
	'Эссенция High Spirits' => 'essentsiya-high-spirits',
	'Смотровое стекло для колонны' => 'smotrovoe-steklo-dlya-kolonny',
	'Бонификатор для сахарных дистиллятов' => 'bonifikator-dlya-saharnyh-distillyatov',
	'Набор для сахарной браги' => 'nabor-dlya-saharnoy-bragi',
	'Облагораживание' => 'nabor-oblagorazhivanie',
	'Фруктовый набор' => 'nabor-fruktovyy',
	'Зерновой дистиллят' => 'nabor-zernovoy-distillyat',
);
