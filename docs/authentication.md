# Authentication and authorization

## Current behavior

Laravel is the only identity source. Fortify handles registration, login, logout, password reset, email verification, profile settings, password changes and TOTP. Sanctum authenticates the first-party SPA with a session cookie and CSRF protection.

The application consumes these versioned routes:

- registration, login and logout;
- forgot-password and reset-password;
- signed email verification and verification resend;
- profile, password confirmation and password update;
- TOTP setup, confirmation, recovery codes, disablement and login challenge;
- `GET /api/v1/me` for the current User.

`GET /sanctum/csrf-cookie` and `POST /api/broadcasting/auth` are framework protocol endpoints. They are called directly by the session and Echo clients rather than through generated product hooks.

## Verification and access

Registration is controlled by `FEATURE_REGISTRATION`. A newly registered User receives the `member` role and must verify their email before accessing Tasks or private channels.

The `admin` and `member` roles and example permissions are persisted by an idempotent seeder and returned by `/api/v1/me`. They provide an authorization foundation. Current Task access is enforced by ownership Policy; no product operation is gated by role or permission yet.

The first admin is promoted explicitly:

```bash
cd the repository root
php artisan app:grant-admin user@example.com
```

## Sessions and realtime

Echo authorizes every private subscription through the same Laravel session. Logout disconnects Echo. Reconnection requires fresh channel authorization and then refetches persisted state.

Changing the account password invalidates the current Sanctum session. The interface returns the User to login with a confirmation message.

## Two-factor authentication

Each User decides whether to enable TOTP. Enabling, viewing recovery codes, regenerating codes and disabling TOTP require the current password. Setup is not active until the User confirms a valid code from an authenticator application.

Recovery codes are shown after confirmation and can be regenerated. Each code works once. A login that returns `two_factor: true` continues through the versioned challenge endpoint with either a TOTP code or one recovery code.

Passkeys, social login, SSO and teams remain outside the core.
