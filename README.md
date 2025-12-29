🚗 EcoRide — Plateforme de covoiturage éco-responsable

Application web réalisée dans le cadre du Titre Professionnel Développeur Web & Web Mobile.
EcoRide permet de proposer, rechercher et réserver des covoiturages en utilisant un système de crédits internes et une logique de gestion complète (trajets, véhicules, avis, employés, administrateurs…).

📦 Technologies utilisées

Symfony 7

PHP 8.2

Twig

Bootstrap 5 / SCSS

Doctrine ORM

MySQL / MariaDB

Docker / Docker Compose

Mailpit (environnement dev)

JavaScript (ES6)

🧱 Architecture générale
ecoride/
│
├── assets/          # Styles, JS, images
├── bin/
├── config/
├── migrations/
├── public/          # Fichiers accessibles publiquement
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Form/
│   ├── Repository/
│   ├── Security/
│   ├── Service/
├── templates/       # Vues Twig
├── docker-compose.yml
└── README.md

🔐 Environnement Docker

L’application fonctionne entièrement via Docker.

Démarrer les containers :
docker compose up -d --build

Accéder aux services :
Service	URL
Application Symfony	http://localhost

Mailpit	http://localhost:8025

phpMyAdmin (si activé)	http://localhost:8080
🧑‍💻 Installation du projet
1. Cloner le projet
git clone https://github.com/quentin-neveux/symfolab
cd ecoride

2. Installer les dépendances PHP
composer install

3. Installer les dépendances front
npm install
npm run build

4. Lancer Docker
docker compose up -d

5. Créer la base et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

🔑 Comptes par défaut (démo)
Administrateur

Email : admin@ecoride.com

Mot de passe : admin123

Rôle : ROLE_ADMIN

Employé (si créé)

Email : employee@ecoride.com

Mot de passe : employee123

Rôle : ROLE_EMPLOYEE

Utilisateur classique

Création via le formulaire d’inscription

20 crédits offerts à la création

🚘 Fonctionnalités principales (US 1 → US 13)
🔹 Recherche & filtre de covoiturages

Recherche par ville, date et autres critères

Filtrage avancé : énergie, prix, durée, note

🔹 Création de trajets (chauffeur)

Gestion de véhicules

Prix libre + commission de 2 crédits pour la plateforme

Places disponibles, horaires, détails

🔹 Participation à un trajet

Vérification crédits

Double confirmation

Déduction automatique des crédits

Mise à jour des places restantes

🔹 Cycle de vie du trajet

Démarrer trajet

Arrêt du trajet

Emails automatiques via Mailpit

Confirmation passagers (OK/NOK)

Avis & notes

🔹 Gestion par les employés (ROLE_EMPLOYEE)

Validation/rejet des avis

Gestion des trajets signalés “mal passés”

🔹 Espace administrateur (ROLE_ADMIN)

Création comptes employés

Suspension comptes utilisateurs

Statistiques :

Trajets/jour

Crédits gagnés/jour

Total crédits plateforme

🗃️ Structure des rôles
Rôle	Permissions
ROLE_USER	voyages, réservations, avis
ROLE_DRIVER	propose trajets, gère véhicules
ROLE_EMPLOYEE	valide avis, traite incidents
ROLE_ADMIN	gère employés, statistiques, suspensions
🧪 Tests

(Optionnel mais conseillé si tu veux étoffer ton README.)

php bin/phpunit

📈 Roadmap (Trello)

Le projet est organisé selon une gestion agile / Kanban :

👉 https://trello.com/invite/b/693aacbc722047a2d28001e1/ATTI5b3d5728983a6c1cc3c7f2d06f94f5c31307D39A/ecoride-gestion-de-projet

Colonnes :

Backlog

À faire

En cours

En review

Terminé

Mergé

Chaque US possède sa branche Git dédiée :
feature/usX-description

🎨 Charte graphique EcoRide

Couleurs :

Vert : #1B3F15

Orange : #FFC77E

Blanc / gris clair

Typographies : Poppins / Roboto

Style : épuré, moderne, éco-responsable

📜 Licence

Projet réalisé dans le cadre du Titre Professionnel DWWM — usage pédagogique.

🙌 Auteur

Quentin N. — Développeur Web & Web Mobile
Projet "EcoRide", 2025.