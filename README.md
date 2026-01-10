# EcoRide (Symfolab)

EcoRide est une application de covoiturage à vocation éco‑responsable, développée avec **Symfony** dans le cadre d’un projet long de formation (*Titre Professionnel Développeur Web & Web Mobile*).

Le projet est actuellement **en développement** et fonctionne uniquement **en local**, via Docker. Il n’est **pas encore déployé en production**.

---

## Présentation générale

L’objectif d’EcoRide est de mettre en place une plateforme de covoiturage réaliste, avec des parcours utilisateurs complets : inscription, recherche de trajets, réservation, gestion des rôles, et système de crédits internes (tokens).

Le projet sert avant tout de support technique pour travailler :

* l’architecture Symfony moderne,
* la conception et l’évolution d’une base de données relationnelle,
* la logique métier côté back‑end,
* et un environnement de développement Dockerisé cohérent.

---

## État actuel du projet

* Application fonctionnelle en **local**
* Environnement **Docker** opérationnel
* Base de données **MariaDB**
* Administration via **phpMyAdmin**
* Gestion des emails en local avec **Mailpit**

Le projet a volontairement été maintenu sur MariaDB à ce stade. Aucune migration PostgreSQL n’est utilisée actuellement.

---

## Stack technique

* **Back‑end** : PHP 8.x, Symfony 7.x
* **Front‑end** : Twig, Bootstrap, JavaScript
* **Base de données** : MariaDB 11
* **Serveur** : Nginx
* **Conteneurisation** : Docker & Docker Compose
* **Outils annexes** : phpMyAdmin, Mailpit

---

## Lancement du projet en local

### Prérequis

* Docker
* Docker Compose

### Installation

1. Cloner le dépôt
2. Copier le fichier `.env` si nécessaire et ajuster les variables
3. Lancer les conteneurs :

```bash
docker compose up -d --build
```

4. Installer les dépendances PHP :

```bash
docker compose exec php composer install
```

5. Lancer les migrations si besoin :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

---

## Accès aux services

* Application : [http://localhost:8888](http://localhost:8888)
* phpMyAdmin : [http://localhost:8082](http://localhost:8082)
* Mailpit : [http://localhost:8025](http://localhost:8025)

---

## Notes importantes

* Le projet n’est **pas finalisé** et évolue régulièrement.
* Il n’existe pas encore de version de production.
* Certaines fonctionnalités peuvent être en cours de refactorisation.

Ce dépôt reflète l’état réel du travail, sans maquillage.

---

## Contexte
EcoRide est développé comme projet fil rouge dans un cadre de formation, avec une attention particulière portée à la compréhension des mécanismes plutôt qu’à la seule livraison rapide.
Il est amené à évoluer, techniquement comme fonctionnellement.

Quentin N. — Développeur Web & Web Mobile
Projet "EcoRide", 2025-2026.
