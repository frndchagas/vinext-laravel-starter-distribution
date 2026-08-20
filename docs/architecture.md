# Architecture

## Current topology

The browser uses one origin published by Caddy:

```text
browser -> Caddy
             ├── /                         -> Vinext on Node
             ├── /api/*, /sanctum/*, /up  -> Laravel
             └── /ws/*                    -> Reverb, with /ws stripped

Laravel -> PostgreSQL
Laravel -> Redis / Horizon
```

Vinext owns presentation routes and user experience. Laravel owns identity, authorization, persistence, queues and events. Relative browser URLs keep internal service ports out of frontend code and avoid a cross-origin cookie setup.

Local development runs Caddy, PostgreSQL, Redis and Mailpit in Docker. Vinext, Laravel, Horizon and Reverb run as host processes through `bun run dev`.

Production builds separate images for Caddy, Vinext, Nginx and PHP-FPM. The PHP image is reused by the API, Horizon, scheduler, migration and Reverb services. PostgreSQL and Redis use named volumes. CI starts the complete topology and sends a Task through Horizon before accepting a change.

## Boundaries

- TypeSpec is the source for application HTTP consumed through the generated client.
- AsyncAPI describes contracted realtime messages. It does not provide implementation drift detection.
- Sanctum CSRF and Echo broadcasting authorization are protocol infrastructure, not generated-client operations.
- PostgreSQL state wins over any realtime notification.
- Roles and permissions are stored and exposed, but the current Task authorization rule is ownership, not role membership.

See [Authentication](authentication.md), [API conventions](api-conventions.md) and [Asynchronous workflow](async-workflow.md) for current behavior.

## Current limits

The starter has no AI provider, billing, teams or passkeys today. Vinext is still in beta and the React Compiler integration is experimental.

The production reference is a regular Docker Compose deployment. It is health checked and suitable for Coolify, but it does not provide zero-downtime traffic switching.

## Positioning

This project is a Laravel and Vinext foundation for coding agents. "AI-first" refers to discoverable instructions, executable contracts and objective gates. It does not mean the starter contains product-specific AI behavior.
