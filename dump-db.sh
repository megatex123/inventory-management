#!/bin/sh
# Dumps the local dev DB (lokaldb container) into quivi.sql.
# Reads DB name/user/password from .env unless overridden via env vars.
set -e

cd "$(dirname "$0")"

DB_CONTAINER=${DB_CONTAINER:-lokaldb}
DB_NAME=${DB_NAME:-$(grep -m1 '^DB_DATABASE=' .env | cut -d= -f2-)}
DB_USER=${DB_USER:-$(grep -m1 '^DB_USERNAME=' .env | cut -d= -f2-)}
DB_PASSWORD=${DB_PASSWORD:-$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)}
OUTPUT=${1:-quivi.sql}

echo "# Dumping '$DB_NAME' from container '$DB_CONTAINER' to $OUTPUT #"
docker exec "$DB_CONTAINER" mariadb-dump \
    -u"$DB_USER" -p"$DB_PASSWORD" \
    --routines --triggers --single-transaction \
    "$DB_NAME" > "$OUTPUT"
echo "# Done #"
