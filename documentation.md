# Cahier de conception et d'implémentation - Application de Gestion d'Emploi du Temps

## 1. Introduction
Cette application permet de gérer les emplois du temps au sein d'un établissement universitaire. Elle gère les contraintes de salles, les effectifs des classes, et les désidératas des enseignants.

## 2. Fonctionnalités
- **Authentification** : Rôles Admin, Enseignant, Étudiant.
- **Paramétrage** : Années académiques, semestres, départements, filières, classes (effectifs).
- **Gestion des Enseignants** : Affectation des UE aux enseignants.
- **Salles** : Gestion des capacités et types de salles.
- **Désidératas** : Soumission et modification des préférences horaires par les enseignants.
- **Planification** : Création d'emplois du temps avec vérification automatique des conflits (salle, enseignant, classe).
- **Arbitrage** : Possibilité pour l'administrateur de modifier les choix des enseignants.
- **Consultation** : Vues par classe, enseignant, salle.

## 3. Architecture Technique
- **Backend** : PHP 8.x
- **Base de données** : SQLite 3 (SQL)
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)

## 4. Installation et Lancement
1. S'assurer que PHP (avec l'extension pdo_sqlite) est installé.
2. Extraire les fichiers de l'application.
3. Le fichier `timetable.db` contient déjà la structure et les données de démonstration.
4. Lancer un serveur local :
   ```bash
   php -S localhost:8000
   ```
5. Accéder à l'application via `http://localhost:8000/timetable_app/index.php`.

## 5. Comptes de démonstration
- **Admin** : `admin` / `admin123`
- **Enseignant** : `monthe` / `pass123`

## 6. Démonstration ICT-L2
L'emploi du temps de la classe ICT-L2 pour le Semestre 1 2025/2026 a été pré-rempli avec quelques exemples (ICT207, ICT203, ENG203) illustrant le respect des contraintes.
