#!/usr/bin/env bash

set -euo pipefail

input=${1:?Usage: bun run db:restore -- <backup-file> <new-database>}
target=${2:?Usage: bun run db:restore -- <backup-file> <new-database>}
created=false
completed=false
compose=(docker compose)

if [[ -n "${POSTGRES_ENV_FILE:-}" ]]; then
    compose+=(--env-file "$POSTGRES_ENV_FILE")
fi

source_database=${DB_DATABASE:-$("${compose[@]}" exec -T postgres printenv POSTGRES_DB)}
username=${DB_USERNAME:-$("${compose[@]}" exec -T postgres printenv POSTGRES_USER)}

if [[ ! -s "$input" ]]; then
    echo "Backup does not exist or is empty: $input" >&2
    exit 1
fi

if [[ ! "$target" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]]; then
    echo "Restore database names may contain only letters, numbers and underscores." >&2
    exit 1
fi

if [[ "$target" == "$source_database" ]]; then
    echo "Restore into a new database; replacing $source_database in place is not allowed." >&2
    exit 1
fi

exists=$(
    "${compose[@]}" exec -T postgres \
        psql --username "$username" --dbname postgres --tuples-only --no-align \
        --command "select 1 from pg_database where datname = '$target';"
)

if [[ "$exists" == 1 ]]; then
    echo "Restore target already exists: $target" >&2
    exit 1
fi

cleanup() {
    if [[ "$created" == true && "$completed" != true ]]; then
        "${compose[@]}" exec -T postgres \
            dropdb --username "$username" --if-exists "$target" >/dev/null
    fi
}

trap cleanup EXIT INT TERM

"${compose[@]}" exec -T postgres \
    createdb --username "$username" --owner "$username" "$target"
created=true

"${compose[@]}" exec -T postgres \
    pg_restore \
    --username "$username" \
    --dbname "$target" \
    --exit-on-error \
    --no-owner \
    --no-privileges \
    <"$input"

completed=true
echo "PostgreSQL backup restored into $target."
