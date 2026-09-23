<div align="center">

# 📦 StockPilot

**Application web de gestion de stock — PHP · MySQL · JavaScript**

Projet réalisé dans le cadre de mon stage PFA chez **Vectorys Logistics**

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![Apache](https://img.shields.io/badge/Apache-D22128?style=flat-square&logo=apache&logoColor=white)
![Status](https://img.shields.io/badge/Status-Terminé-a6835a?style=flat-square)
![License](https://img.shields.io/badge/License-Académique-lightgrey?style=flat-square)

</div>

---

## 📑 Sommaire

- [À propos](#-à-propos)
- [Aperçu](#️-aperçu)
- [Services & rôles](#-services--rôles)
- [Fonctionnalités détaillées](#️-fonctionnalités-détaillées)
- [Stack technique](#️-stack-technique)
- [Architecture & structure du projet](#-architecture--structure-du-projet)
- [Modèle de données](#-modèle-de-données)
- [Installation](#-installation-locale)
- [Sécurité](#-sécurité)
- [Améliorations futures](#-améliorations-futures)
- [Auteur](#-auteur)

---

## 📖 À propos

**StockPilot** est une application web développée pour gérer le stock de consommables d'une entreprise. Le projet couvre l'ensemble du cycle de gestion : articles, fournisseurs, demandes internes, commandes et historique des mouvements.

Le développement a commencé comme une base simple de gestion de stock, puis a évolué progressivement vers un système plus complet avec :
- une gestion des rôles et des permissions,
- une validation automatique des demandes selon la disponibilité du stock,
- un système d'alertes sur les seuils critiques,
- l'export de l'historique en plusieurs formats.

Ce projet a été réalisé en autonomie, de la conception de la base de données jusqu'à l'interface finale.

---

## 🖼️ Aperçu

| Tableau de bord | Gestion des articles |
|:---:|:---:|
| ![Dashboard](screenshots/dashboard.png) | ![Articles](screenshots/articles.png) |

| Gestion des demandes | Historique des mouvements |
|:---:|:---:|
| ![Demandes](screenshots/demandes.png) | ![Historique](screenshots/historique.png) |

---

## 🏢 Services & rôles

L'application repose sur une séparation claire entre les **services** (départements) de l'entreprise et les **rôles** qui y sont rattachés.

| Rôle | Accès | Responsabilités |
|---|---|---|
| 👑 **Administrateur** | Total | Gestion des utilisateurs, des rôles, des plafonds de stock, accès à tous les modules |
| 🛒 **Responsable Achats** | Étendu | Gestion des articles, fournisseurs et commandes, suivi du réapprovisionnement |
| 🏭 **Responsable Service** | Restreint | Création de demandes de consommables pour son service, suivi de leur statut |

**Cycle de vie d'une demande :**
