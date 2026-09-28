FROM wordpress:php8.2-apache

RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' \
       /etc/apache2/sites-available/000-default.conf

# git нужен внутри контейнера для `git pull` в терминале Codespaces —
# в базовом образе wordpress:php8.2-apache его нет.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/*

EXPOSE 8080
