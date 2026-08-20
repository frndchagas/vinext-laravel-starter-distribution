# ADR 0005: versioned authentication surface

Status: accepted.
Implementation: complete.

Fortify application routes live under `/api/v1/auth` and reuse framework controllers. `GET /sanctum/csrf-cookie` remains protocol infrastructure, and `GET /api/v1/me` exposes the current User. Routes consumed by application screens belong in TypeSpec.

Loose Fortify routes were rejected because they create an unversioned exception. Wrapper controllers were rejected because they duplicate validation, throttling and reset behavior already maintained by Fortify.
