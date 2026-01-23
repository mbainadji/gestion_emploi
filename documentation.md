# Cahier de conception et d'implémentation - Application de Gestion d'Emploi du Temps

## 1. Introduction
Cette application web permet de gérer de manière optimale les emplois du temps au sein d'un établissement universitaire (cas d'étude : Université de Yaoundé I). Elle gère les contraintes de salles, les effectifs des classes, les désidératas des enseignants, et offre des outils d'arbitrage pour l'administration.

## 2. Fonctionnalités
- **Authentification Sécurisée** : Gestion des sessions, hachage des mots de passe, et rôles (Admin, Enseignant, Étudiant).
- **Paramétrage** : Années académiques, semestres, départements, filières, classes (effectifs).
- **Gestion des Ressources** :
  - **Enseignants** : CRUD, affectation des UE.
  - **Salles** : CRUD, capacités.
- **Espace Enseignant** :
  - Soumission des désidératas (préférences horaires).
  - Consultation de son emploi du temps personnel.
  - Programmation de sessions supplémentaires (TD/TP/Rattrapage).
- **Planification & Arbitrage (Admin)** :
  - Création d'emplois du temps avec détection automatique des conflits (Salle, Enseignant, Classe).
  - Module d'arbitrage pour visualiser et résoudre les incohérences.
  - Support des semaines alternées (Semaine 1 / Semaine 2).
- **Consultation Publique** : Vues filtrables par classe, enseignant, salle et semestre.
- **Traçabilité & Notifications** :
  - Historique complet des actions (Qui a fait quoi et quand).
  - Système de notifications internes pour les utilisateurs.

## 3. Architecture Technique
- **Backend** : PHP 8.x
- **Base de données** : MySQL / MariaDB
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Sécurité** : PDO (Prepared Statements), Password Hashing (bcrypt), Protection CSRF (via sessions).

## 4. Installation et Lancement
1. **Pré-requis** : Serveur Web (Apache/Nginx), PHP 8+, MySQL.
2. **Base de données** :
   - Créer une base de données nommée `timetable` (ou autre, à configurer).
   - Importer le fichier `database_mysql.sql` pour créer la structure et les données initiales.
   - Exécuter le script `data_ictl2.sql` (si fourni séparément) pour peupler l'emploi du temps exemple.
3. **Configuration** :
   - Modifier le fichier `timetable_app/includes/config.php` avec vos identifiants de base de données (`$host`, `$db`, `$user`, `$pass`).
4. **Lancement** :
   - Placer le dossier du projet dans le répertoire racine du serveur web (ex: `htdocs` ou `/var/www/html`).
   - Accéder à l'application via `http://localhost/gestion_Emploi/index.php`.

## 5. Comptes de démonstration
- **Admin** : `admin` / `admin123` (Mot de passe haché dans la DB)
- **Enseignant** : `monthe` / `pass123` (Mot de passe haché dans la DB)

## 6. Démonstration ICT-L2
L'emploi du temps de la classe ICT-L2 pour le Semestre 1 2025/2026 est entièrement modélisé, incluant :
- Les cours en amphithéâtre (S003/S008).
- La gestion des groupes (G1/G2).
- L'alternance des semaines (ex: Vendredi 15h).

## 7. Améliorations Futures et Roadmap Technique
Pour évoluer vers une version de production robuste, les éléments suivants sont identifiés :
- **Exports** : Génération des emplois du temps au format PDF (ex: via TCPDF ou DomPDF) et Excel.
- **Tests Automatisés** : Ajout de tests unitaires (PHPUnit) pour valider l'algorithme de détection de conflits.
- **Déploiement** : Configuration pour serveurs Web de production (Apache/Nginx) et conteneurisation (Docker).
- **API REST** : Création d'une API pour permettre le développement d'une application mobile.

## 8. Structure du Projet
```
gestion_Emploi/
├── database_mysql.sql       # Script SQL de structure et données
├── documentation.md         # Ce fichier
├── index.php                # Portail d'accueil public
└── timetable_app/
    ├── dashboard.php        # Tableau de bord utilisateur
    ├── assets/              # CSS, JS, Images
    ├── includes/            # Fichiers partagés (config, header, footer)
    └── modules/             # Modules fonctionnels
        ├── academics/       # Gestion académique (Admin)
        ├── accounts/        # Auth (Login, Register, Logout)
        ├── arbitration/     # Gestion des conflits (Admin)
        ├── history/         # Logs et audit (Admin)
        ├── notifications/   # Système de notifications
        ├── preferences/     # Désidératas (Enseignant)
        ├── rooms/           # Gestion des salles (Admin)
        ├── scheduling/      # Planification (Admin)
        ├── teachers/        # Gestion des enseignants (Admin/Teacher)
        └── views/           # Consultation des emplois du temps
```
