# Phase 7 — Branding, email and communications

**Status: NOT STARTED**

## Objective

Make the product presentable and operational for owners and visitors: account
branding, polished transactional email, queued notifications, and a safe WhatsApp
V1 flow.

## Branding

Keep this small. Do not build a full theme editor.

- Account settings: display name, logo, primary contact email, phone, WhatsApp
  number, timezone and public contact details.
- Platform branding: app name, logo, support email, legal links and default sender.
- Reuse the starter kit's visual system. Do not add a new CSS framework.
- Store logos on the configured public disk with MIME/size validation.
- Public listing pages display the account's logo/contact where configured.
- Avoid arbitrary user-supplied CSS or HTML.
- If a primary color is supported, validate it as a hex color and use it only
  through approved design tokens.

## Email

Use Laravel notifications/Markdown mail with queued delivery.

Create/verify:

- email verification;
- password reset;
- welcome email;
- booking inquiry received (owner);
- booking inquiry confirmation (visitor);
- inquiry accepted/declined (visitor);
- account/listing suspension notice (owner);
- subscription payment failure/grace-period notice (owner);
- optional weekly lead summary (only if it can be disabled).

Rules:

- Use a platform From address; use the owner's email as Reply-To where appropriate.
- Do not spoof an owner's domain.
- Keep templates responsive and plain enough to render in common clients.
- Do not include sensitive data in subject lines.
- Queue messages. Configure a real SMTP provider only at deployment.
- Add a local Mailpit/log workflow; never send real email in tests.
- Provide Spanish copy first and keep strings translatable.

## WhatsApp V1

Use click-to-chat only:

```text
https://wa.me/<E.164 digits>?text=<url-encoded message>
```

- Build the link server-side or in a small composable from validated phone data.
- Sanitize/encode the message; never inject raw HTML.
- Include listing name and dates but not unnecessary personal data.
- Display a clear “Abrir WhatsApp” action; do not pretend the message was sent by the
  platform.
- Do not add WhatsApp Business API, templates, opt-in management or webhooks yet.
- Automated WhatsApp is phase 9, only after a provider, consent and cost decision.

## Notifications and preferences

- Use database + mail channels for in-app notification lists where useful.
- Respect account/user notification preferences at a basic level.
- Do not send duplicate owner emails for the same inquiry event.
- Ensure notification failures do not break the booking transaction; queue after
  commit.

## Tests

- Branding upload validates MIME/size and is account-scoped.
- Public page uses the correct account branding.
- Emails are queued and contain the expected listing/inquiry data.
- Suspension email is sent only to the affected account owner/contact.
- WhatsApp URL is correctly encoded and rejects malformed phone numbers.
- No Bootstrap/jQuery.

## Commands

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

## Exit criteria

- Account branding and transactional email are usable.
- Notifications are queued.
- WhatsApp V1 is safe and clearly manual.
- Phase report and status updated.

## Do not

- Do not build an email campaign builder.
- Do not add an external WhatsApp/AI provider.
- Do not allow arbitrary CSS/HTML from accounts.
