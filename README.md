EcoRide (Symfolab)

EcoRide est une application de covoiturage à vocation éco-responsable, développée avec Symfony, dans le cadre du projet long du Titre Professionnel Développeur Web & Web Mobile.

L’application est déployée sur une infrastructure AWS, avec résolution de domaine via DuckDNS, afin de permettre une consultation publique dans un cadre pédagogique.

Présentation

EcoRide propose une plateforme complète de covoiturage intégrant :

inscription et authentification des utilisateurs,

gestion des rôles (passager, conducteur, administrateur),

recherche et réservation de trajets,

gestion des véhicules,

système de crédits internes (tokens),

espace d’administration,

gestion des emails transactionnels.

Le projet met l’accent sur la cohérence de l’architecture, la logique métier et la sécurité des accès.

Accès de démonstration (jury)

Les services suivants sont accessibles pour la démonstration du projet :

🌍 Application EcoRide
https://ecoride-app-studi.duckdns.org

📧 Mailpit (emails de test)
http://ecoride-app-studi.duckdns.org:8026

🗄️ Base de données (phpMyAdmin)
http://ecoride-app-studi.duckdns.org:8082

Ces accès sont fournis uniquement dans un cadre pédagogique et de démonstration.

État du projet

Application fonctionnelle

Déploiement Docker sur serveur AWS

Domaine dynamique via DuckDNS

Base de données MariaDB

Gestion des emails via Mailpit

Sécurité Symfony (authentification, rôles, contrôle d’accès)

Interface d’administration dédiée

Stack technique

PHP 8.x — Symfony 7.x

Twig, Bootstrap, JavaScript

MariaDB 11

Nginx

Docker & Docker Compose

Hébergement : AWS (EC2)

DNS : DuckDNS

Positionnement pédagogique

EcoRide est un projet de formation, conçu pour démontrer :

la maîtrise d’un framework back-end moderne,

la structuration d’une application web complète,

la mise en place d’une logique métier réaliste,

la gestion d’une base de données relationnelle,

la sécurisation d’une application web,

et le déploiement d’une application fonctionnelle.

Remarque importante

Ce projet n’a pas vocation à être exploité en production commerciale.
Il représente un travail pédagogique long, évolutif et volontairement transparent.

Auteur

Quentin Neveux
Développeur Web & Web Mobile
Projet EcoRide — 2025-2026
