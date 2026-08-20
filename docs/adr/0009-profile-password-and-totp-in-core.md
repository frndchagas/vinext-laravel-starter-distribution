# ADR 0009: profile, password and TOTP in core

Status: accepted.
Implementation: complete.

The authentication core includes profile settings, password changes and TOTP two-factor authentication with recovery codes. TOTP is available by default, but each User chooses whether to enable it. Configuration and recovery-code management require the current password. Passkeys, social login, SSO and teams remain outside the core. Laravel continues to own identity, sessions and authorization.
