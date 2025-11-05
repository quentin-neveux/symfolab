# Stoppe le serveur Symfony et les processus PHP
symfony server:stop
Get-Process | Where-Object { $_.ProcessName -match "php|symfony" } | Stop-Process -Force -ErrorAction SilentlyContinue

# Supprime les logs Symfony verrouillés
Remove-Item "$env:USERPROFILE\.symfony5\log\*" -Force -ErrorAction SilentlyContinue

# Vide le cache Symfony
php bin/console cache:clear

# Relance le serveur sans TLS pour éviter les conflits
symfony serve -d --no-tls
