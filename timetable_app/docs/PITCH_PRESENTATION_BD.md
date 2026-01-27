# Pitch / Speech : Soutenance de la Base de Données

Ce document est un guide pour présenter oralement la base de données devant un jury ou un professeur.

---

### Introduction (Accroche)
"Monsieur, pour ce projet de gestion d'emploi du temps, j'ai conçu une base de données MySQL qui ne se contente pas de stocker des données, mais qui garantit activement la cohérence et la sécurité du planning académique."

### 1. La Structure (Le "Quoi")
"L'architecture est de type **relationnelle normalisée**. Le point central est la table `timetable`. Sa force réside dans sa capacité à lier cinq dimensions critiques : l'enseignant, le cours, la salle, le groupe d'étudiants et le créneau horaire. Cette structure permet d'éviter les doublons et facilite les mises à jour."

### 2. La Gestion des Conflits (L'Intégrité)
"Pour assurer l'intégrité, j'ai mis en place des **contraintes de clés étrangères** strictes. De plus, au niveau applicatif et SQL, nous vérifions systématiquement la disponibilité des ressources avant toute insertion. Par exemple, une salle ne peut pas être occupée par deux classes simultanément."

### 3. Les Fonctionnalités Avancées (L'Expertise)
"Ce qui rend cette base robuste, ce sont ses mécanismes automatisés :
1. **La Corbeille Intelligente** : Grâce à un **Trigger**, toute suppression est archivée en format JSON pour permettre une restauration rapide.
2. **L'Audit Trail** : Chaque modification est tracée dans une table d'historique.
3. **Les Vues SQL** : J'ai créé des vues pour simplifier l'affichage des données complexes, ce qui optimise les performances de l'application PHP."

### Conclusion (Ouverture)
"En résumé, c'est une base de données **évolutive**, **sécurisée** et **optimisée** pour répondre aux besoins réels d'un établissement scolaire."

---

### Questions Probables & Réponses :
- **Q : Pourquoi avoir utilisé du JSON pour la corbeille ?**
- *R : "Le format JSON permet de stocker l'intégralité de la ligne supprimée dans une seule colonne, peu importe sa structure, ce qui simplifie énormément le processus de restauration."*

- **Q : Comment gérez-vous les heures de cours différentes (CM, TD, TP) ?**
- *R : "La table `timetable` possède une colonne `type`. De plus, la vue `v_teacher_workload` applique des coefficients différents selon ce type pour calculer la charge de travail réelle."*
