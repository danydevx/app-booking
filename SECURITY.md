# Security Policy

## Reporting a vulnerability

Please report vulnerabilities **privately** through GitHub Security Advisories
for this repository:

1. Go to the repository's **Security** tab.
2. Select **Report a vulnerability**.
3. Include reproduction steps, affected version/commit, impact and any suggested
   fix.

Do not open a public issue, pull request or discussion for an undisclosed
vulnerability.

## Scope

This is an application, not a distributable library. Security fixes are applied
to `main` and deployed through the normal release process.

In scope:

- authentication, authorization and tenant isolation;
- public listings, inquiries and report endpoints;
- file uploads and media handling;
- Stripe checkout, portal and webhook handling;
- platform superadmin and moderation;
- leaks of secrets, personal data or uploaded files.

## Operational expectations

- Never commit real `.env` values, Stripe live keys or database dumps.
- Use `composer audit` and `npm audit --omit=dev` in CI.
- Keep `main` deployable; do not bypass CI checks.
- Rotate any credential that may have been exposed.
