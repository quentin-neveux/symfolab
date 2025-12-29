FROM php:8.3-fpm-bullseye

# ------------------------------------------------------------
# Extensions nécessaires à Symfony
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libzip-dev curl \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache gd

# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# UID / GID (ALIGNÉS AVEC WINDOWS)
# ------------------------------------------------------------
ARG UID=1000
ARG GID=1000

RUN groupmod -g ${GID} www-data \
 && usermod -u ${UID} -g ${GID} www-data

# ------------------------------------------------------------
# Dossier de travail
# ------------------------------------------------------------
WORKDIR /var/www/html

# ------------------------------------------------------------
# Dépendances (cache Docker)
# ------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts

# ------------------------------------------------------------
# Projet
# ------------------------------------------------------------
COPY . .

# ------------------------------------------------------------
# Permissions FINALES
# ------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html

# ------------------------------------------------------------
# Runtime
# ------------------------------------------------------------
USER www-data
CMD ["php-fpm"]
EXPOSE 9000
