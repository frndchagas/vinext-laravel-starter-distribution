# ADR 0007: Node container production topology

Status: accepted.
Implementation: complete.

The supported production reference runs Vinext on Node behind Caddy, with separate Laravel API, Horizon, scheduler and Reverb processes backed by PostgreSQL and Redis. Dockerfiles, production Compose, a CI smoke test and a Coolify-specific entry file are part of the repository. Consumers build images from the source tag. Public images and attestations may be added after there is demand. Cloudflare Workers may be added later as a separate deployment variant.
