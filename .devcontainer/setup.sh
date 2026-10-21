#!/usr/bin/env bash

set -Eeuo pipefail

WP_TITLE="Беседка"
WP_ADMIN_USER="admin"
WP_ADMIN_PASSWORD="admin"
WP_ADMIN_EMAIL="admin@example.com"

if [[ -n "${CODESPACE_NAME:-}" && -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]]; then
  WP_URL="https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
else
  WP_URL="http://localhost:8080"
fi

echo "== Устанавливаем WP-CLI =="

if ! command -v wp >/dev/null 2>&1; then
  curl -fsSL \
    -o /usr/local/bin/wp \
    https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar

  chmod +x /usr/local/bin/wp
fi

cd /var/www/html

echo "== Ждём готовности MySQL =="

until php -r '
$host = getenv("WORDPRESS_DB_HOST") ?: "db";
$name = getenv("WORDPRESS_DB_NAME") ?: "wordpress";
$user = getenv("WORDPRESS_DB_USER") ?: "wordpress";
$pass = getenv("WORDPRESS_DB_PASSWORD") ?: "wordpress";

mysqli_report(MYSQLI_REPORT_OFF);

$db = @new mysqli($host, $user, $pass, $name);

exit($db->connect_errno ? 1 : 0);
'; do
  echo "MySQL ещё не готов, ждём..."
  sleep 2
done

echo "== MySQL готов =="

echo "== Проверяем WordPress =="

# GitHub Codespaces reverse proxy fix
if ! grep -q "HTTP_X_FORWARDED_HOST" /var/www/html/wp-config.php; then
  sed -i "/require_once ABSPATH . 'wp-settings.php';/i\\
/* GitHub Codespaces reverse proxy */\\
if (!empty(\\\$_SERVER['HTTP_X_FORWARDED_HOST'])) {\\
    \\\$_SERVER['HTTP_HOST'] = \\\$_SERVER['HTTP_X_FORWARDED_HOST'];\\
    \\\$_SERVER['SERVER_NAME'] = preg_replace('/:\\\\d+$/', '', \\\$_SERVER['HTTP_X_FORWARDED_HOST']);\\
}\\
if (!empty(\\\$_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(\\\$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {\\
    \\\$_SERVER['HTTPS'] = 'on';\\
    \\\$_SERVER['REQUEST_SCHEME'] = 'https';\\
    \\\$_SERVER['SERVER_PORT'] = 443;\\
}\\
" /var/www/html/wp-config.php
fi

if ! wp core is-installed --allow-root >/dev/null 2>&1; then
  echo "== Устанавливаем WordPress =="

  wp core install \
    --url="$WP_URL" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email \
    --allow-root
else
  echo "WordPress уже установлен"
fi

echo "== Обновляем адрес сайта =="

wp option update home "$WP_URL" --allow-root
wp option update siteurl "$WP_URL" --allow-root

echo "== Устанавливаем WooCommerce =="

if ! wp plugin is-installed woocommerce --allow-root; then
  wp plugin install woocommerce --allow-root
fi

wp plugin activate woocommerce --allow-root

echo "== Настраиваем валюту (рубли) =="

echo "== Включаем режим Live (без заглушки «Coming soon») =="

wp option update woocommerce_coming_soon no --allow-root
wp option update woocommerce_store_pages_only no --allow-root

wp option update woocommerce_currency RUB --allow-root
wp option update woocommerce_currency_pos right_space --allow-root
wp option update woocommerce_price_num_decimals 0 --allow-root

echo "== Корзина: страницы и настройки WooCommerce =="

wp option update woocommerce_enable_ajax_add_to_cart yes --allow-root
wp option update woocommerce_cart_redirect_after_add no --allow-root

# Создаёт «Корзину», «Оформление заказа» и др., если их ещё нет, и назначает в настройках.
wp wc tool run install_pages --user="$WP_ADMIN_USER" --allow-root || true

CART_ID=$(wp option get woocommerce_cart_page_id --allow-root 2>/dev/null || echo 0)
CHECKOUT_ID=$(wp option get woocommerce_checkout_page_id --allow-root 2>/dev/null || echo 0)

if [[ "$CART_ID" -gt 0 ]]; then
  wp post update "$CART_ID" --post_content='[woocommerce_cart]' --allow-root
  echo "Корзина: $(wp post get "$CART_ID" --field=url --allow-root)"
fi
if [[ "$CHECKOUT_ID" -gt 0 ]]; then
  wp post update "$CHECKOUT_ID" --post_content='[woocommerce_checkout]' --allow-root
  echo "Оформление заказа: $(wp post get "$CHECKOUT_ID" --field=url --allow-root)"
fi

echo "== Активируем тему =="

wp theme activate besedka --allow-root

echo "== Настраиваем ЧПУ =="

wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

echo "== Каталог: страница магазина «Каталог» и постоянные ссылки =="

SHOP_ID=$(wp option get woocommerce_shop_page_id --allow-root 2>/dev/null || echo 0)
if ! [[ "$SHOP_ID" =~ ^[0-9]+$ ]] || [[ "$SHOP_ID" -le 0 ]] || ! wp post get "$SHOP_ID" --field=ID --allow-root >/dev/null 2>&1; then
  SHOP_ID=$(wp post create --post_type=page --post_status=publish --post_title='Каталог' --post_name='catalog' --porcelain --allow-root)
fi
# Страницу «Shop» не удаляем — переименовываем и меняем адрес с shop на catalog.
wp post update "$SHOP_ID" --post_title='Каталог' --post_name='catalog' --allow-root
wp option update woocommerce_shop_page_id "$SHOP_ID" --allow-root

# Категории: /catalog/{категория}/, товары: /catalog/{категория}/{товар}/
wp option update woocommerce_permalinks '{"category_base":"catalog","product_base":"/catalog/%product_cat%","tag_base":"","attribute_base":"","use_verbose_page_rules":false}' --format=json --allow-root

wp rewrite structure '/%postname%/' --hard --allow-root
wp rewrite flush --hard --allow-root

echo "Страница магазина: ID $(wp option get woocommerce_shop_page_id --allow-root), адрес $(wp post get "$SHOP_ID" --field=url --allow-root)"

echo "== Создаём категории =="

declare -A CATEGORIES=(
  [ugol]="Уголь"
  [kubiki]="Дубовые кубики"
  [essencii]="Эссенции"
  [drozhzhi]="Спиртовые дрожжи"
  [komplekt]="Комплектующие"
  [bonifikator]="Бонификаторы"
)

for slug in "${!CATEGORIES[@]}"; do
  if ! wp term get product_cat "$slug" --by=slug --allow-root >/dev/null 2>&1; then
    wp term create \
      product_cat \
      "${CATEGORIES[$slug]}" \
      --slug="$slug" \
      --allow-root
  fi
done

create_product() {
  local name="$1"
  local price="$2"
  local sale="$3"
  local cat="$4"
  local composition="$5"
  local size="$6"
  local hit="$7"
  local new="$8"

  if wp post list \
    --post_type=product \
    --title="$name" \
    --field=ID \
    --allow-root | grep -q .; then
    echo "Товар уже существует: $name"
    return
  fi

  local args=(
    wc product create
    "--name=$name"
    "--type=simple"
    "--regular_price=$price"
    "--categories=[{\"slug\":\"$cat\"}]"
    "--short_description=$composition"
    "--user=$WP_ADMIN_USER"
    --allow-root
    --porcelain
  )

  if [[ -n "$sale" ]]; then
    args+=("--sale_price=$sale")
  fi

  local id
  id=$(wp "${args[@]}")

  wp post meta update "$id" _besedka_composition "$composition" --allow-root
  wp post meta update "$id" _besedka_size "$size" --allow-root

  [[ "$hit" == "1" ]] &&
    wp post meta update "$id" _besedka_is_hit "1" --allow-root

  [[ "$new" == "1" ]] &&
    wp post meta update "$id" _besedka_is_new "1" --allow-root

  echo "Создан товар #$id: $name"
}

echo "== Создаём тестовые товары (плейсхолдеры — замените на реальные) =="

create_product "Уголь кокосовый активированный" "350" "" "ugol" \
  "Активированный кокосовый уголь для очистки браги и дистиллятов" \
  "Фасовка 1000 г" "0" "1"

create_product "Кубики Cognac Mix, славонский дуб" "850" "690" "kubiki" \
  "Дубовые кубики специальной обжарки для облагораживания зерновых дистиллятов" \
  "467 г, 24 месяца сушки" "1" "0"

create_product "Эссенция High Spirits" "250" "" "essencii" \
  "Ароматическая эссенция для дистиллятов и настоек" \
  "Флакон 30 мл" "0" "1"

create_product "Смотровое стекло для колонны" "1200" "" "komplekt" \
  "Комплектующее из нержавеющей стали для самогонного аппарата" \
  "Стандартное присоединение" "0" "0"

create_product "Бонификатор для сахарных дистиллятов" "180" "" "bonifikator" \
  "Улучшает вкус сахарных дистиллятов, питает дрожжи витаминами и минералами" \
  "Пакет 20 г" "0" "1"

echo "== Загружаем товары категории «Спиртовые дрожжи» =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/seed-yeast.php --allow-root

echo "== Пересоздаём миниатюры без обрезки (один раз) =="

if [[ -z "$(wp option get besedka_thumbs_v2 --allow-root 2>/dev/null || true)" ]]; then
  wp media regenerate --yes --allow-root
  wp option update besedka_thumbs_v2 1 --allow-root
fi

echo "== Подключаем фото к товарам =="

# slug (файл в img/categories/<slug>.png) -> название созданного товара
declare -A PRODUCT_PHOTOS=(
  [ugol]="Уголь кокосовый активированный"
  [kubiki]="Кубики Cognac Mix, славонский дуб"
  [essencii]="Эссенция High Spirits"
  [komplekt]="Смотровое стекло для колонны"
  [bonifikator]="Бонификатор для сахарных дистиллятов"
)

for slug in "${!PRODUCT_PHOTOS[@]}"; do
  title="${PRODUCT_PHOTOS[$slug]}"
  img_path="/var/www/html/wp-content/themes/besedka/img/categories/${slug}.png"

  product_id=$(wp post list --post_type=product --title="$title" --field=ID --allow-root | head -1)
  if [[ -z "$product_id" || ! -f "$img_path" ]]; then
    continue
  fi

  current_thumb=$(wp post meta get "$product_id" _thumbnail_id --allow-root 2>/dev/null || true)
  if [[ -z "$current_thumb" ]]; then
    wp media import "$img_path" --post_id="$product_id" --featured_image --title="${slug}-photo" --allow-root
  fi
done

echo "== Устанавливаем ACF (поля рецептов) =="

if ! wp plugin is-installed advanced-custom-fields --allow-root; then
  wp plugin install advanced-custom-fields --allow-root
fi
wp plugin activate advanced-custom-fields --allow-root

echo "== Создаём «Готовые наборы» =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/seed-sets.php --allow-root

echo "== Создаём демо-рецепты =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/seed-recipes.php --allow-root
wp rewrite flush --allow-root

echo "== Вложения: ярлык не должен совпадать с ярлыком категории или страницы =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/fix-attachment-slugs.php --allow-root

echo "== Ярлыки товаров: единый транслит, схема тип-бренд-модель =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/apply-slugs.php --allow-root

echo "== Проверка каталога: категории и ярлыки (отчёт, ничего не меняет) =="

wp eval-file /var/www/html/wp-content/themes/besedka/.devcontainer/check-catalog.php --allow-root || true
wp rewrite flush --hard --allow-root

echo "== Диагностика адресов (код ответа и переадресация) =="

for path in "/catalog/" "/catalog/kubiki/" "/catalog/drozhzhi/" "/catalog/drozhzhi/turbo-drozhzhi-bragoff-48-universal/" "/product-category/kubiki/" "/shop/"; do
  curl -s -o /dev/null -w "%{http_code} ${path} -> %{redirect_url}\n" "http://localhost:8080${path}" || true
done

echo ""
echo "== Готово =="
echo "Сайт: $WP_URL"
echo "Админка: $WP_URL/wp-admin/"
echo "Логин: $WP_ADMIN_USER"
echo "Пароль: $WP_ADMIN_PASSWORD"
