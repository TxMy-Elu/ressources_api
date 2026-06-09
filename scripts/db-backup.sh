#!/usr/bin/env bash
set -euo pipefail

DB_CONTAINER="resources_db"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/../backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

if ! docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    echo "Erreur: le conteneur '$DB_CONTAINER' n'est pas en cours d'exécution." >&2
    echo "Lancer: docker-compose up -d" >&2
    exit 1
fi

for DB in resources resources_test; do
    FILE="$BACKUP_DIR/${DB}_${TIMESTAMP}.sql.gz"
    echo "Sauvegarde de '$DB' → $FILE"
    docker exec "$DB_CONTAINER" mysqldump -u app -papp --single-transaction "$DB" \
        | gzip > "$FILE"
    echo "  OK ($(du -sh "$FILE" | cut -f1))"
done

echo ""
echo "Backups disponibles dans: $BACKUP_DIR"
