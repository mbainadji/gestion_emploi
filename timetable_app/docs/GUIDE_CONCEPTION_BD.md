# Guide de Conception de la Base de Données - Gestion d'Emploi du Temps

Ce document détaille l'architecture et les choix techniques effectués pour la conception de la base de données du projet.

## 1. Philosophie de Conception
La base de données a été conçue selon une approche **relationnelle normalisée**. L'objectif principal est de garantir l'**intégrité des données** (pas d'erreurs de cohérence) et d'éliminer la **redondance** (ne pas stocker la même information plusieurs fois).

## 2. Architecture du Schéma (Modèle Logique)

### A. Les Entités de Base (Référentiels)
Ces tables stockent les ressources fondamentales du système :
- **`users`** : Centralise l'authentification (Admin, Enseignant, Étudiant). Les mots de passe sont hachés en `BCRYPT`.
- **`teachers`** : Profils des enseignants liés à un compte utilisateur.
- **`courses`** : Catalogue des Unités d'Enseignement (UE).
- **`rooms`** : Inventaire des salles avec leur capacité.
- **`classes`** : Groupes d'étudiants (ex: ICTL2) rattachés à une filière (`programs`).
- **`slots`** : Découpage temporel (Lundi 08:00-11:00, etc.).

### B. La Table Pivot Centrale : `timetable`
C'est le "cœur" du système. Elle réalise l'association entre toutes les ressources pour créer une session de cours :
- Elle lie un **Cours** + un **Enseignant** + une **Salle** + une **Classe** + un **Créneau**.
- Elle supporte la gestion par **semaines** et par **groupes** (G1, G2).

### C. Tables de Suivi et Logique
- **`attendance` & `teacher_attendance`** : Suivi des présences.
- **`history`** : Journal d'audit qui enregistre chaque action (INSERT, UPDATE, DELETE) pour une traçabilité totale.

## 3. Mécanismes Avancés (Le "Plus" Technique)

### 3.1 Intégrité Référentielle
L'utilisation de **Clés Étrangères (Foreign Keys)** avec des contraintes assure qu'on ne peut pas supprimer une salle ou un prof s'ils sont déjà programmés dans l'emploi du temps.

### 3.2 Automatisation par Triggers
Un **Trigger** (`trg_timetable_after_delete`) a été mis en place. Dès qu'un cours est supprimé de la table `timetable`, il est automatiquement sauvegardé dans la table `deleted_records_log` au format **JSON**. Cela permet une récupération facile en cas d'erreur humaine (Corbeille).

### 3.3 Optimisation par les Vues (Views)
Pour éviter des requêtes SQL trop longues et complexes dans le code PHP, nous utilisons des **Vues** :
- **`v_timetable_details`** : Rassemble les noms des profs, des salles et des cours en une seule "table virtuelle".
- **`v_teacher_workload`** : Calcule automatiquement le nombre d'heures effectuées par chaque enseignant.

## 4. Sécurité des Données
- **Transactions SQL** : Les opérations critiques sont encapsulées dans des transactions. Si une partie de l'opération échoue, tout est annulé pour éviter des données corrompues.
- **Procédures Stockées** : Des procédures comme `sp_backup_table` permettent de créer des instantanés de sécurité des tables importantes.
