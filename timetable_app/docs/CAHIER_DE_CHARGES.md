# Cahier des Charges - Système de Gestion d'Emploi du Temps

## 1. Présentation du Projet
Le projet consiste en le développement d'une application web complète pour la gestion des emplois du temps académiques. L'objectif est de remplacer les méthodes manuelles par une solution automatisée, sécurisée et collaborative permettant aux administrateurs, enseignants et étudiants d'interagir avec le planning.

## 2. Objectifs du Système
- **Centralisation** : Regrouper toutes les données liées aux cours, salles, enseignants et classes.
- **Automatisation** : Détecter et prévenir les conflits (salle occupée, enseignant indisponible).
- **Transparence** : Permettre une consultation en temps réel des plannings.
- **Suivi** : Tracer les présences des étudiants et l'assiduité des enseignants.
- **Analyse** : Fournir des indicateurs visuels sur l'occupation des ressources.

## 3. Besoins Fonctionnels

### 3.1 Gestion des Utilisateurs et Accès (RBAC)
- Authentification sécurisée (Hashage des mots de passe).
- Profils : Administrateur, Enseignant, Étudiant.
- Gestion autonome des profils (mise à jour des contacts, avatar).

### 3.2 Planification (Scheduling)
- Création de sessions de cours (CM, TD, TP).
- Vérification automatique de la capacité des salles.
- Gestion des rattrapages avec validation administrative.

### 3.3 Suivi et Présence (Attendance)
- Marquage des présences étudiants par les enseignants.
- Signature numérique (check-in) pour les enseignants.
- Tableau de bord de suivi des enseignants pour l'administration.

### 3.4 Reporting et Export
- Tableau de bord statistique (Chart.js) : charge de travail, répartition des cours.
- Export des calendriers au format ICS (compatible Google Calendar, Outlook).
- Export des plannings en PDF.

### 3.5 Historique et Sécurité
- Journalisation de toutes les actions critiques (Audit Trail).
- Système de "Corbeille" pour restaurer les données supprimées accidentellement.

## 4. Besoins Techniques
- **Langage** : PHP 8.x (Architecture modulaire).
- **Base de données** : MySQL 8.x avec support JSON et procédures stockées.
- **Interface** : HTML5, CSS3, JavaScript (Bootstrap 5 pour le responsive).
- **Sécurité** : Transactions SQL, protection contre les injections SQL (PDO), gestion des sessions.

## 5. Contraintes
- **Disponibilité** : L'application doit être accessible 24h/24.
- **Performance** : Temps de réponse < 2s pour les recherches de conflits.
- **Maintenance** : Code commenté et structuré pour une évolution facile.
