#!/usr/bin/env bash
set -euo pipefail

usage() {
    echo "Usage: $0 <fichier_backup.sql[.gz]> [nom_base]"
    echo ""
    echo "  fichier_backup  Fichier .sql ou .sql.gz produit par db-backup.sh"
    echo "  nom_base        Base cible (défaut: resources)"
    echo ""
    echo "Exemples:"
    echo "  $0 backups/resources_20260427_120000.sql.gz"
    echo "  $0 backups/resources_test_20260427_120000.sql.gz resources_test"
    exit 1
}

BACKUP_FILE="${1:-}"
DB="${2:-resources}"
DB_CONTAINER="resources_db"

[[ -z "$BACKUP_FILE" ]] && usage
[[ ! -f "$BACKUP_FILE" ]] && { echo "Erreur: fichier introuvable: $BACKUP_FILE" >&2; exit 1; }

if ! docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    echo "Erreur: le conteneur '$DB_CONTAINER' n'est pas en cours d'exécution." >&2
    echo "Lancer: docker-compose up -d" >&2
    exit 1
fi

echo "Restauration de '$BACKUP_FILE' → base '$DB'"

if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" | docker exec -i "$DB_CONTAINER" mysql -u app -papp "$DB"
else
    docker exec -i "$DB_CONTAINER" mysql -u app -papp "$DB" < "$BACKUP_FILE"
fi

echo "Restauration terminée."
