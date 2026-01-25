#!/bin/bash
# Vérifier si Docker est installé
if ! [ -x "$(command -v docker-compose)" ]; then
  echo 'Erreur: Docker Compose n est pas installé.' >&2
  exit 1
fi

# Démarrer les conteneurs
docker-compose up -d

# Ouvrir le navigateur
echo "L'application démarre sur http://localhost:8080"
sleep 5
xdg-open http://localhost:8080/timetable_app/index.php
