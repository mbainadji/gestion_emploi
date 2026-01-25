@echo off
REM Démarrer les conteneurs
docker-compose up -d

REM Ouvrir le navigateur
echo L'application demarre sur http://localhost:8080
timeout /t 5
start http://localhost:8080/timetable_app/index.php
