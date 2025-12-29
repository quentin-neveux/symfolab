# Stoppe le serveur Symfony et les processus PHP
symfony server:stop
Get-Process | Where-Object { $_.ProcessName -match "php|symfony" } | Stop-Process -Force -ErrorAction SilentlyContinue

# Supprime les logs Symfony verrouillés
Remove-Item "$env:USERPROFILE\.symfony5\log\*" -Force -ErrorAction SilentlyContinue

# Vide le cache Symfony
php bin/console cache:clear

# Relance le serveur sans TLS pour éviter les conflits
symfony serve -d --no-tls

# Migrations de la base de données
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Cibler le Container PHP
docker-compose exec php bash

# Nettoyer les caches et ajuster les permissions
rm -rf var/cache/*
chown -R www-data:www-data var
chmod -R 775 var

# Reset des auto-increment des tables de la base de données
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM review;
DELETE FROM trajet_passager;
DELETE FROM trajet;
DELETE FROM vehicle;
DELETE FROM user;

ALTER TABLE review AUTO_INCREMENT = 1;
ALTER TABLE trajet_passager AUTO_INCREMENT = 1;
ALTER TABLE trajet AUTO_INCREMENT = 1;
ALTER TABLE vehicle AUTO_INCREMENT = 1;
ALTER TABLE user AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;


# Générer des données après le reset
php bin/console app:generate-full-dataset -vvv

# Vérification des comptes de chaque table
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM vehicle;"
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM trajet;"
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user;"
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM review;"

