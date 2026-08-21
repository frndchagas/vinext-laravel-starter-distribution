# ADR 0002: same-origin authentication

Status: accepted.
Implementation: complete.

Caddy exposes Vinext, Laravel and Reverb through one browser origin. Fortify provides identity flows, Sanctum authenticates the SPA with a session cookie, and the same Laravel guard authorizes private channels. A shared client layout queries Laravel once and centralizes authenticated navigation, redirects, loading and error states. Vinext server code and middleware do not interpret or replicate the Laravel session. Reverb is exposed under `/ws` because its native `/app/{key}` path conflicts with the web application's `app/` directory.

Bearer tokens and a separate TypeScript auth server were rejected because the browser is first party and Laravel already owns Policies, sessions and channel authorization. ADR 0009 records the accepted profile and TOTP expansion.
