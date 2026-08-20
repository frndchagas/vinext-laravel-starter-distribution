# Deployment

The production reference runs one public Caddy service with separate containers for Vinext, Laravel, Horizon, the scheduler and Reverb. PostgreSQL and Redis keep persistent volumes. Laravel migrations run once before application services start.

## Environment

Copy the example only for local inspection. In Coolify, create the variables through the environment editor instead of committing a file.

```bash
cp .env.production.example .env.production
```

Set unique values for `APP_KEY`, `POSTGRES_PASSWORD`, `REVERB_APP_KEY` and `REVERB_APP_SECRET`. `APP_URL` includes the public scheme and host. `APP_HOST` contains only the host, plus a port when the public URL uses one.

The example logs mail instead of sending it. Configure a real SMTP provider before enabling registration or password reset for users.

## Local production smoke

The automated smoke builds every image, starts the complete production topology, applies migrations and sends a Task through Horizon:

```bash
bun run test:production
```

The script uses a separate Compose project and deletes its containers and volumes when it exits.

## Coolify

Create a Docker Compose application from the public repository and select `compose.coolify.yaml`. Assign a domain to the `proxy` service on port `8080`. Keep PostgreSQL and Redis private. The Coolify-specific file excludes the one-time migration container from aggregate health checks and is generated from the production Compose file with `bun run coolify:build`.

Add every variable from `.env.production.example`. Do not expose the internal API, Reverb, PostgreSQL or Redis ports. Configure the service health path as `/up` with expected status `200`.

Deploys run migrations before starting the application services. Published migrations must remain backward compatible with the previous release. Back up PostgreSQL before a schema change and test restoration separately.

Coolify recreates services in a regular Docker Compose deployment. This reference does not claim zero downtime. Roll back by selecting the previous source tag and redeploying it; do not roll back the database unless the migration has an explicit reversal plan.
