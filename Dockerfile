# ============================================================
# 🐘 DOCKERFILE POUR SYMFONY (PHP-FPM 8.3)
# ============================================================

FROM php:8.3-fpm

# ------------------------------------------------------------
# Extensions nécessaires à Symfony
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libzip-dev curl \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache gd

# ------------------------------------------------------------
# Installation de Composer (copié depuis l’image officielle)
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# Dossier de travail
# ------------------------------------------------------------
WORKDIR /var/www/html

# ------------------------------------------------------------
# Copie des fichiers (composer.json AVANT le reste = cache Docker)
# ------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts

# Maintenant on copie le reste du projet
COPY . .

# ------------------------------------------------------------
# Fix des droits pour Symfony
# ------------------------------------------------------------
RUN chown -R www-data:www-data var/ vendor/

# ------------------------------------------------------------
# PHP-FPM comme process principal
# ------------------------------------------------------------
CMD ["php-fpm"]

EXPOSE 9000
