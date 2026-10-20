# Security Policy

Navdhya is a spiritual-services platform handling bookings, payments, identity, and partner operations. We welcome good-faith security research and take every report seriously.

## Supported versions

| Version | Supported |
| --- | --- |
| `main` (latest commit) | ✅ |
| Latest published release | ✅ |
| Older commits, forks, and unmaintained branches | ❌ |

Security fixes land on `main` first. If you run a fork or a pinned commit, rebase before testing.

## Reporting a vulnerability

**Do not open a public issue, discussion, or pull request containing exploit details.**

Report privately using one of these channels:

1. **Email** — `hntlsquad@gmail.com` (primary). If you need encryption, say so in your first message and we will arrange a key exchange.
2. **GitHub Private Vulnerability Reporting** — if the repository's **Security** tab offers **Report a vulnerability**, you may use it instead. It creates a private advisory with the maintainers.

Please include:

- A short summary and your severity estimate.
- The affected component, URL, commit/version, and environment.
- Reproduction steps and a non-destructive proof of concept.
- The impact you believe is possible — for example data exposure, account takeover, payment manipulation, or cross-tenant access.
- Any suggested remediation.
- Your name or handle and whether you want credit.

Never access, modify, retain, or share real customer data, OTPs, session tokens, payment credentials, or another person's account. Use test accounts and your own data, and do not test against production payments or live customer records.

## What to expect

| Stage | Target |
| --- | --- |
| Acknowledgement | 2 business days |
| Initial triage and severity | 5 business days |
| Critical fix | 7 days |
| High fix | 30 days |
| Medium fix | 90 days |
| Low fix | Next scheduled release |

We will keep you informed, explain if a report is out of scope, and coordinate disclosure. We aim to publish an advisory within 90 days of the report or as soon as a fix is available, whichever is earlier. We will credit you unless you ask to remain anonymous.

We do not currently run a paid bug bounty. Recognition or swag may be offered at the maintainers' discretion.

## Scope

### In scope

- `apps/web`, `apps/customer`, `apps/dashboard`, and `apps/partner`
- `platform-navdhya/` — Laravel API, modules, migrations, queues, and scheduled jobs
- Authentication, SSO, OTP, sessions, and CSRF handling
- Booking, pricing, payment, refund, cancellation, and reschedule flows
- Tenant isolation, role and permission checks, and the admin/platform consoles
- BFF routes under `/api/*` and their upstream contracts
- Shared packages and infrastructure configuration in this repository
- Secrets or credentials committed to this repository

### Out of scope

- Third-party services such as MSG91, Razorpay, Vercel, GitHub, or cloud providers. Report to the provider; tell us if Navdhya's integration is affected.
- Denial of service, volumetric attacks, brute force, or rate-limit exhaustion.
- Social engineering, phishing, or physical attacks.
- Spam, content abuse, or automated scanner output without a reproducible exploit.
- Missing security headers or best-practice configuration without a demonstrated impact.
- Issues requiring an unsupported browser, a rooted or compromised device, or a compromised user account.
- Publicly known dependency vulnerabilities with no reachable exploit path. These are still welcome, but lower priority.
- Test, demo, or mock data, and placeholder values in `.env.example`.

## Safe harbor

We will not pursue legal action for good-faith research that:

- Stays within the scope above.
- Uses only test accounts and your own data.
- Avoids privacy violations, data destruction, and service disruption.
- Stops immediately and reports if you access sensitive data.
- Gives us reasonable time to fix before public disclosure.

If you are unsure whether an action is allowed, ask us first at `hntlsquad@gmail.com`.

## For contributors

- Never commit secrets, tokens, private keys, or real customer data. Use `.env.local` or the secret manager; `.env.example` contains placeholders only.
- Run `pnpm secrets:scan` before committing and `pnpm secrets:scan:all` before pushing; the former scans only staged changes, the latter scans full history. The pre-commit hook runs gitleaks only when gitleaks is installed, so install gitleaks (or run the npm scripts manually) rather than relying on the hook alone.
- Do not weaken authentication, authorization, tenant isolation, RLS, CSRF, or session protections without a reviewed threat model and tests.
- Keep dependencies patched and review `pnpm audit` output.
- Report dependency or supply-chain issues through the private channels above, not a public issue.
- If a secret is exposed, rotate it immediately and notify the maintainers. Deleting the commit is not sufficient.

## Contact

- Security reports: `hntlsquad@gmail.com`
- GitHub: **Security** tab → **Report a vulnerability** (when available)
- Product support: use the in-app support channel. Do not send vulnerability details there.

Thank you for helping keep Navdhya and its users safe.
