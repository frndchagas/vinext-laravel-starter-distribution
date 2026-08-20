#!/usr/bin/env bash

set -euo pipefail

project_name="vinext-production-smoke-${GITHUB_RUN_ID:-$$}-${GITHUB_RUN_ATTEMPT:-1}"
image_tag="smoke-${GITHUB_RUN_ID:-$$}-${GITHUB_RUN_ATTEMPT:-1}"
production_port=${PRODUCTION_PORT:-14000}
cookie_jar=$(mktemp)

export APP_HOST="127.0.0.1:$production_port"
export APP_KEY
APP_KEY=$(php -r 'echo "base64:".base64_encode(random_bytes(32));')
export APP_NAME="Vinext production smoke"
export APP_URL="http://127.0.0.1:$production_port"
export IMAGE_TAG="$image_tag"
export MAIL_MAILER=log
export POSTGRES_PASSWORD=smoke-password
export PRODUCTION_PORT="$production_port"
export REVERB_APP_ID=starter
export REVERB_APP_KEY=smoke-key
export REVERB_APP_SECRET=smoke-secret
export SESSION_SECURE_COOKIE=false

compose=(
    docker compose
    --project-name "$project_name"
    --env-file .env.production.example
    --file compose.production.yaml
    --file compose.production.local.yaml
)

cleanup() {
    set +e
    "${compose[@]}" down --volumes --remove-orphans
    rm -f "$cookie_jar"
}

trap cleanup EXIT INT TERM

docker build \
    --file infra/docker/api/Dockerfile \
    --tag "vinext-ai-starter-api:$image_tag" \
    .

docker build \
    --file infra/docker/api-nginx/Dockerfile \
    --tag "vinext-ai-starter-api-nginx:$image_tag" \
    .

docker build \
    --file infra/docker/proxy/Dockerfile \
    --tag "vinext-ai-starter-proxy:$image_tag" \
    .

docker build \
    --build-arg "NEXT_PUBLIC_REVERB_APP_KEY=$REVERB_APP_KEY" \
    --file infra/docker/web/Dockerfile \
    --tag "vinext-ai-starter-web:$image_tag" \
    .

"${compose[@]}" up --detach --no-build --wait

curl --fail --silent --show-error "$APP_URL/" >/dev/null
curl --fail --silent --show-error "$APP_URL/up" >/dev/null

me_status=$(curl --silent --output /dev/null --write-out '%{http_code}' "$APP_URL/api/v1/me")
if [[ "$me_status" != 401 ]]; then
    echo "Expected anonymous /api/v1/me to return 401, received $me_status." >&2
    exit 1
fi

expected_migrations=$(find database/migrations -type f -name '*.php' | wc -l | tr -d ' ')
applied_migrations=$(
    "${compose[@]}" exec -T postgres \
        psql --username starter --dbname starter --tuples-only --no-align \
        --command 'select count(*) from migrations;'
)

if [[ "$applied_migrations" != "$expected_migrations" ]]; then
    echo "Expected $expected_migrations migrations, found $applied_migrations." >&2
    exit 1
fi

curl --fail --silent --show-error \
    --cookie-jar "$cookie_jar" \
    "$APP_URL/sanctum/csrf-cookie" >/dev/null

xsrf_token=$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$cookie_jar" | tail -n 1)
decoded_xsrf_token=$(php -r "echo urldecode(\$argv[1]);" "$xsrf_token")

register_status=$(
    curl --silent --output /dev/null --write-out '%{http_code}' \
        --cookie "$cookie_jar" \
        --cookie-jar "$cookie_jar" \
        --header 'Accept: application/json' \
        --header 'Content-Type: application/json' \
        --header "Origin: $APP_URL" \
        --header "Referer: $APP_URL/" \
        --header "X-XSRF-TOKEN: $decoded_xsrf_token" \
        --request POST \
        --data '{"name":"Smoke User","email":"smoke@example.invalid","password":"smoke-password","password_confirmation":"smoke-password"}' \
        "$APP_URL/api/v1/auth/register"
)

if [[ "$register_status" != 201 ]]; then
    echo "Expected registration to return 201, received $register_status." >&2
    exit 1
fi

xsrf_token=$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$cookie_jar" | tail -n 1)
decoded_xsrf_token=$(php -r "echo urldecode(\$argv[1]);" "$xsrf_token")

"${compose[@]}" exec -T postgres \
    psql --username starter --dbname starter \
    --command "update users set email_verified_at = now() where email = 'smoke@example.invalid';" \
    >/dev/null

task_response=$(
    curl --fail --silent --show-error \
        --cookie "$cookie_jar" \
        --header 'Accept: application/json' \
        --header 'Content-Type: application/json' \
        --header 'Idempotency-Key: production-smoke-task' \
        --header "Origin: $APP_URL" \
        --header "Referer: $APP_URL/" \
        --header "X-XSRF-TOKEN: $decoded_xsrf_token" \
        --request POST \
        --data '{"input":"production smoke"}' \
        "$APP_URL/api/v1/tasks"
)

task_id=$(php -r "\$data=json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR); echo \$data['id'];" <<<"$task_response")
task_completed=false

for _ in {1..30}; do
    task_response=$(
        curl --fail --silent --show-error \
            --cookie "$cookie_jar" \
            --header 'Accept: application/json' \
            --header "Origin: $APP_URL" \
            --header "Referer: $APP_URL/" \
            "$APP_URL/api/v1/tasks/$task_id"
    )
    task_state=$(php -r "\$data=json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR); echo \$data['state'];" <<<"$task_response")

    if [[ "$task_state" == completed ]]; then
        task_completed=true
        break
    fi

    sleep 1
done

if [[ "$task_completed" != true ]]; then
    echo "Task $task_id did not complete through Horizon." >&2
    exit 1
fi

echo "Production smoke passed with $applied_migrations migrations and a completed queued Task."
