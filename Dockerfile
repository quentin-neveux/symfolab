# ============================================================
# 🐘 DOCKERFILE POUR ECORIDE (Symfony + PHP 8.2)
# ============================================================

# Étape 1 : Base PHP-FPM avec extensions utiles à Symfony
FROM php:8.2-fpm

# ------------------------------------------------------------
# Installation des dépendances système et extensions PHP
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev zip curl \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache gd

# ------------------------------------------------------------
# Installation de Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# Copie du code Symfony dans le conteneur
# ------------------------------------------------------------
WORKDIR /var/www/html
COPY . .

# ------------------------------------------------------------
# Installation des dépendances PHP (prod uniquement)
# ------------------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ------------------------------------------------------------
# Optimisations Symfony pour la prod
# ------------------------------------------------------------
RUN php bin/console cache:clear --env=prod && \
    php bin/console cache:warmup --env=prod && \
    chown -R www-data:www-data /var/www/html/var

# ------------------------------------------------------------
# Expose le port 8080 (Render attend $PORT)
# ------------------------------------------------------------
EXPOSE 8080

# ------------------------------------------------------------
# Commande de démarrage : PHP intégré
# ------------------------------------------------------------
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
