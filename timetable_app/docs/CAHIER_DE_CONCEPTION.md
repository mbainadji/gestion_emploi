# Cahier de Conception - Système de Gestion d'Emploi du Temps

## 1. Architecture Logicielle
L'application suit une structure modulaire pour séparer les responsabilités :
- **Includes/** : Cœur de l'application (configuration, connexion BD, fonctions globales, en-têtes).
- **Modules/** : Dossiers regroupant les fonctionnalités par domaine (academics, scheduling, reports, etc.).
- **Assets/** : Ressources statiques (CSS, JS, Images).

## 2. Conception de la Base de Données (MySQL)

### 2.1 Schéma Relationnel Principal
- **users** : Utilisateurs du système (Admin, Teacher, Student).
- **teachers** : Détails spécifiques aux enseignants liés aux utilisateurs.
- **timetable** : Table pivot centrale reliant `courses`, `rooms`, `teachers`, `slots`, et `classes`.
- **attendance** : Enregistrement des présences étudiants liés à une session de `timetable`.
- **teacher_attendance** : Suivi de la présence effective des enseignants.

### 2.2 Fonctionnalités Avancées
- **Vues (Views)** :
    - `v_timetable_details` : Jointure complexe pour un affichage simplifié du planning.
    - `v_teacher_workload` : Agrégation des heures par enseignant.
- **Triggers** :
    - `trg_timetable_after_delete` : Archivage automatique dans `deleted_records_log` pour récupération.
- **Transactions** : Utilisation systématique de `BEGIN TRANSACTION` pour les opérations critiques (insertion de planning + log).

## 3. Conception de l'Interface (UI)
- **Framework** : Bootstrap 5 pour un design "mobile-first".
- **Navigation** : Barre latérale ou menu d'en-tête dynamique selon le rôle de l'utilisateur.
- **Visualisation** : Intégration de `Chart.js` pour les statistiques de charge de travail et d'occupation des salles.

## 4. Logique Métier Critique

### 4.1 Algorithme de Détection de Conflits
Avant chaque insertion dans `timetable`, le système vérifie :
1. Si l'enseignant est déjà occupé sur le même créneau (`slot_id`).
2. Si la salle (`room_id`) est déjà réservée.
3. Si la classe (`class_id`) a déjà un cours prévu.
4. Si la capacité de la salle est suffisante pour l'effectif de la classe.

### 4.2 Système de Récupération (Recycle Bin)
La table `deleted_records_log` stocke les données supprimées au format JSON. Une procédure stockée peut être appelée pour restaurer ces données directement dans la table d'origine.

## 5. Sécurité et Robustesse
- **Authentification** : `password_hash()` et `password_verify()`.
- **Accès** : Middleware PHP simple (`requireRole()`) au début de chaque module sensible.
- **Intégrité** : Clés étrangères MySQL pour garantir la cohérence des données lors des suppressions.
- **Sauvegarde** : Procédures stockées `sp_backup_table` pour snapshots manuels.
