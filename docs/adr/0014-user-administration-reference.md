# ADR 0014: User administration reference

Status: accepted.
Implementation: complete.

The core RBAC example is User administration: authorized admins can search and page through Users, inspect identity and role metadata, and change `member` or `admin` roles through `users.view` and `users.manage`. An admin cannot change their own role or demote the Last admin, and the vertical cannot delete another User or inspect TOTP details. The first admin continues to be granted through the existing CLI command. Unused `settings.manage` permission is removed until a real global setting exists.
